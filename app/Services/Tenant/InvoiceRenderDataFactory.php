<?php

namespace App\Services\Tenant;

use App\Models\Tenant\InvoiceDraft;
use App\Models\Tenant\InvoiceTemplate;
use Illuminate\Support\Facades\Storage;

class InvoiceRenderDataFactory
{
    public function __construct(private readonly InvoiceTemplateCatalog $catalog)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function make(
        InvoiceDraft $invoice,
        InvoiceTemplate $template,
        string $locale,
    ): array {
        $invoice->loadMissing('lines');

        return [
            'invoice' => $invoice,
            'template' => $this->catalog->definition($template),
            'locale' => $locale,
            'rtl' => in_array($locale, ['fa', 'ps'], true),
            'company' => $invoice->company_snapshot,
            'customer' => $invoice->customer_snapshot,
            'logoDataUri' => $this->dataUri($invoice->company_snapshot['logo_path'] ?? null),
            'signatureDataUri' => $this->dataUri($invoice->company_snapshot['signature_path'] ?? null),
            'stampDataUri' => $this->dataUri($invoice->company_snapshot['stamp_path'] ?? null),
        ];
    }

    private function dataUri(?string $path): ?string
    {
        if ($path === null || $path === '' || Storage::disk('local')->exists($path) === false) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        $contents = Storage::disk('local')->get($path);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
