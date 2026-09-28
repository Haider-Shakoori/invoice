<?php

namespace App\Services\Commercial;

use App\Enums\SubscriptionStatus;
use App\Models\Central\Subscription;
use App\Models\Central\SubscriptionAuditEvent;

class SubscriptionAuditLogger
{
    public function record(
        Subscription $subscription,
        string $event,
        SubscriptionStatus|string|null $previousStatus = null,
        SubscriptionStatus|string|null $newStatus = null,
        array $context = [],
        string $actorType = 'system',
        ?int $actorId = null,
    ): SubscriptionAuditEvent {
        return SubscriptionAuditEvent::query()->create([
            'subscription_id' => $subscription->id,
            'business_id' => $subscription->business_id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'event' => $event,
            'previous_status' => $previousStatus instanceof SubscriptionStatus ? $previousStatus->value : $previousStatus,
            'new_status' => $newStatus instanceof SubscriptionStatus ? $newStatus->value : $newStatus,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
