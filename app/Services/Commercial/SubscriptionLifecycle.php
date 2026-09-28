<?php

namespace App\Services\Commercial;

use App\Enums\SubscriptionStatus;
use App\Models\Central\Subscription;
use Carbon\CarbonInterface;

class SubscriptionLifecycle
{
    public function __construct(private readonly SubscriptionAuditLogger $audit)
    {
    }

    public function activate(Subscription $subscription, CarbonInterface $effectiveAt, ?int $actorId = null): Subscription
    {
        $previous = $subscription->status;
        $plan = $subscription->plan()->firstOrFail();
        $periodEnd = $effectiveAt->copy()->addMonths($plan->term_months);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'activated_at' => $subscription->activated_at ?? $effectiveAt,
            'current_period_start' => $effectiveAt,
            'current_period_end' => $periodEnd,
            'grace_ends_at' => null,
            'locked_at' => null,
        ]);

        $subscription->business()->update([
            'status' => 'active',
            'subscription_ends_at' => $periodEnd,
        ]);

        $this->audit->record(
            $subscription,
            'subscription_activated',
            $previous,
            SubscriptionStatus::Active,
            ['current_period_end' => $periodEnd->toIso8601String()],
            $actorId ? 'admin' : 'system',
            $actorId,
        );

        return $subscription->fresh();
    }

    public function renew(Subscription $subscription, CarbonInterface $effectiveAt, ?int $actorId = null): Subscription
    {
        $previous = $subscription->status;
        $plan = $subscription->plan()->firstOrFail();

        $base = $subscription->current_period_end && $subscription->current_period_end->isFuture()
            ? $subscription->current_period_end->copy()
            : $effectiveAt->copy();

        $periodEnd = $base->copy()->addMonths($plan->term_months);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'activated_at' => $subscription->activated_at ?? $effectiveAt,
            'current_period_start' => $subscription->current_period_end && $subscription->current_period_end->isFuture()
                ? $subscription->current_period_start
                : $effectiveAt,
            'current_period_end' => $periodEnd,
            'grace_ends_at' => null,
            'locked_at' => null,
        ]);

        $subscription->business()->update([
            'status' => 'active',
            'subscription_ends_at' => $periodEnd,
        ]);

        $this->audit->record(
            $subscription,
            'subscription_renewed',
            $previous,
            SubscriptionStatus::Active,
            ['current_period_end' => $periodEnd->toIso8601String()],
            $actorId ? 'admin' : 'system',
            $actorId,
        );

        return $subscription->fresh();
    }

    public function synchronize(Subscription $subscription, ?CarbonInterface $at = null): Subscription
    {
        $at ??= now();

        if ($subscription->status === SubscriptionStatus::Trialing
            && $subscription->trial_ends_at
            && $subscription->trial_ends_at->lte($at)) {
            return $this->expire($subscription, 'trial_expired', $at);
        }

        if ($subscription->status === SubscriptionStatus::Active
            && $subscription->current_period_end
            && $subscription->current_period_end->lte($at)) {
            $graceDays = (int) config('invoice.commercial.grace_days', 0);

            if ($graceDays > 0) {
                return $this->enterGrace($subscription, $at, $graceDays);
            }

            return $this->expire($subscription, 'subscription_expired', $at);
        }

        if ($subscription->status === SubscriptionStatus::Grace
            && $subscription->grace_ends_at
            && $subscription->grace_ends_at->lte($at)) {
            return $this->expire($subscription, 'grace_expired', $at);
        }

        return $subscription;
    }

    private function enterGrace(Subscription $subscription, CarbonInterface $at, int $days): Subscription
    {
        $previous = $subscription->status;
        $graceEnd = $at->copy()->addDays($days);

        $subscription->update([
            'status' => SubscriptionStatus::Grace,
            'grace_ends_at' => $graceEnd,
            'locked_at' => null,
        ]);

        $subscription->business()->update(['status' => 'grace']);

        $this->audit->record(
            $subscription,
            'subscription_grace_started',
            $previous,
            SubscriptionStatus::Grace,
            ['grace_ends_at' => $graceEnd->toIso8601String()],
        );

        return $subscription->fresh();
    }

    private function expire(Subscription $subscription, string $event, CarbonInterface $at): Subscription
    {
        $previous = $subscription->status;

        $subscription->update([
            'status' => SubscriptionStatus::Expired,
            'locked_at' => $subscription->locked_at ?? $at,
        ]);

        $subscription->business()->update(['status' => 'expired']);

        $this->audit->record(
            $subscription,
            $event,
            $previous,
            SubscriptionStatus::Expired,
            ['locked_at' => $subscription->fresh()->locked_at?->toIso8601String()],
        );

        return $subscription->fresh();
    }
}
