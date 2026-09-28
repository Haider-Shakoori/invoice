<?php

namespace App\Services\Commercial;

use App\Enums\PlatformInvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Central\PlatformInvoice;
use App\Models\Central\Subscription;
use LogicException;

class ApplyPaidInvoiceToSubscription
{
    public function __construct(private readonly SubscriptionAuditLogger $audit)
    {
    }

    public function handle(PlatformInvoice $invoice): Subscription
    {
        if ($invoice->status !== PlatformInvoiceStatus::Paid) {
            throw new LogicException('Only a paid platform invoice can change subscription entitlement.');
        }

        $subscription = Subscription::query()
            ->whereKey($invoice->subscription_id)
            ->lockForUpdate()
            ->firstOrFail();

        $plan = $subscription->plan()->firstOrFail();
        $paidAt = $invoice->paid_at ?? now();
        $previousStatus = $subscription->status;

        if ($invoice->type === 'activation') {
            $periodStart = $subscription->trial_ends_at && $subscription->trial_ends_at->greaterThan($paidAt)
                ? $subscription->trial_ends_at->copy()
                : $paidAt->copy();
            $event = 'subscription_activated';
        } elseif ($invoice->type === 'renewal') {
            $periodStart = $subscription->current_period_end && $subscription->current_period_end->greaterThan($paidAt)
                ? $subscription->current_period_end->copy()
                : $paidAt->copy();
            $event = 'subscription_renewed';
        } else {
            throw new LogicException("Unsupported platform invoice type [{$invoice->type}].");
        }

        $periodEnd = $periodStart->copy()->addMonthsNoOverflow($plan->term_months);

        $subscription->forceFill([
            'status' => SubscriptionStatus::Active,
            'activated_at' => $subscription->activated_at ?? $paidAt,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'grace_ends_at' => null,
            'locked_at' => null,
        ])->save();

        $subscription->business()->update([
            'status' => 'active',
            'trial_ends_at' => $subscription->trial_ends_at,
            'subscription_ends_at' => $periodEnd,
        ]);

        $this->audit->record(
            $subscription,
            $event,
            $previousStatus,
            SubscriptionStatus::Active,
            [
                'platform_invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'period_start' => $periodStart->toIso8601String(),
                'period_end' => $periodEnd->toIso8601String(),
            ],
        );

        return $subscription->fresh();
    }
}
