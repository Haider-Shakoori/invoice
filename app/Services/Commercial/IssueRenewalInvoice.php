<?php

namespace App\Services\Commercial;

use App\Enums\PlatformInvoiceStatus;
use App\Models\Central\PlatformInvoice;
use App\Models\Central\Subscription;
use Illuminate\Support\Facades\DB;

class IssueRenewalInvoice
{
    public function __construct(
        private readonly CommercialNumberGenerator $numbers,
        private readonly SubscriptionAuditLogger $audit,
    ) {}

    public function handle(Subscription $subscription): PlatformInvoice
    {
        if ($existing = $subscription->invoices()
            ->where('type', 'renewal')
            ->whereIn('status', [
                PlatformInvoiceStatus::Issued->value,
                PlatformInvoiceStatus::Overdue->value,
            ])
            ->latest('id')
            ->first()) {
            return $existing;
        }

        $plan = $subscription->plan()->firstOrFail();
        $connection = (new PlatformInvoice)->getConnectionName();

        $invoice = DB::connection($connection)->transaction(function () use ($subscription, $plan): PlatformInvoice {
            $invoice = PlatformInvoice::query()->create([
                'number' => $this->numbers->next('platform_invoice'),
                'business_id' => $subscription->business_id,
                'subscription_id' => $subscription->id,
                'type' => 'renewal',
                'status' => PlatformInvoiceStatus::Issued,
                'currency' => 'AFN',
                'subtotal_afn' => $plan->renewal_fee_afn,
                'discount_afn' => 0,
                'total_afn' => $plan->renewal_fee_afn,
                'issued_at' => now(),
                'due_at' => now()->addDays((int) config('invoice.commercial.payment_due_days', 7)),
            ]);

            $invoice->lines()->create([
                'position' => 1,
                'description' => 'Annual subscription renewal',
                'quantity' => 1,
                'unit_price_afn' => $plan->renewal_fee_afn,
                'amount_afn' => $plan->renewal_fee_afn,
                'meta' => ['component' => 'renewal'],
            ]);

            return $invoice;
        });

        $this->audit->record(
            $subscription,
            'renewal_invoice_issued',
            $subscription->status,
            $subscription->status,
            ['platform_invoice_id' => $invoice->id, 'number' => $invoice->number, 'total_afn' => $invoice->total_afn],
        );

        return $invoice->load('lines');
    }
}
