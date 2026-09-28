@extends('tenant.layouts.workspace')

@section('title', __('ui.invoices.title'))
@section('heading', __('ui.invoices.title'))
@section('subheading', __('ui.invoices.subtitle'))

@section('actions')
@if(auth()->user()->hasTenantPermission('drafts.manage'))
<a class="btn" href="{{ route('tenant.invoices.create') }}">{{ __('ui.invoices.new') }}</a>
@endif
@endsection

@section('content')
<div class="card">
    <form method="get" class="actions" style="margin-bottom:14px">
        <input name="q" value="{{ $search }}" placeholder="{{ __('ui.invoices.search_placeholder') }}" style="max-width:390px">
        <button class="btn secondary" type="submit">{{ __('ui.common.search') }}</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>{{ __('ui.invoices.invoice') }}</th><th>{{ __('ui.invoices.client') }}</th><th>{{ __('ui.invoices.date') }}</th><th>{{ __('ui.common.status') }}</th><th class="right">{{ __('ui.invoices.total') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('tenant.invoices.show', $invoice) }}"><strong class="numeric">{{ $invoice->number }}</strong></a><div class="subtle">{{ __('ui.invoices.version') }} {{ $invoice->version_no }}</div></td>
                    <td>{{ $invoice->customer_snapshot['name'] ?? $invoice->customer?->name }}<div class="subtle">{{ $invoice->customer_snapshot['company_name'] ?? null }}</div></td>
                    <td class="numeric">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                    <td><span class="badge">{{ ucfirst($invoice->status) }}</span></td>
                    <td class="right nowrap numeric">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
                    <td class="right"><a class="btn secondary small" href="{{ route('tenant.invoices.show', $invoice) }}">{{ __('ui.common.open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">{{ __('ui.invoices.no_invoices') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
</div>
@endsection
