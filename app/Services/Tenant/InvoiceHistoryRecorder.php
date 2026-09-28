<?php

namespace App\Services\Tenant;

use App\Models\Tenant\DocumentActivity;
use App\Models\Tenant\InvoiceDraft;
use App\Models\Tenant\InvoiceVersion;
use App\Models\Tenant\User;

class InvoiceHistoryRecorder
{
    public function version(InvoiceDraft $invoice, ?User $user, ?string $reason = null): InvoiceVersion
    {
        $invoice->loadMissing('lines');

        return InvoiceVersion::query()->create([
            'invoice_draft_id' => $invoice->id,
            'version' => $invoice->version_no,
            'created_by_user_id' => $user?->id,
            'reason' => $reason,
            'snapshot' => [
                'invoice' => $invoice->only([
                    'uuid',
                    'number',
                    'customer_id',
                    'invoice_template_id',
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
                    'total',
                    'notes',
                    'terms',
                    'version_no',
                ]),
                'lines' => $invoice->lines->map(fn ($line) => $line->only([
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
                ]))->values()->all(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function activity(InvoiceDraft $invoice, ?User $user, string $event, array $context = []): DocumentActivity
    {
        return DocumentActivity::query()->create([
            'invoice_draft_id' => $invoice->id,
            'user_id' => $user?->id,
            'event' => $event,
            'context' => $context,
        ]);
    }
}
