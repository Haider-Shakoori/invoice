@extends('tenant.layouts.workspace')

@php
$isEdit = $invoice !== null;
$lineRows = old('lines');
if ($lineRows === null) {
    $lineRows = $isEdit
        ? $invoice->lines->map(fn($line) => [
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'unit_price' => $line->unit_price,
            'discount_percent' => $line->discount_percent,
        ])->all()
        : [['description' => '', 'quantity' => '1.000', 'unit' => '', 'unit_price' => '0.00', 'discount_percent' => '0']];
}
@endphp

@section('title', $isEdit ? __('ui.invoices.edit_title', ['number' => $invoice->number]) : __('ui.invoices.new_title'))
@section('heading', $isEdit ? __('ui.invoices.edit_title', ['number' => $invoice->number]) : __('ui.invoices.new_title'))
@section('subheading', __('ui.invoices.editor_subtitle'))

@section('actions')
@if($isEdit)
<a class="btn secondary" href="{{ route('tenant.templates.index', ['invoice' => $invoice->id]) }}">{{ __('ui.nav.templates') }}</a>
<a class="btn secondary" target="_blank" href="{{ route('tenant.invoices.preview', ['invoice' => $invoice, 'template_id' => old('invoice_template_id', $invoice->invoice_template_id), 'locale' => old('locale', $invoice->locale)]) }}">{{ __('ui.invoices.preview_document') }}</a>
@endif
@endsection

