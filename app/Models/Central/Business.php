<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Business extends Model
{
    protected $fillable = ['tenant_id', 'display_name', 'owner_name', 'owner_email', 'owner_phone', 'status', 'trial_ends_at', 'subscription_ends_at', 'provisioning_status'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'subscription_ends_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
