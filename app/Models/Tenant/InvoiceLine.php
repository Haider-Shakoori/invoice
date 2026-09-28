<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_draft_id',
        'position',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_percent',
        'line_subtotal',
        'line_discount_amount',
        'line_total',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:4',
            'line_subtotal' => 'decimal:2',
            'line_discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceDraft::class, 'invoice_draft_id');
    }
}
