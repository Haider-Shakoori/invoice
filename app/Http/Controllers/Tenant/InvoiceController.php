<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\InvoiceDraft;
use App\Models\Tenant\InvoiceTemplate;
use App\Services\Tenant\InvoiceDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $invoices = InvoiceDraft::query()
            ->with('customer')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%"));
                });
            })
            ->latest('issue_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('tenant.invoices.index', compact('invoices', 'search'));
    }

    public function create(): View
    {
        return view('tenant.invoices.form', [
            'invoice' => null,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'templates' => InvoiceTemplate::query()->where('is_active', true)->orderBy('template_number')->get(),
        ]);
    }

    public function store(Request $request, InvoiceDraftService $service): RedirectResponse
    {
        try {
            $invoice = $service->create($this->validated($request), $request->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['invoice' => $exception->getMessage()]);
        }

        return redirect()->route('tenant.invoices.show', $invoice)->with('status', 'Invoice draft created.');
    }

    public function show(InvoiceDraft $invoice): View
    {
        $invoice->load(['customer', 'template', 'lines', 'versions.createdBy', 'activity.user', 'exports.template']);

        return view('tenant.invoices.show', compact('invoice'));
    }

    public function edit(InvoiceDraft $invoice): View
    {
        $invoice->load('lines');

        return view('tenant.invoices.form', [
            'invoice' => $invoice,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'templates' => InvoiceTemplate::query()->where('is_active', true)->orderBy('template_number')->get(),
        ]);
    }

    public function update(Request $request, InvoiceDraft $invoice, InvoiceDraftService $service): RedirectResponse
    {
        try {
            $invoice = $service->update($invoice, $this->validated($request, false), $request->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['invoice' => $exception->getMessage()]);
        }

        return redirect()->route('tenant.invoices.show', $invoice)->with('status', 'Invoice draft updated.');
    }

    public function duplicate(Request $request, InvoiceDraft $invoice, InvoiceDraftService $service): RedirectResponse
    {
        $copy = $service->duplicate($invoice, $request->user());

        return redirect()->route('tenant.invoices.edit', $copy)->with('status', 'Invoice duplicated with a new number.');
    }

    public function destroy(Request $request, InvoiceDraft $invoice, InvoiceDraftService $service): RedirectResponse
    {
        $service->delete($invoice, $request->user());

        return redirect()->route('tenant.invoices.index')->with('status', 'Invoice draft deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $linesRequired = true): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_template_id' => ['nullable', 'integer', 'exists:invoice_templates,id'],
            'locale' => ['required', Rule::in(config('invoice.locales'))],
            'currency' => ['required', Rule::in(['AFN', 'USD'])],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'discount_type' => ['nullable', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'additional_charge_label' => ['nullable', 'string', 'max:100'],
            'additional_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_label' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'lines' => [$linesRequired ? 'required' : 'sometimes', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit' => ['nullable', 'string', 'max:50'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
        ]);
    }
}
