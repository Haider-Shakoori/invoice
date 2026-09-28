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

@section('title', $isEdit ? 'Edit Invoice' : 'New Invoice')
@section('heading', $isEdit ? 'Edit '.$invoice->number : 'New invoice')
@section('subheading', 'Browser totals are a preview only; the server recalculates every amount before saving.')

@section('content')
<form method="post" action="{{ $isEdit ? route('tenant.invoices.update', $invoice) : route('tenant.invoices.store') }}" id="invoice-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="card">
        <div class="grid grid-3">
            <div class="field">
                <label>Client *</label>
                <select name="customer_id" required>
                    <option value="">Choose client</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $invoice?->customer_id) === (string) $customer->id)>
                        {{ $customer->name }}{{ $customer->company_name ? ' — '.$customer->company_name : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Template</label>
                <select name="invoice_template_id">
                    @foreach($templates as $template)
                    <option value="{{ $template->id }}" @selected((string) old('invoice_template_id', $invoice?->invoice_template_id) === (string) $template->id)>
                        {{ $template->template_number }}. {{ $template->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Language</label>
                <select name="locale">
                    <option value="en" @selected(old('locale', $invoice?->locale ?? 'en') === 'en')>English</option>
                    <option value="fa" @selected(old('locale', $invoice?->locale) === 'fa')>Dari</option>
                    <option value="ps" @selected(old('locale', $invoice?->locale) === 'ps')>Pashto</option>
                </select>
            </div>
            <div class="field"><label>Issue date</label><input type="date" name="issue_date" value="{{ old('issue_date', $invoice?->issue_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div>
            <div class="field"><label>Due date</label><input type="date" name="due_date" value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d')) }}"></div>
            <div class="field"><label>Currency</label><input value="AFN — Afghani" disabled></div>
        </div>
    </div>

    <div class="card">
        <div class="actions" style="justify-content:space-between;align-items:center">
            <h2>Items</h2>
            <button class="btn secondary small" type="button" id="add-line">Add line</button>
        </div>
        <div class="table-wrap">
            <table id="lines-table">
                <thead><tr><th>Description</th><th>Qty</th><th>Unit</th><th>Unit price</th><th>Disc. %</th><th class="right">Preview</th><th></th></tr></thead>
                <tbody>
                @foreach($lineRows as $index => $line)
                <tr class="line-row">
                    <td><input name="lines[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" required></td>
                    <td><input class="qty" type="number" min="0.001" step="0.001" name="lines[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '1.000' }}" required></td>
                    <td><input name="lines[{{ $index }}][unit]" value="{{ $line['unit'] ?? '' }}"></td>
                    <td><input class="price" type="number" min="0" step="0.01" name="lines[{{ $index }}][unit_price]" value="{{ $line['unit_price'] ?? '0.00' }}" required></td>
                    <td><input class="line-discount" type="number" min="0" max="100" step="0.0001" name="lines[{{ $index }}][discount_percent]" value="{{ $line['discount_percent'] ?? '0' }}"></td>
                    <td class="right line-total">0.00</td>
                    <td><button class="btn danger small remove-line" type="button">×</button></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="field"><label>Notes</label><textarea name="notes">{{ old('notes', $invoice?->notes) }}</textarea></div>
            <div class="field"><label>Terms</label><textarea name="terms">{{ old('terms', $invoice?->terms) }}</textarea></div>
        </div>
        <div class="card">
            <h2>Totals</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>Invoice discount</label>
                    <select name="discount_type" id="discount-type">
                        <option value="">None</option>
                        <option value="percent" @selected(old('discount_type', $invoice?->discount_type) === 'percent')>Percent</option>
                        <option value="fixed" @selected(old('discount_type', $invoice?->discount_type) === 'fixed')>Fixed AFN</option>
                    </select>
                </div>
                <div class="field"><label>Discount value</label><input id="discount-value" type="number" min="0" step="0.01" name="discount_value" value="{{ old('discount_value', $invoice?->discount_value ?? 0) }}"></div>
            </div>
            <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                <div class="stat"><span class="subtle">Subtotal</span><strong id="preview-subtotal">0.00</strong></div>
                <div class="stat"><span class="subtle">Discount</span><strong id="preview-discount">0.00</strong></div>
                <div class="stat"><span class="subtle">Total AFN</span><strong id="preview-total">0.00</strong></div>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn" type="submit">{{ $isEdit ? 'Save new version' : 'Create invoice' }}</button>
        <a class="btn secondary" href="{{ route('tenant.invoices.index') }}">Cancel</a>
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
        const value = parseFloat(discountValue.value || 0);
        if (discountType.value === 'percent') discount = subtotal * Math.min(100, Math.max(0, value)) / 100;
        if (discountType.value === 'fixed') discount = Math.min(subtotal, Math.max(0, value));

        document.querySelector('#preview-subtotal').textContent = subtotal.toFixed(2);
        document.querySelector('#preview-discount').textContent = discount.toFixed(2);
        document.querySelector('#preview-total').textContent = Math.max(0, subtotal - discount).toFixed(2);
    }

    add.addEventListener('click', () => {
        const index = table.querySelectorAll('.line-row').length;
        const row = document.createElement('tr');
        row.className = 'line-row';
        row.innerHTML = `<td><input name="lines[${index}][description]" required></td>
            <td><input class="qty" type="number" min="0.001" step="0.001" name="lines[${index}][quantity]" value="1.000" required></td>
            <td><input name="lines[${index}][unit]"></td>
            <td><input class="price" type="number" min="0" step="0.01" name="lines[${index}][unit_price]" value="0.00" required></td>
            <td><input class="line-discount" type="number" min="0" max="100" step="0.0001" name="lines[${index}][discount_percent]" value="0"></td>
            <td class="right line-total">0.00</td>
            <td><button class="btn danger small remove-line" type="button">×</button></td>`;
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
    recalc();
})();
</script>
@endpush
