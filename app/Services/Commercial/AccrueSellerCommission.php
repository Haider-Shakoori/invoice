<?php

namespace App\Services\Commercial;

use App\Enums\PlatformInvoiceStatus;
use App\Models\Central\PlatformInvoice;
use App\Models\Central\PlatformPayment;
use App\Models\Central\SellerCommission;

class AccrueSellerCommission
{
    public function handle(PlatformPayment $payment, PlatformInvoice $invoice): ?SellerCommission
    {
        $subscription = $invoice->subscription()->with('seller')->first();

        if (! $subscription?->seller || ! $subscription->seller->is_active) {
            return null;
        }

        $seller = $subscription->seller;
        $rate = (float) $seller->commission_rate;

        if ($rate <= 0) {
            return null;
        }

        if ($seller->commission_type === 'percent') {
            $amount = (int) round($payment->amount_afn * ($rate / 100));
        } elseif ($seller->commission_type === 'fixed') {
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
            return null;
        }

        if ($amount <= 0) {
            return null;
        }

        return SellerCommission::query()->firstOrCreate(
            ['platform_payment_id' => $payment->id],
            [
                'seller_id' => $seller->id,
                'business_id' => $invoice->business_id,
                'status' => 'pending',
                'amount_afn' => $amount,
                'rate_snapshot' => $seller->commission_rate,
                'commission_type_snapshot' => $seller->commission_type,
                'earned_at' => $payment->received_at,
            ],
        );
    }
}
