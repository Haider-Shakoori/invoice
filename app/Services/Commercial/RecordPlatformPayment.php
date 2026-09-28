<?php

namespace App\Services\Commercial;

use App\Enums\PaymentMethod;
use App\Enums\PlatformInvoiceStatus;
use App\Enums\PlatformPaymentStatus;
use App\Models\Central\AdminUser;
use App\Models\Central\PlatformInvoice;
use App\Models\Central\PlatformPayment;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class RecordPlatformPayment
{
    public function __construct(
        private readonly CommercialNumberGenerator $numbers,
        private readonly SubscriptionLifecycle $lifecycle,
        private readonly SellerCommissionService $commissions,
        private readonly SubscriptionAuditLogger $audit,
    ) {}

    public function handle(
        PlatformInvoice $invoice,
        int $amountAfn,
        PaymentMethod|string $method,
        ?CarbonInterface $receivedAt = null,
        ?string $reference = null,
        ?string $notes = null,
        ?AdminUser $recordedBy = null,
    ): PlatformPayment {
        if ($invoice->status === PlatformInvoiceStatus::Void) {
            throw new DomainException('Payments cannot be recorded against a void platform invoice.');
        }

        if ($amountAfn <= 0) {
            throw new DomainException('Payment amount must be greater than zero.');
        }

        $method = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);
        $receivedAt ??= now();
        $connection = (new PlatformPayment)->getConnectionName();

        return DB::connection($connection)->transaction(function () use (
            $invoice,
            $amountAfn,
            $method,
            $receivedAt,
            $reference,
            $notes,
            $recordedBy,
        ): PlatformPayment {
            $invoice = PlatformInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $paid = (int) $invoice->payments()
                ->where('status', PlatformPaymentStatus::Recorded->value)
                ->sum('amount_afn');

            $outstanding = max(0, $invoice->total_afn - $paid);

            if ($amountAfn > $outstanding) {
                throw new DomainException("Payment exceeds the outstanding amount of {$outstanding} AFN.");
            }

            $payment = PlatformPayment::query()->create([
                'receipt_number' => $this->numbers->next('receipt'),
                'platform_invoice_id' => $invoice->id,
                'business_id' => $invoice->business_id,
                'recorded_by_admin_user_id' => $recordedBy?->id,
                'method' => $method,
                'status' => PlatformPaymentStatus::Recorded,
                'amount_afn' => $amountAfn,
                'reference' => $reference,
                'notes' => $notes,
                'received_at' => $receivedAt,
            ]);

            $newPaid = $paid + $amountAfn;

            if ($newPaid >= $invoice->total_afn) {
                $invoice->update([
                    'status' => PlatformInvoiceStatus::Paid,
                    'paid_at' => $receivedAt,
                ]);

                if ($subscription = $invoice->subscription()->first()) {
                    if ($invoice->type === 'activation') {
                        $this->lifecycle->activate($subscription, $receivedAt, $recordedBy?->id);
                    } elseif ($invoice->type === 'renewal') {
                        $this->lifecycle->renew($subscription, $receivedAt, $recordedBy?->id);
                    }

                    $this->audit->record(
                        $subscription->fresh(),
                        'platform_invoice_paid',
                        $subscription->fresh()->status,
                        $subscription->fresh()->status,
                        [
                            'platform_invoice_id' => $invoice->id,
                            'platform_payment_id' => $payment->id,
                            'amount_afn' => $amountAfn,
                        ],
                        $recordedBy ? 'admin' : 'system',
                        $recordedBy?->id,
                    );
                }
            }

            $this->commissions->createForPayment($payment);

            return $payment->fresh(['invoice', 'commission']);
        });
    }
}
