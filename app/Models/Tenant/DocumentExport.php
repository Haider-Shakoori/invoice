<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExport extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'invoice_draft_id',
        'invoice_template_id',
        'exported_by_user_id',
        'locale',
        'format',
        'filename',
        'storage_path',
        'sha256',
        'size_bytes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'meta' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceDraft::class, 'invoice_draft_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InvoiceTemplate::class, 'invoice_template_id');
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by_user_id');
    }
}
