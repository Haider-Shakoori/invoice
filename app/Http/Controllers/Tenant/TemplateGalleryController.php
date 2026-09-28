<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\InvoiceDraft;
use App\Models\Tenant\InvoiceTemplate;
use App\Services\Tenant\InvoiceTemplateCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateGalleryController extends Controller
{
    public function index(Request $request, InvoiceTemplateCatalog $catalog): View
    {
        $invoice = null;

        if ($request->filled('invoice')) {
            $invoice = InvoiceDraft::query()->findOrFail((int) $request->query('invoice'));
        }

        $records = InvoiceTemplate::query()
            ->where('is_active', true)
            ->orderBy('template_number')
            ->get()
            ->keyBy('key');

        $templates = $catalog->all()->map(function (array $definition) use ($records): array {
            return [
                ...$definition,
                'id' => $records->get($definition['key'])?->id,
            ];
        });

        return view('tenant.templates.index', compact('templates', 'invoice'));
    }
}
