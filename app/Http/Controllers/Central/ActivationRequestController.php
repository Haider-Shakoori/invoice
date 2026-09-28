<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\ActivationRequest;
use App\Services\Commercial\IssueActivationInvoice;
use App\Services\Commercial\SubscriptionAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivationRequestController extends Controller
{
    public function index(): View
    {
        return view('central.activation-requests', [
            'requests' => ActivationRequest::query()
                ->with(['business.subscription', 'seller', 'reviewedBy'])
                ->latest('requested_at')
                ->paginate(50),
        ]);
    }

    public function approve(
        Request $request,
        ActivationRequest $activationRequest,
        IssueActivationInvoice $issue,
        SubscriptionAuditLogger $audit,
    ): JsonResponse {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($activationRequest->status === 'pending', 422, 'Activation request has already been reviewed.');

        $subscription = $activationRequest->business()->firstOrFail()->subscription()->firstOrFail();

        if ($activationRequest->seller_id && ! $subscription->seller_id) {
            $subscription->update(['seller_id' => $activationRequest->seller_id]);
        }

        $invoice = $issue->handle($subscription);

        $activationRequest->update([
            'status' => 'approved',
            'reviewed_by_admin_user_id' => Auth::guard('central')->id(),
            'review_note' => $data['review_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        $audit->record(
            $subscription,
            'activation_request_approved',
            $subscription->status,
            $subscription->status,
            ['activation_request_id' => $activationRequest->id, 'platform_invoice_id' => $invoice->id],
            'admin',
            Auth::guard('central')->id(),
        );

        return response()->json([
            'activation_request' => $activationRequest->fresh(),
            'invoice' => $invoice,
        ]);
    }

    public function reject(
        Request $request,
        ActivationRequest $activationRequest,
        SubscriptionAuditLogger $audit,
    ): JsonResponse {
        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:2000'],
        ]);

        abort_unless($activationRequest->status === 'pending', 422, 'Activation request has already been reviewed.');

        $subscription = $activationRequest->business()->firstOrFail()->subscription()->firstOrFail();

        $activationRequest->update([
            'status' => 'rejected',
            'reviewed_by_admin_user_id' => Auth::guard('central')->id(),
            'review_note' => $data['review_note'],
            'reviewed_at' => now(),
        ]);

        $audit->record(
            $subscription,
            'activation_request_rejected',
            $subscription->status,
            $subscription->status,
            ['activation_request_id' => $activationRequest->id],
            'admin',
            Auth::guard('central')->id(),
        );

        return response()->json($activationRequest->fresh());
    }
}
