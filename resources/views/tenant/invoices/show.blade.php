@extends('tenant.layouts.workspace')

@section('title', $invoice->number)
@section('heading', $invoice->number)
@section('subheading', __('ui.invoices.version').' '.$invoice->version_no.' · '.$invoice->issue_date->format('Y-m-d'))

@section('actions')
<a class="btn secondary" href="{{ route('tenant.templates.index', ['invoice' => $invoice->id]) }}">{{ __('ui.nav.templates') }}</a>
<a class="btn secondary" target="_blank" href="{{ route('tenant.invoices.preview', ['invoice' => $invoice, 'template_id' => $invoice->invoice_template_id, 'locale' => $invoice->locale]) }}">{{ __('ui.invoices.preview_document') }}</a>
@if(auth()->user()->hasTenantPermission('pdf.export'))
<form method="post" action="{{ route('tenant.invoices.export', $invoice) }}">
    @csrf
    <input type="hidden" name="template_id" value="{{ $invoice->invoice_template_id }}">
    <input type="hidden" name="locale" value="{{ $invoice->locale }}">
    <button class="btn" type="submit">{{ __('ui.invoices.export_pdf') }}</button>
</form>
@endif
@if(auth()->user()->hasTenantPermission('drafts.manage'))
<a class="btn" href="{{ route('tenant.invoices.edit', $invoice) }}">{{ __('ui.common.edit') }}</a>
<form method="post" action="{{ route('tenant.invoices.duplicate', $invoice) }}">@csrf<button class="btn secondary" type="submit">{{ __('ui.invoices.duplicate') }}</button></form>
@endif
@endsection

@section('content')
<div class="grid grid-2">
    <div class="card">
        <h2>{{ __('ui.invoices.from') }}</h2>
        <strong>{{ $invoice->company_snapshot['display_name'] ?? '' }}</strong>
        <div>{{ $invoice->company_snapshot['address'] ?? '' }}</div>
        <div>{{ $invoice->company_snapshot['city_province'] ?? '' }}</div>
        <div class="numeric">{{ $invoice->company_snapshot['phone'] ?? '' }}</div>
    </div>
    <div class="card">
        <h2>{{ __('ui.invoices.bill_to') }}</h2>
        <strong>{{ $invoice->customer_snapshot['name'] ?? '' }}</strong>
        <div>{{ $invoice->customer_snapshot['company_name'] ?? '' }}</div>
        <div>{{ $invoice->customer_snapshot['address'] ?? '' }}</div>
        <div class="numeric">{{ $invoice->customer_snapshot['phone'] ?? '' }}</div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>{{ __('ui.invoices.description') }}</th><th class="right">{{ __('ui.invoices.qty') }}</th><th>{{ __('ui.invoices.unit') }}</th><th class="right">{{ __('ui.invoices.unit_price') }}</th><th class="right">{{ __('ui.invoices.discount_percent') }}</th><th class="right">{{ __('ui.invoices.total') }}</th></tr></thead>
            <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td class="numeric">{{ $line->position }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="right numeric">{{ $line->quantity }}</td>
                    <td>{{ $line->unit }}</td>
                    <td class="right numeric">{{ $line->unit_price }}</td>
                    <td class="right numeric">{{ $line->discount_percent }}%</td>
                    <td class="right numeric">{{ $line->line_total }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div style="max-width:390px;margin-inline-start:auto;margin-top:18px">
        <div class="grid grid-2"><span>{{ __('ui.invoices.subtotal') }}</span><strong class="right numeric">{{ $invoice->subtotal }} {{ $invoice->currency }}</strong></div>
        @if((float) $invoice->discount_amount > 0)<div class="grid grid-2"><span>{{ __('ui.invoices.discount') }}</span><strong class="right numeric">{{ $invoice->discount_amount }} {{ $invoice->currency }}</strong></div>@endif
        @if((float) $invoice->additional_charge_amount > 0)<div class="grid grid-2"><span>{{ $invoice->additional_charge_label ?: __('ui.invoices.additional_charge') }}</span><strong class="right numeric">{{ $invoice->additional_charge_amount }} {{ $invoice->currency }}</strong></div>@endif
        @if((float) $invoice->tax_amount > 0)<div class="grid grid-2"><span>{{ $invoice->tax_label ?: __('ui.invoices.tax') }} <span class="numeric">({{ $invoice->tax_rate }}%)</span></span><strong class="right numeric">{{ $invoice->tax_amount }} {{ $invoice->currency }}</strong></div>@endif
        <hr>
        <div class="grid grid-2"><span>{{ __('ui.invoices.total') }}</span><strong class="right numeric" style="font-size:22px">{{ $invoice->total }} {{ $invoice->currency }}</strong></div>
    </div>
</div>

@if($invoice->notes || $invoice->terms)
<div class="grid grid-2">
    <div class="card"><h3>{{ __('ui.invoices.notes') }}</h3><div>{!! nl2br(e($invoice->notes)) !!}</div></div>
    <div class="card"><h3>{{ __('ui.invoices.terms') }}</h3><div>{!! nl2br(e($invoice->terms)) !!}</div></div>
</div>
@endif

@if(auth()->user()->hasTenantPermission('pdf.export'))
<div class="card">
    <h2>{{ __('ui.invoices.prior_exports') }}</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>{{ __('ui.invoices.date') }}</th><th>{{ __('ui.invoices.template') }}</th><th>{{ __('ui.invoices.language') }}</th><th>{{ __('ui.invoices.version') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($invoice->exports as $export)
                <tr>
                    <td class="numeric">{{ $export->created_at }}</td>
                    <td>{{ $export->template?->name }}</td>
                    <td>{{ __('ui.languages.'.$export->locale) }}</td>
                    <td class="numeric">{{ $export->meta['invoice_version'] ?? '-' }}</td>
                    <td class="right"><a class="btn secondary small" href="{{ route('tenant.exports.download', $export) }}">{{ __('ui.invoices.download') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">—</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="grid grid-2">
    <div class="card">
        <h2>{{ __('ui.invoices.version_history') }}</h2>
        @forelse($invoice->versions as $version)
            <div style="padding:10px 0;border-bottom:1px solid #e5e7eb">
                <strong>{{ __('ui.invoices.version') }} {{ $version->version }}</strong>
                <div class="subtle">{{ $version->reason ?: __('ui.common.saved') }} · {{ $version->created_at }} · {{ $version->createdBy?->name ?: __('ui.common.system') }}</div>
            </div>
        @empty
            <div class="subtle">{{ __('ui.invoices.no_versions') }}</div>
        @endforelse
    </div>
    <div class="card">
        <h2>{{ __('ui.invoices.activity') }}</h2>
        @forelse($invoice->activity as $event)
            <div style="padding:10px 0;border-bottom:1px solid #e5e7eb">
                <strong>{{ str_replace('_', ' ', ucfirst($event->event)) }}</strong>
                <div class="subtle">{{ $event->created_at }} · {{ $event->user?->name ?: __('ui.common.system') }}</div>
            </div>
        @empty
            <div class="subtle">{{ __('ui.invoices.no_activity') }}</div>
        @endforelse
    </div>
</div>

@if(auth()->user()->hasTenantPermission('drafts.delete'))
<div class="card">
    <form method="post" action="{{ route('tenant.invoices.destroy', $invoice) }}" onsubmit="return confirm(@js(__('ui.invoices.delete_confirm')))">
        @csrf @method('DELETE')
        <button class="btn danger" type="submit">{{ __('ui.invoices.delete_draft') }}</button>
    </form>
</div>
@endif
@endsection
