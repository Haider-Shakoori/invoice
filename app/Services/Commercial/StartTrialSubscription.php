<?php

namespace App\Services\Commercial;

use App\Enums\SubscriptionStatus;
use App\Models\Central\Business;
use App\Models\Central\Subscription;
use Carbon\CarbonInterface;

class StartTrialSubscription
{
    public function __construct(
        private readonly EnsureDefaultPlan $ensureDefaultPlan,
        private readonly SubscriptionAuditLogger $audit,
    ) {
    }

    public function handle(
        Business $business,
        ?int $sellerId = null,
        ?CarbonInterface $startedAt = null,
        ?CarbonInterface $endsAt = null,
    ): Subscription {
        if ($existing = $business->subscription()->first()) {
            return $existing;
        }

        $plan = $this->ensureDefaultPlan->handle();
        $startedAt ??= now();
        $endsAt ??= $startedAt->copy()->addDays($plan->trial_days);

        $subscription = Subscription::query()->create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'seller_id' => $sellerId,
            'status' => SubscriptionStatus::Trialing,
            'trial_started_at' => $startedAt,
            'trial_ends_at' => $endsAt,
        ]);

        $business->update([
            'status' => 'trial',
            'trial_ends_at' => $endsAt,
            'subscription_ends_at' => null,
        ]);

        $this->audit->record(
            $subscription,
            'trial_started',
            null,
            SubscriptionStatus::Trialing,
            ['trial_ends_at' => $endsAt->toIso8601String()],
        );

        return $subscription;
    }
}
