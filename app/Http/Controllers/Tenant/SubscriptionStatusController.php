<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\ActivationRequest;
use App\Models\Central\Business;
use App\Services\Commercial\SubscriptionLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionStatusController extends Controller
{
    public function __invoke(Request $request, SubscriptionLifecycle $lifecycle): View|JsonResponse
    {
        $business = Business::query()
            ->where('tenant_id', tenant('id'))
            ->with(['subscription.plan'])
            ->firstOrFail();

        $subscription = $business->subscription;

        if (! $subscription) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'missing', 'access_allowed' => false]);
            }

            return view('tenant.subscription.show', compact('business', 'subscription'));
        }

        $subscription = $lifecycle->synchronize($subscription);
        $subscription->loadMissing('plan');

        if ($request->expectsJson()) {
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

        $pendingActivation = ActivationRequest::query()
            ->where('business_id', $business->id)
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        return view('tenant.subscription.show', compact('business', 'subscription', 'pendingActivation'));
    }
}
