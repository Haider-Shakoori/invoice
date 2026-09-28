<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Central\ActivationRequest;
use App\Models\Central\Business;
use App\Services\Commercial\SubscriptionAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivationRequestController extends Controller
{
    public function store(Request $request, SubscriptionAuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $business = Business::query()
            ->where('tenant_id', tenant('id'))
            ->firstOrFail();

        $subscription = $business->subscription()->firstOrFail();

        if ($subscription->status === SubscriptionStatus::Active && $subscription->current_period_end?->isFuture()) {
            return response()->json(['message' => 'The subscription is already active.'], 422);
        }

        $activationRequest = ActivationRequest::query()->firstOrCreate(
            [
                'business_id' => $business->id,
                'status' => 'pending',
            ],
            [
                'seller_id' => $subscription->seller_id,
                'request_note' => $data['note'] ?? null,
                'requested_at' => now(),
            ],
        );

        if ($activationRequest->wasRecentlyCreated) {
            $audit->record(
                $subscription,
                'activation_requested',
                $subscription->status,
                $subscription->status,
                ['activation_request_id' => $activationRequest->id],
                'tenant_user',
                auth()->id(),
            );
        }

        return response()->json($activationRequest, $activationRequest->wasRecentlyCreated ? 201 : 200);
    }
}
