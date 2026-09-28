<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Business extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'display_name',
        'owner_name',
        'owner_email',
        'owner_phone',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'provisioning_status',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function platformInvoices(): HasMany
    {
        return $this->hasMany(PlatformInvoice::class);
    }

    public function platformPayments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }

    public function activationRequests(): HasMany
    {
        return $this->hasMany(ActivationRequest::class);
    }
}
