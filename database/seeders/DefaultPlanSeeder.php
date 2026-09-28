<?php

namespace Database\Seeders;

use App\Models\Central\Plan;
use Illuminate\Database\Seeder;

class DefaultPlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
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
