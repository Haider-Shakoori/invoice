<?php

namespace App\Services\Commercial;

use App\Enums\PlatformInvoiceStatus;
use App\Models\Central\PlatformInvoice;
use App\Models\Central\Subscription;
use Illuminate\Support\Facades\DB;
use LogicException;

class IssueActivationInvoice
{
    public function __construct(
        private readonly CommercialNumberGenerator $numbers,
        private readonly SubscriptionAuditLogger $audit,
    ) {
    }

    public function handle(Subscription $subscription): PlatformInvoice
    {
        if ($existing = $subscription->invoices()
            ->where('type', 'activation')
            ->whereIn('status', [
                PlatformInvoiceStatus::Issued->value,
                PlatformInvoiceStatus::Overdue->value,
            ])
            ->latest('id')
            ->first()) {
            return $existing;
        }

        if ($subscription->invoices()
            ->where('type', 'activation')
            ->where('status', PlatformInvoiceStatus::Paid->value)
            ->exists()) {
            throw new LogicException('This subscription already has a paid activation invoice.');
        }

        $plan = $subscription->plan()->firstOrFail();
        $connection = (new PlatformInvoice)->getConnectionName();

        $invoice = DB::connection($connection)->transaction(function () use ($subscription, $plan): PlatformInvoice {
            $subtotal = $plan->setup_fee_afn + $plan->first_term_fee_afn;

            $invoice = PlatformInvoice::query()->create([
                'number' => $this->numbers->next('platform_invoice'),
                'business_id' => $subscription->business_id,
                'subscription_id' => $subscription->id,
                'type' => 'activation',
                'status' => PlatformInvoiceStatus::Issued,
                'currency' => 'AFN',
                'subtotal_afn' => $subtotal,
                'discount_afn' => 0,
                'total_afn' => $subtotal,
                'issued_at' => now(),
                'due_at' => now()->addDays((int) config('invoice.commercial.payment_due_days', 7)),
            ]);

            $invoice->lines()->createMany([
                [
                    'position' => 1,
                    'description' => 'Platform setup and activation',
                    'quantity' => 1,
                    'unit_price_afn' => $plan->setup_fee_afn,
                    'amount_afn' => $plan->setup_fee_afn,
                    'meta' => ['component' => 'setup'],
                ],
                [
                    'position' => 2,
                    'description' => 'First annual subscription term',
                    'quantity' => 1,
                    'unit_price_afn' => $plan->first_term_fee_afn,
                    'amount_afn' => $plan->first_term_fee_afn,
                    'meta' => ['component' => 'first_term'],
                ],
            ]);

            return $invoice;
        });

        $this->audit->record(
            $subscription,
            'activation_invoice_issued',
            $subscription->status,
            $subscription->status,
            ['platform_invoice_id' => $invoice->id, 'number' => $invoice->number, 'total_afn' => $invoice->total_afn],
        );

        return $invoice->load('lines');
    }
}
