<?php

namespace App\Services\Commercial;

use App\Models\Central\Plan;

class EnsureDefaultPlan
{
    public function handle(): Plan
    {
        return Plan::query()->firstOrCreate(
            ['code' => 'standard'],
            [
                'name' => 'Invoice Drafts Annual',
                'trial_days' => (int) config('invoice.trial_days', 7),
                'term_months' => 12,
                'setup_fee_afn' => (int) config('invoice.pricing.activation_setup_afn', 2000),
                'first_term_fee_afn' => (int) config('invoice.pricing.first_year_afn', 3000),
                'renewal_fee_afn' => (int) config('invoice.pricing.renewal_afn', 3000),
                'is_active' => true,
                'meta' => ['currency' => 'AFN'],
            ],
        );
    }
}
