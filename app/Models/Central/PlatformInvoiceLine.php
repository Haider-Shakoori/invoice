<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PlatformInvoiceLine extends Model
{
    use CentralConnection;

    protected $fillable = [
        'platform_invoice_id',
        'position',
        'description',
        'quantity',
        'unit_price_afn',
        'amount_afn',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'quantity' => 'decimal:3',
            'unit_price_afn' => 'integer',
            'amount_afn' => 'integer',
            'meta' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }
}
