<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\Business;
use App\Services\Commercial\SubscriptionLifecycle;
use Illuminate\Http\JsonResponse;

class SubscriptionStatusController extends Controller
{
    public function __invoke(SubscriptionLifecycle $lifecycle): JsonResponse
    {
        $business = Business::query()
            ->where('tenant_id', tenant('id'))
            ->with(['subscription.plan'])
            ->firstOrFail();

        $subscription = $business->subscription;

        if (! $subscription) {
            return response()->json([
                'status' => 'missing',
                'access_allowed' => false,
            ], 200);
        }

        $subscription = $lifecycle->synchronize($subscription);

        return response()->json([
            'status' => $subscription->status->value,
            'access_allowed' => $subscription->status->allowsTenantAccess(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'current_period_end' => $subscription->current_period_end?->toIso8601String(),
            'grace_ends_at' => $subscription->grace_ends_at?->toIso8601String(),
            'plan' => [
                'code' => $subscription->plan->code,
                'name' => $subscription->plan->name,
                'renewal_fee_afn' => $subscription->plan->renewal_fee_afn,
            ],
        ]);
    }
}
