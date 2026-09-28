<?php

namespace App\Services\Commercial;

use App\Models\Central\Subscription;

class SubscriptionStatusSynchronizer
{
    public function __construct(private readonly SubscriptionLifecycle $lifecycle)
    {
    }

    public function handle(): int
    {
        $updated = 0;

        Subscription::query()
            ->whereIn('status', ['trialing', 'active', 'grace'])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$updated): void {
                foreach ($subscriptions as $subscription) {
                    $before = $subscription->status->value;
                    $after = $this->lifecycle->synchronize($subscription)->status->value;

                    if ($before !== $after) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