@section('content')
<form method="post" action="{{ $isEdit ? route('tenant.invoices.update', $invoice) : route('tenant.invoices.store') }}" id="invoice-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="card">
        <div class="grid grid-3">
            <div class="field">
                <label>{{ __('ui.invoices.client') }} *</label>
                <select name="customer_id" required>
                    <option value="">{{ __('ui.invoices.choose_client') }}</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $invoice?->customer_id) === (string) $customer->id)>
                        {{ $customer->name }}{{ $customer->company_name ? ' — '.$customer->company_name : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>{{ __('ui.invoices.template') }}</label>
                <select name="invoice_template_id">
                    @foreach($templates as $template)
                    <option value="{{ $template->id }}" @selected((string) old('invoice_template_id', $invoice?->invoice_template_id) === (string) $template->id)>
                        {{ str_pad((string) $template->template_number, 2, '0', STR_PAD_LEFT) }}. {{ $template->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>{{ __('ui.invoices.language') }}</label>
                <select name="locale">
                    @foreach(config('invoice.locales') as $locale)
                        <option value="{{ $locale }}" @selected(old('locale', $invoice?->locale ?? app()->getLocale()) === $locale)>{{ __('ui.languages.'.$locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>{{ __('ui.invoices.issue_date') }}</label><input type="date" name="issue_date" value="{{ old('issue_date', $invoice?->issue_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div>
            <div class="field"><label>{{ __('ui.invoices.due_date') }}</label><input type="date" name="due_date" value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d')) }}"></div>
            <div class="field">
                <label>{{ __('ui.invoices.currency') }}</label>
                <select name="currency" id="currency">
                    <option value="AFN" @selected(old('currency', $invoice?->currency ?? 'AFN') === 'AFN')>AFN — Afghani</option>
                    <option value="USD" @selected(old('currency', $invoice?->currency) === 'USD')>USD — US Dollar</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="actions" style="justify-content:space-between;align-items:center">
            <h2>{{ __('ui.invoices.items') }}</h2>
            <button class="btn secondary small" type="button" id="add-line">{{ __('ui.invoices.add_line') }}</button>
        </div>
        <div class="table-wrap">
            <table id="lines-table">
                <thead><tr><th>{{ __('ui.invoices.description') }}</th><th>{{ __('ui.invoices.qty') }}</th><th>{{ __('ui.invoices.unit') }}</th><th>{{ __('ui.invoices.unit_price') }}</th><th>{{ __('ui.invoices.discount_percent') }}</th><th class="right">{{ __('ui.invoices.preview') }}</th><th></th></tr></thead>
                <tbody>
                @foreach($lineRows as $index => $line)
                <tr class="line-row">
                    <td><input name="lines[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" required></td>
                    <td><input class="qty numeric" type="number" min="0.001" step="0.001" name="lines[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '1.000' }}" required></td>
                    <td><input name="lines[{{ $index }}][unit]" value="{{ $line['unit'] ?? '' }}"></td>
                    <td><input class="price numeric" type="number" min="0" step="0.01" name="lines[{{ $index }}][unit_price]" value="{{ $line['unit_price'] ?? '0.00' }}" required></td>
                    <td><input class="line-discount numeric" type="number" min="0" max="100" step="0.0001" name="lines[{{ $index }}][discount_percent]" value="{{ $line['discount_percent'] ?? '0' }}"></td>
                    <td class="right line-total numeric">0.00</td>
                    <td><button class="btn danger small remove-line" type="button">×</button></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="field"><label>{{ __('ui.invoices.notes') }}</label><textarea name="notes">{{ old('notes', $invoice?->notes) }}</textarea></div>
            <div class="field"><label>{{ __('ui.invoices.terms') }}</label><textarea name="terms">{{ old('terms', $invoice?->terms) }}</textarea></div>
        </div>
        <div class="card">
            <h2>{{ __('ui.invoices.totals') }}</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>{{ __('ui.invoices.invoice_discount') }}</label>
                    <select name="discount_type" id="discount-type">
                        <option value="">{{ __('ui.invoices.none') }}</option>
                        <option value="percent" @selected(old('discount_type', $invoice?->discount_type) === 'percent')>{{ __('ui.invoices.percent') }}</option>
                        <option value="fixed" @selected(old('discount_type', $invoice?->discount_type) === 'fixed')>{{ __('ui.invoices.fixed') }}</option>
                    </select>
                </div>
                <div class="field"><label>{{ __('ui.invoices.discount_value') }}</label><input class="numeric" id="discount-value" type="number" min="0" step="0.0001" name="discount_value" value="{{ old('discount_value', $invoice?->discount_value ?? 0) }}"></div>
                <div class="field"><label>{{ __('ui.invoices.additional_charge_label') }}</label><input name="additional_charge_label" value="{{ old('additional_charge_label', $invoice?->additional_charge_label) }}"></div>
                <div class="field"><label>{{ __('ui.invoices.additional_charge_amount') }}</label><input class="numeric" id="additional-charge" type="number" min="0" step="0.01" name="additional_charge_amount" value="{{ old('additional_charge_amount', $invoice?->additional_charge_amount ?? 0) }}"></div>
                <div class="field"><label>{{ __('ui.invoices.tax_label') }}</label><input name="tax_label" value="{{ old('tax_label', $invoice?->tax_label) }}"></div>
                <div class="field"><label>{{ __('ui.invoices.tax_rate') }}</label><input class="numeric" id="tax-rate" type="number" min="0" max="100" step="0.0001" name="tax_rate" value="{{ old('tax_rate', $invoice?->tax_rate ?? 0) }}"></div>
            </div>
            <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                <div class="stat"><span class="subtle">{{ __('ui.invoices.subtotal') }}</span><strong id="preview-subtotal" class="numeric">0.00</strong></div>
                <div class="stat"><span class="subtle">{{ __('ui.invoices.discount') }}</span><strong id="preview-discount" class="numeric">0.00</strong></div>
                <div class="stat"><span class="subtle">{{ __('ui.invoices.additional_charge') }}</span><strong id="preview-additional" class="numeric">0.00</strong></div>
                <div class="stat"><span class="subtle">{{ __('ui.invoices.tax') }}</span><strong id="preview-tax" class="numeric">0.00</strong></div>
                <div class="stat"><span class="subtle">{{ __('ui.invoices.total') }}</span><strong id="preview-total" class="numeric">0.00</strong></div>
                <div class="stat"><span class="subtle">{{ __('ui.invoices.currency') }}</span><strong id="preview-currency">AFN</strong></div>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn" type="submit">{{ $isEdit ? __('ui.invoices.save_version') : __('ui.invoices.create') }}</button>
        <a class="btn secondary" href="{{ route('tenant.invoices.index') }}">{{ __('ui.common.cancel') }}</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const table = document.querySelector('#lines-table tbody');
    const add = document.querySelector('#add-line');
    const discountType = document.querySelector('#discount-type');
    const discountValue = document.querySelector('#discount-value');
    const additionalCharge = document.querySelector('#additional-charge');
    const taxRate = document.querySelector('#tax-rate');
    const currency = document.querySelector('#currency');

    function renumber() {
        [...table.querySelectorAll('.line-row')].forEach((row, index) => {
            row.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/lines\[\d+\]/, 'lines[' + index + ']');
            });
        });
    }

    function recalc() {
        let subtotal = 0;

        table.querySelectorAll('.line-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty').value || 0);
            const price = parseFloat(row.querySelector('.price').value || 0);
            const discount = Math.min(100, Math.max(0, parseFloat(row.querySelector('.line-discount').value || 0)));
            const total = qty * price * (1 - discount / 100);

            row.querySelector('.line-total').textContent = total.toFixed(2);
            subtotal += total;
        });

        let discount = 0;
        const discountInput = parseFloat(discountValue.value || 0);

        if (discountType.value === 'percent') discount = subtotal * Math.min(100, Math.max(0, discountInput)) / 100;
        if (discountType.value === 'fixed') discount = Math.min(subtotal, Math.max(0, discountInput));

        const additional = Math.max(0, parseFloat(additionalCharge.value || 0));
        const taxPercent = Math.min(100, Math.max(0, parseFloat(taxRate.value || 0)));
        const taxBase = Math.max(0, subtotal - discount + additional);
        const tax = taxBase * taxPercent / 100;
        const total = taxBase + tax;

        document.querySelector('#preview-subtotal').textContent = subtotal.toFixed(2);
        document.querySelector('#preview-discount').textContent = discount.toFixed(2);
        document.querySelector('#preview-additional').textContent = additional.toFixed(2);
        document.querySelector('#preview-tax').textContent = tax.toFixed(2);
        document.querySelector('#preview-total').textContent = total.toFixed(2);
        document.querySelector('#preview-currency').textContent = currency.value;
    }

    add.addEventListener('click', () => {
        const index = table.querySelectorAll('.line-row').length;
        const row = document.createElement('tr');
        row.className = 'line-row';
        row.innerHTML =
            '<td><input name="lines[' + index + '][description]" required></td>' +
            '<td><input class="qty numeric" type="number" min="0.001" step="0.001" name="lines[' + index + '][quantity]" value="1.000" required></td>' +
            '<td><input name="lines[' + index + '][unit]"></td>' +
            '<td><input class="price numeric" type="number" min="0" step="0.01" name="lines[' + index + '][unit_price]" value="0.00" required></td>' +
            '<td><input class="line-discount numeric" type="number" min="0" max="100" step="0.0001" name="lines[' + index + '][discount_percent]" value="0"></td>' +
            '<td class="right line-total numeric">0.00</td>' +
            '<td><button class="btn danger small remove-line" type="button">×</button></td>';

        table.appendChild(row);
        recalc();
    });

    table.addEventListener('click', event => {
        if (!event.target.classList.contains('remove-line')) return;
        if (table.querySelectorAll('.line-row').length === 1) return;

        event.target.closest('.line-row').remove();
        renumber();
        recalc();
    });

    document.querySelector('#invoice-form').addEventListener('input', recalc);
    document.querySelector('#invoice-form').addEventListener('change', recalc);
    recalc();
})();
</script>
@endpush
