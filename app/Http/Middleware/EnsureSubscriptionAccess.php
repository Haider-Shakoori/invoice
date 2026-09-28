<?php

namespace App\Http\Middleware;

use App\Models\Central\Business;
use App\Services\Commercial\SubscriptionLifecycle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAccess
{
    public function __construct(private readonly SubscriptionLifecycle $lifecycle) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = tenant('id');

        $business = Business::query()
            ->where('tenant_id', $tenantId)
            ->with('subscription')
            ->first();

        if ($business?->subscription === null) {
            return response()->json([
                'message' => 'Subscription information is not available for this business.',
                'subscription_status' => 'missing',
                'data_retained' => true,
            ], 402);
        }

        $subscription = $this->lifecycle->synchronize($business->subscription);

        if (! $subscription->status->allowsTenantAccess()) {
            return response()->json([
                'message' => 'This subscription is not currently active. Tenant data remains safely retained.',
                'subscription_status' => $subscription->status->value,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                'data_retained' => true,
            ], 402);
        }

        $request->attributes->set('subscription', $subscription);

        return $next($request);
    }
}
