@extends('tenant.layouts.workspace')

@section('title', __('ui.templates.title'))
@section('heading', __('ui.templates.title'))
@section('subheading', __('ui.templates.subtitle'))

@section('content')
@if(!$invoice)
<div class="alert">{{ __('ui.templates.select_invoice') }}</div>
@endif

<div class="template-grid">
@foreach($templates as $template)
    <article class="template-card">
        <div class="template-thumb family-{{ $template['family'] }}" style="--accent:{{ $template['accent'] }};--surface:{{ $template['surface'] }}">
            <div class="mini-head">
                <span>COMPANY</span><strong>INVOICE</strong>
            </div>
            <div class="mini-info"><i></i><i></i></div>
            <div class="mini-table"><b></b><span></span><span></span><span></span></div>
            <div class="mini-total"></div>
        </div>
        <div class="template-meta">
            <strong>{{ str_pad((string) $template['number'], 2, '0', STR_PAD_LEFT) }}. {{ $template['name'] }}</strong>
            @if($invoice && $template['id'])
                <div class="actions">
                    <a class="btn secondary small" target="_blank"
                       href="{{ route('tenant.invoices.preview', ['invoice' => $invoice, 'template_id' => $template['id'], 'locale' => $invoice->locale]) }}">
                        {{ __('ui.templates.preview') }}
                    </a>
                    @if(auth()->user()->hasTenantPermission('drafts.manage'))
                        <a class="btn small" href="{{ route('tenant.invoices.edit', $invoice) }}?template={{ $template['id'] }}">
                            {{ __('ui.templates.use_in_editor') }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </article>
@endforeach
</div>
@endsection

@push('head')
<style>
.template-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.template-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
.template-thumb{height:230px;background:#fff;padding:16px;position:relative;color:#334155;border-bottom:1px solid #e5e7eb}
.mini-head{display:flex;justify-content:space-between;font-size:9px;color:var(--accent);font-weight:800;padding-bottom:9px;border-bottom:2px solid var(--accent)}
.mini-info{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin:20px 0}.mini-info i{display:block;height:32px;background:var(--surface);border-radius:3px}
.mini-table{border-top:2px solid var(--accent)}.mini-table b{display:block;height:12px;background:var(--surface)}.mini-table span{display:block;height:17px;border-bottom:1px solid #e5e7eb}
.mini-total{position:absolute;inset-inline-end:16px;bottom:28px;width:75px;height:28px;background:var(--surface);border-top:2px solid var(--accent)}
.family-banner .mini-head{margin:-16px -16px 0;padding:17px 16px 22px;background:var(--accent);color:#fff;border:0}
.family-rail{border-inline-start:7px solid var(--accent)}.family-warm .mini-head{margin:-16px -16px 0;padding:17px 16px;background:var(--surface)}
.family-editorial .mini-head{font-size:12px;color:#111}.family-editorial .mini-head strong{order:-1;font-size:18px}
.family-border{margin:8px;border:2px solid var(--accent);height:214px}.family-wave .mini-head{margin:-16px -16px 0;padding:17px 16px 28px;background:var(--accent);color:#fff;border-radius:0 0 45% 45%}
.family-gold{border-top:5px solid var(--accent)}.family-mono{filter:grayscale(1)}.family-soft .mini-head,.family-soft .mini-info i{border-radius:7px;background:var(--surface);padding:7px}
.family-precision .mini-table b{background:var(--accent)}
.template-meta{padding:13px}.template-meta strong{display:block;margin-bottom:10px}
@media(max-width:1100px){.template-grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:800px){.template-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:520px){.template-grid{grid-template-columns:1fr}}
</style>
@endpush
