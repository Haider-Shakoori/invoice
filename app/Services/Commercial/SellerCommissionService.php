<?php

namespace App\Services\Commercial;

use App\Enums\PlatformInvoiceStatus;
use App\Models\Central\PlatformPayment;
use App\Models\Central\SellerCommission;

class SellerCommissionService
{
    public function createForPayment(PlatformPayment $payment): ?SellerCommission
    {
        $invoice = $payment->invoice()->firstOrFail();
        $subscription = $invoice->subscription()->with('seller')->first();

        if (! $subscription?->seller || ! $subscription->seller->is_active) {
            return null;
        }

        if ($payment->commission()->exists()) {
            return $payment->commission()->first();
        }

        $seller = $subscription->seller;
        $rate = (float) $seller->commission_rate;

        if ($rate <= 0) {
            return null;
        }

        if ($seller->commission_type === 'fixed') {
            if ($invoice->status !== PlatformInvoiceStatus::Paid) {
                return null;
            }

            $alreadyAccrued = SellerCommission::query()
                ->where('seller_id', $seller->id)
                ->whereHas('payment', fn ($query) => $query->where('platform_invoice_id', $invoice->id))
                ->exists();

            if ($alreadyAccrued) {
                return null;
            }

            $amount = (int) round($rate);
        } else {
            $amount = (int) round($payment->amount_afn * ($rate / 100));
        }

        if ($amount <= 0) {
            return null;
        }

        return SellerCommission::query()->create([
            'seller_id' => $seller->id,
            'business_id' => $payment->business_id,
            'platform_payment_id' => $payment->id,
            'status' => 'pending',
            'amount_afn' => $amount,
            'rate_snapshot' => $seller->commission_rate,
            'commission_type_snapshot' => $seller->commission_type,
            'earned_at' => $payment->received_at,
        ]);
    }
}
