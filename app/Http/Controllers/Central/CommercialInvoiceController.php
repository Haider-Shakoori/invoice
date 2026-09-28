<?php

namespace App\Http\Controllers\Central;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Central\Business;
use App\Models\Central\PlatformInvoice;
use App\Services\Commercial\IssueActivationInvoice;
use App\Services\Commercial\IssueRenewalInvoice;
use App\Services\Commercial\RecordPlatformPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CommercialInvoiceController extends Controller
{
    public function activation(Business $business, IssueActivationInvoice $issue): JsonResponse
    {
        $subscription = $business->subscription()->firstOrFail();

        return response()->json($issue->handle($subscription), 201);
    }

    public function renewal(Business $business, IssueRenewalInvoice $issue): JsonResponse
    {
        $subscription = $business->subscription()->firstOrFail();

        return response()->json($issue->handle($subscription), 201);
    }

    public function recordPayment(
        Request $request,
        PlatformInvoice $platformInvoice,
        RecordPlatformPayment $recordPayment,
    ): JsonResponse {
        $data = $request->validate([
            'amount_afn' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'received_at' => ['nullable', 'date'],
        ]);

        $payment = $recordPayment->handle(
            $platformInvoice,
            (int) $data['amount_afn'],
            PaymentMethod::from($data['method']),
            isset($data['received_at']) ? Carbon::parse($data['received_at']) : null,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
            Auth::guard('central')->user(),
        );

        return response()->json([
            'payment' => $payment,
            'invoice' => $platformInvoice->fresh(['lines', 'payments']),
            'subscription' => $platformInvoice->subscription()->first(),
        ], 201);
    }
}
