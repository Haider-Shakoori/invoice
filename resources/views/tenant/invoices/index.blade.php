@extends('tenant.layouts.workspace')

@section('title', 'Invoices')
@section('heading', 'Invoices')
@section('subheading', 'Your invoice workspace. No analytics dashboard between you and the work.')

@section('actions')
@if(auth()->user()->hasTenantPermission('drafts.manage'))
<a class="btn" href="{{ route('tenant.invoices.create') }}">New invoice</a>
@endif
@endsection

@section('content')
<div class="card">
    <form method="get" class="actions" style="margin-bottom:14px">
        <input name="q" value="{{ $search }}" placeholder="Search invoice number or client…" style="max-width:390px">
        <button class="btn secondary" type="submit">Search</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Invoice</th><th>Client</th><th>Date</th><th>Status</th><th class="right">Total</th><th></th></tr></thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('tenant.invoices.show', $invoice) }}"><strong>{{ $invoice->number }}</strong></a><div class="subtle">v{{ $invoice->version_no }}</div></td>
                    <td>{{ $invoice->customer_snapshot['name'] ?? $invoice->customer?->name }}<div class="subtle">{{ $invoice->customer_snapshot['company_name'] ?? null }}</div></td>
                    <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                    <td><span class="badge">{{ ucfirst($invoice->status) }}</span></td>
                    <td class="right nowrap">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
                    <td class="right"><a class="btn secondary small" href="{{ route('tenant.invoices.show', $invoice) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No invoices yet. Create your first invoice draft.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
</div>
@endsection
