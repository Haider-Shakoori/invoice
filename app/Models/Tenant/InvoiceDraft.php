<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceDraft extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'number',
        'customer_id',
        'invoice_template_id',
        'created_by_user_id',
        'updated_by_user_id',
        'duplicated_from_id',
        'status',
        'locale',
        'currency',
        'issue_date',
        'due_date',
        'customer_snapshot',
        'company_snapshot',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_label',
        'tax_rate',
        'tax_amount',
        'additional_charge_label',
        'additional_charge_amount',
        'total',
        'notes',
        'terms',
        'version_no',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'customer_snapshot' => 'array',
            'company_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'additional_charge_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'version_no' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InvoiceTemplate::class, 'invoice_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function duplicatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicated_from_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(InvoiceVersion::class)->orderByDesc('version');
    }

    public function activity(): HasMany
    {
        return $this->hasMany(DocumentActivity::class)->orderByDesc('created_at');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(DocumentExport::class)->orderByDesc('created_at');
    }
}
