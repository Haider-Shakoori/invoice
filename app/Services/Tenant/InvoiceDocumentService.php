<?php

namespace App\Services\Tenant;

use App\Models\Tenant\DocumentExport;
use App\Models\Tenant\InvoiceDraft;
use App\Models\Tenant\InvoiceTemplate;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class InvoiceDocumentService
{
    public function __construct(
        private readonly InvoiceRenderDataFactory $dataFactory,
        private readonly ChromiumPdfRenderer $pdfRenderer,
        private readonly InvoiceHistoryRecorder $history,
    ) {}

    /**
     * @return array{html:string,template:InvoiceTemplate,locale:string}
     */
    public function preview(InvoiceDraft $invoice, ?int $templateId = null, ?string $locale = null): array
    {
        $template = $this->template($invoice, $templateId);
        $locale = $this->locale($locale ?? $invoice->locale);
        $previousLocale = App::getLocale();

        try {
            App::setLocale($locale);

            return [
                'html' => view(
                    'tenant.invoices.print',
                    $this->dataFactory->make($invoice, $template, $locale),
                )->render(),
                'template' => $template,
                'locale' => $locale,
            ];
        } finally {
            App::setLocale($previousLocale);
        }
    }

    public function export(
        InvoiceDraft $invoice,
        User $user,
        ?int $templateId = null,
        ?string $locale = null,
    ): DocumentExport {
        $preview = $this->preview($invoice, $templateId, $locale);
        $template = $preview['template'];
        $locale = $preview['locale'];
        $filename = sprintf(
            '%s-v%d-%s-t%02d.pdf',
            $invoice->number,
            $invoice->version_no,
            $locale,
            $template->template_number,
        );

        $rendered = $this->pdfRenderer->render($preview['html'], $filename);

        $export = DocumentExport::query()->create([
            'invoice_draft_id' => $invoice->id,
            'invoice_template_id' => $template->id,
            'exported_by_user_id' => $user->id,
            'locale' => $locale,
            'format' => 'pdf',
            'filename' => $filename,
            'storage_path' => $rendered['path'],
            'sha256' => $rendered['sha256'],
            'size_bytes' => $rendered['size_bytes'],
            'meta' => [
                'invoice_version' => $invoice->version_no,
                'template_key' => $template->key,
                'renderer' => 'chromium',
            ],
        ]);

        $this->history->activity($invoice, $user, 'pdf_exported', [
            'document_export_id' => $export->id,
            'filename' => $filename,
            'template' => $template->key,
            'locale' => $locale,
            'sha256' => $rendered['sha256'],
        ]);

        return $export;
    }

    public function absolutePath(DocumentExport $export): string
    {
        if (! Storage::disk('local')->exists($export->storage_path)) {
            throw new InvalidArgumentException('The exported PDF is no longer available in tenant storage.');
        }

        return Storage::disk('local')->path($export->storage_path);
    }

    private function template(InvoiceDraft $invoice, ?int $templateId): InvoiceTemplate
    {
        if ($templateId !== null) {
            return InvoiceTemplate::query()
                ->where('is_active', true)
                ->findOrFail($templateId);
        }

        return $invoice->template()->where('is_active', true)->first()
            ?? InvoiceTemplate::query()->where('is_active', true)->orderBy('template_number')->firstOrFail();
    }

    private function locale(string $locale): string
    {
        if (! in_array($locale, config('invoice.locales'), true)) {
            throw new InvalidArgumentException("Unsupported invoice locale [{$locale}].");
        }

        return $locale;
    }
}
