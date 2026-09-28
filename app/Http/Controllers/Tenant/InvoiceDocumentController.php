<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DocumentExport;
use App\Models\Tenant\InvoiceDraft;
use App\Services\Tenant\InvoiceDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceDocumentController extends Controller
{
    public function preview(
        Request $request,
        InvoiceDraft $invoice,
        InvoiceDocumentService $documents,
    ): Response {
        $data = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:invoice_templates,id'],
            'locale' => ['nullable', 'in:en,fa,ps'],
        ]);

        $preview = $documents->preview(
            $invoice,
            isset($data['template_id']) ? (int) $data['template_id'] : null,
            $data['locale'] ?? null,
        );

        return response($preview['html'])
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', "default-src 'none'; img-src data:; style-src 'unsafe-inline'");
    }

    public function export(
        Request $request,
        InvoiceDraft $invoice,
        InvoiceDocumentService $documents,
    ): BinaryFileResponse {
        $data = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:invoice_templates,id'],
            'locale' => ['nullable', 'in:en,fa,ps'],
        ]);

        $export = $documents->export(
            $invoice,
            $request->user(),
            isset($data['template_id']) ? (int) $data['template_id'] : null,
            $data['locale'] ?? null,
        );

        return response()->download(
            $documents->absolutePath($export),
            $export->filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function download(
        DocumentExport $documentExport,
        InvoiceDocumentService $documents,
    ): BinaryFileResponse {
        return response()->download(
            $documents->absolutePath($documentExport),
            $documentExport->filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
