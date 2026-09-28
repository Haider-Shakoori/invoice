@extends('tenant.layouts.workspace')

@section('title', $invoice->number)
@section('heading', $invoice->number)
@section('subheading', 'Version '.$invoice->version_no.' · '.$invoice->issue_date->format('Y-m-d'))

@section('actions')
@if(auth()->user()->hasTenantPermission('drafts.manage'))
<a class="btn" href="{{ route('tenant.invoices.edit', $invoice) }}">Edit</a>
<form method="post" action="{{ route('tenant.invoices.duplicate', $invoice) }}">@csrf<button class="btn secondary" type="submit">Duplicate</button></form>
@endif
@endsection

@section('content')
<div class="grid grid-2">
    <div class="card">
        <h2>From</h2>
        <strong>{{ $invoice->company_snapshot['display_name'] ?? '' }}</strong>
        <div>{{ $invoice->company_snapshot['address'] ?? '' }}</div>
        <div>{{ $invoice->company_snapshot['city_province'] ?? '' }}</div>
        <div>{{ $invoice->company_snapshot['phone'] ?? '' }}</div>
    </div>
    <div class="card">
        <h2>Bill to</h2>
        <strong>{{ $invoice->customer_snapshot['name'] ?? '' }}</strong>
        <div>{{ $invoice->customer_snapshot['company_name'] ?? '' }}</div>
        <div>{{ $invoice->customer_snapshot['address'] ?? '' }}</div>
        <div>{{ $invoice->customer_snapshot['phone'] ?? '' }}</div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Description</th><th class="right">Qty</th><th>Unit</th><th class="right">Unit price</th><th class="right">Disc.</th><th class="right">Total</th></tr></thead>
            <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td>{{ $line->position }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td>{{ $line->unit }}</td>
                    <td class="right">{{ $line->unit_price }}</td>
                    <td class="right">{{ $line->discount_percent }}%</td>
                    <td class="right">{{ $line->line_total }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div style="max-width:360px;margin-left:auto;margin-top:18px">
        <div class="grid grid-2"><span>Subtotal</span><strong class="right">{{ $invoice->subtotal }} AFN</strong></div>
        <div class="grid grid-2"><span>Discount</span><strong class="right">{{ $invoice->discount_amount }} AFN</strong></div>
        <hr>
        <div class="grid grid-2"><span>Total</span><strong class="right" style="font-size:22px">{{ $invoice->total }} AFN</strong></div>
    </div>
</div>

@if($invoice->notes || $invoice->terms)
<div class="grid grid-2">
    <div class="card"><h3>Notes</h3><div>{!! nl2br(e($invoice->notes)) !!}</div></div>
    <div class="card"><h3>Terms</h3><div>{!! nl2br(e($invoice->terms)) !!}</div></div>
</div>
@endif

<div class="grid grid-2">
    <div class="card">
        <h2>Version history</h2>
        @forelse($invoice->versions as $version)
            <div style="padding:10px 0;border-bottom:1px solid #e5e7eb">
                <strong>Version {{ $version->version }}</strong>
                <div class="subtle">{{ $version->reason ?: 'saved' }} · {{ $version->created_at }} · {{ $version->createdBy?->name ?: 'System' }}</div>
            </div>
        @empty
            <div class="subtle">No versions recorded.</div>
        @endforelse
    </div>
    <div class="card">
        <h2>Activity</h2>
        @forelse($invoice->activity as $event)
            <div style="padding:10px 0;border-bottom:1px solid #e5e7eb">
                <strong>{{ str_replace('_', ' ', ucfirst($event->event)) }}</strong>
                <div class="subtle">{{ $event->created_at }} · {{ $event->user?->name ?: 'System' }}</div>
            </div>
        @empty
            <div class="subtle">No activity recorded.</div>
        @endforelse
    </div>
</div>

@if(auth()->user()->hasTenantPermission('drafts.delete'))
<div class="card">
    <form method="post" action="{{ route('tenant.invoices.destroy', $invoice) }}" onsubmit="return confirm('Delete this invoice draft?')">
        @csrf @method('DELETE')
        <button class="btn danger" type="submit">Delete draft</button>
    </form>
</div>
@endif
@endsection
