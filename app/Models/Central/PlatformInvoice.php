<?php

namespace App\Models\Central;

use App\Enums\PlatformInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PlatformInvoice extends Model
{
    use CentralConnection;

    protected $fillable = [
        'number',
        'business_id',
        'subscription_id',
        'type',
        'status',
        'currency',
        'subtotal_afn',
        'discount_afn',
        'total_afn',
        'issued_at',
        'due_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlatformInvoiceStatus::class,
            'subtotal_afn' => 'integer',
            'discount_afn' => 'integer',
            'total_afn' => 'integer',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PlatformInvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }
}
