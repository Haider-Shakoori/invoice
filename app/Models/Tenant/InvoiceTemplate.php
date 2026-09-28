<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceTemplate extends Model
{
    protected $fillable = ['template_number', 'key', 'name', 'version', 'is_active', 'meta'];

    protected function casts(): array
    {
        return [
            'template_number' => 'integer',
            'version' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceDraft::class);
    }
}
