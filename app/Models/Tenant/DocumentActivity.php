<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'document_activity';

    protected $fillable = ['invoice_draft_id', 'user_id', 'event', 'context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceDraft::class, 'invoice_draft_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
