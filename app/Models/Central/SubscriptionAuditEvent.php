<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SubscriptionAuditEvent extends Model
{
    use CentralConnection;

    protected $fillable = [
        'subscription_id',
        'business_id',
        'actor_type',
        'actor_id',
        'event',
        'previous_status',
        'new_status',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'actor_id' => 'integer',
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
