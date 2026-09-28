<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Plan extends Model
{
    use CentralConnection;

    protected $fillable = [
        'code',
        'name',
        'trial_days',
        'term_months',
        'setup_fee_afn',
        'first_term_fee_afn',
        'renewal_fee_afn',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'trial_days' => 'integer',
            'term_months' => 'integer',
            'setup_fee_afn' => 'integer',
            'first_term_fee_afn' => 'integer',
            'renewal_fee_afn' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
