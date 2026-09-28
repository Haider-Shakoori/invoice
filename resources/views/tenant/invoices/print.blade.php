<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<title>{{ $invoice->number }}</title>
<style>
    @page { size: A4; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #fff; }
    body {
        font-family: {!! $rtl ? "'".config('invoice.pdf.rtl_font_family')."'" : "'".config('invoice.pdf.font_family')."'" !!};
        color: #202938;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-size: 10.5pt;
    }
    .invoice-page {
        --accent: {{ $template['accent'] }};
        --surface: {{ $template['surface'] }};
        position: relative;
        width: 210mm;
        min-height: 297mm;
        padding: 18mm 15mm 21mm;
        background: #fff;
        overflow: hidden;
    }
    .invoice-page.rtl { direction: rtl; }
    .invoice-page.ltr { direction: ltr; }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20mm;
        min-height: 30mm;
        position: relative;
        z-index: 2;
    }
    .brand-wrap { display: flex; align-items: flex-start; gap: 4mm; min-width: 0; }
    .logo { max-width: 24mm; max-height: 18mm; object-fit: contain; }
    .brand-name { margin: 0; font-size: 19pt; line-height: 1.08; font-weight: 800; letter-spacing: .2px; color: var(--accent); }
    .brand-secondary { margin-top: 2mm; font-size: 9pt; color: #657083; }
    .title-wrap { text-align: end; min-width: 44mm; }
    .doc-title { margin: 0; font-size: 24pt; line-height: 1; font-weight: 800; color: var(--accent); letter-spacing: .3px; }
    .doc-number { margin-top: 3mm; color: #667085; font-size: 9pt; }
    .professional-label {
        display: none;
        margin: 3mm 0 10mm;
        padding: 2.5mm 3mm;
        font-weight: 700;
        color: var(--accent);
        background: var(--surface);
        font-size: 8.5pt;
    }
    .party-grid {
        margin-top: 12mm;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12mm;
    }
    .info-block { min-height: 27mm; }
    .section-label { color: var(--accent); font-size: 8pt; font-weight: 800; text-transform: uppercase; margin-bottom: 2mm; letter-spacing: .15px; }
    .customer-name { font-size: 13pt; font-weight: 800; margin-bottom: 1.5mm; }
    .info-line { line-height: 1.55; }
    .numeric, .doc-number, .money { direction: ltr; unicode-bidi: isolate; font-variant-numeric: tabular-nums; }
    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 13mm;
        color: #263244;
    }
    table.items thead { display: table-header-group; }
    table.items tr { break-inside: avoid; page-break-inside: avoid; }
    table.items th {
        padding: 3.2mm 3mm;
        border-top: .35mm solid var(--accent);
        border-bottom: .35mm solid var(--accent);
        color: var(--accent);
        text-align: start;
        font-size: 8pt;
        font-weight: 800;
        text-transform: uppercase;
    }
    table.items td {
        padding: 4mm 3mm;
        border-bottom: .25mm solid #e4e7ec;
        vertical-align: top;
    }
    table.items th.num, table.items td.num { text-align: end; direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
    .row-index { display: none; width: 10mm; color: var(--accent); font-weight: 800; }
    .lower {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 72mm;
        gap: 12mm;
        margin-top: 8mm;
        align-items: start;
    }
    .notes { font-size: 9.5pt; line-height: 1.6; }
    .notes .section-label + div { margin-bottom: 5mm; }
    .totals {
        background: var(--surface);
        border-radius: 2mm;
        padding: 4mm 4.5mm;
    }
    .total-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8mm;
        padding: 1.2mm 0;
        align-items: baseline;
    }
    .total-row.grand {
        margin-top: 2mm;
        padding-top: 3mm;
        border-top: .45mm solid var(--accent);
        color: var(--accent);
        font-size: 15pt;
        font-weight: 800;
    }
    .signature-zone {
        margin-top: 18mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 10mm;
        min-height: 23mm;
    }
    .signature-assets { display: flex; gap: 5mm; align-items: flex-end; }
    .signature-assets img { max-height: 19mm; max-width: 35mm; object-fit: contain; }
    .signature-label { padding-top: 2mm; border-top: .3mm solid var(--accent); min-width: 54mm; font-size: 8pt; color: #697386; }
    .company-footer { text-align: end; color: #697386; font-size: 8pt; }
    .page-foot {
        position: fixed;
        inset-inline: 15mm;
        bottom: 5mm;
        display: flex;
        justify-content: space-between;
        gap: 8mm;
        color: #8a93a3;
        font-size: 7pt;
        border-top: .25mm solid color-mix(in srgb, var(--accent) 65%, #ffffff);
        padding-top: 2mm;
    }
    .page-counter::after { content: counter(page); }

    /* 01/06/08 - strong business mastheads */
    .template-executive-navy,
    .template-corporate-blue,
    .template-charcoal-pro { padding-top: 0; }
    .template-executive-navy .header,
    .template-corporate-blue .header,
    .template-charcoal-pro .header {
        margin-inline: -15mm;
        padding: 17mm 15mm 14mm;
        min-height: 52mm;
        color: #fff;
        background: var(--accent);
    }
    .template-executive-navy .brand-name,
    .template-executive-navy .brand-secondary,
    .template-executive-navy .doc-title,
    .template-executive-navy .doc-number,
    .template-corporate-blue .brand-name,
    .template-corporate-blue .brand-secondary,
    .template-corporate-blue .doc-title,
    .template-corporate-blue .doc-number,
    .template-charcoal-pro .brand-name,
    .template-charcoal-pro .brand-secondary,
    .template-charcoal-pro .doc-title,
    .template-charcoal-pro .doc-number { color: #fff; }
    .template-executive-navy .party-grid,
    .template-corporate-blue .party-grid,
    .template-charcoal-pro .party-grid { margin-top: 12mm; }

    /* 02 - minimal white */
    .template-minimal-white .header { min-height: 20mm; padding-bottom: 5mm; border-bottom: .25mm solid #222; }
    .template-minimal-white .brand-name { font-size: 13pt; }
    .template-minimal-white .doc-title { font-size: 14pt; }
    .template-minimal-white .doc-number { display: none; }
    .template-minimal-white table.items th { border-top: .25mm solid #222; border-bottom: .25mm solid #222; color: #222; }
    .template-minimal-white .totals { background: transparent; border-radius: 0; padding-inline: 0; }
    .template-minimal-white .total-row.grand { color: #111; }

    /* 03 - classic ledger */
    .template-classic-ledger .header { padding-bottom: 5mm; border-bottom: .35mm solid var(--accent); }
    .template-classic-ledger .brand-name { font-size: 15pt; }
    .template-classic-ledger .doc-title { font-size: 16pt; }
    .template-classic-ledger table.items thead { background: var(--accent); }
    .template-classic-ledger table.items th { color: #fff; border-color: var(--accent); }
    .template-classic-ledger .totals { background: transparent; border-radius: 0; }

    /* 04 - modern indigo */
    .template-modern-indigo { border-top: 3mm solid var(--accent); }
    .template-modern-indigo .professional-label { display: block; }
    .template-modern-indigo .party-grid { margin-top: 0; }
    .template-modern-indigo .info-block { padding: 4mm; border-radius: 2mm; background: #f7f7fb; }
    .template-modern-indigo table.items thead { background: #edf1ff; }
    .template-modern-indigo table.items th { border: 0; color: var(--accent); }
    .template-modern-indigo table.items tbody tr:nth-child(odd) { background: #f6f8ff; }
    .template-modern-indigo .totals { background: transparent; border-radius: 0; }

    /* 05/17 - vertical rail */
    .template-emerald-business::before,
    .template-split-header::before {
        content: "";
        position: absolute;
        inset-block: 0;
        inset-inline-start: 0;
        width: 4mm;
        background: var(--accent);
    }
    .template-emerald-business .header,
    .template-split-header .header { padding-inline-start: 7mm; }
    .template-emerald-business .brand-name,
    .template-split-header .brand-name { font-size: 18pt; }
    .template-emerald-business .title-wrap,
    .template-split-header .title-wrap { padding-top: 1mm; }

    /* 07 - warm sand */
    .template-warm-sand { padding-top: 0; }
    .template-warm-sand .header {
        margin-inline: -15mm;
        padding: 17mm 15mm 12mm;
        min-height: 48mm;
        background: var(--surface);
        border-bottom: .35mm solid color-mix(in srgb, var(--accent) 45%, #ffffff);
    }
    .template-warm-sand .totals { background: transparent; }

    /* 09 - editorial */
    .template-editorial .header { align-items: center; border-bottom: .35mm solid #27313f; padding-bottom: 4mm; }
    .template-editorial .brand-wrap { order: 2; margin-inline-start: auto; text-align: end; }
    .template-editorial .brand-name { font-size: 8pt; color: #27313f; }
    .template-editorial .brand-secondary { font-size: 7pt; }
    .template-editorial .title-wrap { order: 1; text-align: start; margin-inline-end: auto; }
    .template-editorial .doc-title { font-size: 28pt; text-transform: none; color: #27313f; }
    .template-editorial .doc-title::after { content: "."; }
    .template-editorial .doc-number { display: none; }
    .template-editorial .totals { background: transparent; }

    /* 10 - compact trade */
    .template-compact-trade { font-size: 9.5pt; }
    .template-compact-trade .header {
        padding: 4mm;
        min-height: 14mm;
        background: var(--surface);
        align-items: center;
    }
    .template-compact-trade .brand-name { font-size: 10pt; }
    .template-compact-trade .doc-title { font-size: 10pt; }
    .template-compact-trade .doc-number { display: inline; margin: 0; }
    .template-compact-trade .party-grid { margin-top: 6mm; }
    .template-compact-trade table.items { margin-top: 6mm; }
    .template-compact-trade table.items th,
    .template-compact-trade table.items td { padding-block: 2.4mm; }
    .template-compact-trade table.items thead { background: var(--accent); }
    .template-compact-trade table.items th { color: #fff; }
    .template-compact-trade .lower { margin-top: 4mm; }
    .template-compact-trade .totals { background: transparent; }

    /* 11 - signature */
    .template-signature .header { padding-bottom: 4mm; border-bottom: .35mm solid var(--accent); }
    .template-signature .party-grid .info-block:last-child { background: var(--surface); padding: 4mm; }
    .template-signature .signature-zone { min-height: 36mm; }
    .template-signature .signature-label { min-width: 72mm; }

    /* 12 - borderline */
    .template-borderline {
        margin: 8mm;
        width: 194mm;
        min-height: 281mm;
        padding: 12mm 11mm 18mm;
        border: .65mm solid color-mix(in srgb, var(--accent) 65%, #ffffff);
    }
    .template-borderline .page-foot { inset-inline: 20mm; bottom: 11mm; }
    .template-borderline .header { border-bottom: .35mm solid var(--accent); padding-bottom: 4mm; }
    .template-borderline table.items thead { background: var(--accent); }
    .template-borderline table.items th { color: #fff; }

    /* 13 - azure wave */
    .template-azure-wave { padding-top: 0; }
    .template-azure-wave .header {
        margin-inline: -15mm;
        padding: 15mm 15mm 25mm;
        min-height: 49mm;
        background: var(--accent);
        border-end-start-radius: 48% 11mm;
        border-end-end-radius: 48% 11mm;
    }
    .template-azure-wave .brand-name,
    .template-azure-wave .brand-secondary,
    .template-azure-wave .doc-title,
    .template-azure-wave .doc-number { color: #fff; }
    .template-azure-wave table.items thead { background: #eaf3ff; }
    .template-azure-wave table.items th { border: 0; }

    /* 14 - slate grid */
    .template-slate-grid .header { padding: 3mm 4mm; background: var(--surface); }
    .template-slate-grid .party-grid .info-block { background: #f7f8fa; padding: 4mm; }
    .template-slate-grid table.items thead { background: #eef1f5; }
    .template-slate-grid table.items th { border: 0; }
    .template-slate-grid .totals { background: transparent; }

    /* 15 - gold accent */
    .template-gold-accent { border-top: 2.5mm solid var(--accent); }
    .template-gold-accent .header { border-bottom: .4mm solid var(--accent); padding-bottom: 5mm; }
    .template-gold-accent .totals { background: #f7f8fa; }
    .template-gold-accent table.items th { color: var(--accent); }

    /* 16 - monochrome statement */
    .template-mono-statement { filter: grayscale(1); }
    .template-mono-statement .header { border-bottom: .35mm solid #111; padding-bottom: 5mm; }
    .template-mono-statement .totals { background: transparent; }
    .template-mono-statement table.items th { color: #111; border-color: #111; }

    /* 18 - letterhead */
    .template-letterhead .header { padding-bottom: 5mm; border-bottom: .45mm solid var(--accent); }
    .template-letterhead .brand-name { font-size: 14pt; }
    .template-letterhead .brand-secondary { display: block; }
    .template-letterhead .doc-title { font-size: 15pt; }
    .template-letterhead .doc-number { display: none; }
    .template-letterhead .party-grid { margin-top: 7mm; }
    .template-letterhead .totals { background: transparent; }

    /* 19 - soft blue */
    .template-soft-blue .header { background: var(--surface); border-radius: 2mm; padding: 4mm 5mm; min-height: 16mm; }
    .template-soft-blue .brand-name, .template-soft-blue .doc-title { font-size: 11pt; color: var(--accent); }
    .template-soft-blue .doc-number { display: none; }
    .template-soft-blue .party-grid .info-block { background: #f5f8fb; border-radius: 2mm; padding: 4mm; }
    .template-soft-blue table.items thead { background: #e9f2ff; }
    .template-soft-blue table.items th { border: 0; }
    .template-soft-blue .totals { background: transparent; }

    /* 20 - precision */
    .template-precision .header { border-bottom: .75mm solid var(--accent); padding-bottom: 4mm; }
    .template-precision table.items thead { background: var(--accent); }
    .template-precision table.items th { color: #fff; border: 0; }
    .template-precision .row-index { display: table-cell; }
    .template-precision .totals { background: var(--surface); }

    @media print {
        .invoice-page { break-after: page; }
        a { color: inherit; text-decoration: none; }
    }
</style>
</head>
<body>
@php
    app()->setLocale($locale);
    $companyName = $company['display_name'] ?? '';
    $companySecondary = $company['secondary_name'] ?? null;
    $companyLocation = collect([$company['city_province'] ?? null, $company['address'] ?? null])->filter()->join(' · ');
    $companyContact = collect([$company['phone'] ?? null, $company['email'] ?? null])->filter()->join(' · ');
    $clientLocation = collect([$customer['city_province'] ?? null, $customer['address'] ?? null])->filter()->join(' · ');
@endphp
<div class="invoice-page template-{{ $template['key'] }} {{ $rtl ? 'rtl' : 'ltr' }}">
    <header class="header">
        <div class="brand-wrap">
            @if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="">@endif
            <div>
                <h1 class="brand-name">{{ $companyName }}</h1>
                @if($companySecondary)<div class="brand-secondary">{{ $companySecondary }}</div>@endif
                @if($companyLocation || $companyContact)
                    <div class="brand-secondary">{{ $companyLocation }}{{ $companyLocation && $companyContact ? ' | ' : '' }}{{ $companyContact }}</div>
                @endif
            </div>
        </div>
        <div class="title-wrap">
            <h2 class="doc-title">{{ __('invoice.invoice') }}</h2>
            <div class="doc-number">{{ $invoice->number }}</div>
        </div>
    </header>

    <div class="professional-label">{{ __('invoice.professional_invoice') }}</div>

    <section class="party-grid">
        <div class="info-block">
            <div class="section-label">{{ __('invoice.bill_to') }}</div>
            <div class="customer-name">{{ $customer['name'] ?? '' }}</div>
            @if($customer['company_name'] ?? null)<div class="info-line">{{ $customer['company_name'] }}</div>@endif
            @if($clientLocation)<div class="info-line">{{ $clientLocation }}</div>@endif
            @if($customer['phone'] ?? null)<div class="info-line numeric">{{ $customer['phone'] }}</div>@endif
            @if($customer['email'] ?? null)<div class="info-line">{{ $customer['email'] }}</div>@endif
        </div>

        <div class="info-block">
            <div class="section-label">{{ __('invoice.invoice_details') }}</div>
            <div class="info-line"><strong>{{ __('invoice.number') }}:</strong> <span class="numeric">{{ $invoice->number }}</span></div>
            <div class="info-line"><strong>{{ __('invoice.date') }}:</strong> <span class="numeric">{{ $invoice->issue_date->format('Y-m-d') }}</span></div>
            @if($invoice->due_date)<div class="info-line"><strong>{{ __('invoice.due_date') }}:</strong> <span class="numeric">{{ $invoice->due_date->format('Y-m-d') }}</span></div>@endif
            <div class="info-line"><strong>{{ __('invoice.currency') }}:</strong> <span class="numeric">{{ $invoice->currency }}</span></div>
        </div>
    </section>

    <table class="items">
        <thead>
            <tr>
                <th class="row-index">#</th>
                <th>{{ __('invoice.description') }}</th>
                <th class="num">{{ __('invoice.qty') }}</th>
                <th>{{ __('invoice.unit') }}</th>
                <th class="num">{{ __('invoice.unit_price') }}</th>
                <th class="num">{{ __('invoice.discount') }}</th>
                <th class="num">{{ __('invoice.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td class="row-index numeric">{{ str_pad((string) $line->position, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="num numeric">{{ rtrim(rtrim(number_format((float) $line->quantity, 3, '.', ''), '0'), '.') }}</td>
                    <td>{{ $line->unit }}</td>
                    <td class="num money">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="num numeric">{{ (float) $line->discount_percent > 0 ? rtrim(rtrim(number_format((float) $line->discount_percent, 4, '.', ''), '0'), '.').'%' : '-' }}</td>
                    <td class="num money"><strong>{{ number_format((float) $line->line_total, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <section class="lower">
        <div class="notes">
            @if($invoice->notes)
                <div class="section-label">{{ __('invoice.notes') }}</div>
                <div>{!! nl2br(e($invoice->notes)) !!}</div>
            @endif
            @if($invoice->terms)
                <div class="section-label">{{ __('invoice.terms') }}</div>
                <div>{!! nl2br(e($invoice->terms)) !!}</div>
            @endif
        </div>

        <div class="totals">
            <div class="total-row"><span>{{ __('invoice.subtotal') }}</span><span class="money">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</span></div>
            @if((float) $invoice->discount_amount > 0)
                <div class="total-row"><span>{{ __('invoice.discount') }}</span><span class="money">{{ number_format((float) $invoice->discount_amount, 2) }} {{ $invoice->currency }}</span></div>
            @endif
            @if((float) ($invoice->additional_charge_amount ?? 0) > 0)
                <div class="total-row"><span>{{ $invoice->additional_charge_label ?: __('invoice.additional_charge') }}</span><span class="money">{{ number_format((float) $invoice->additional_charge_amount, 2) }} {{ $invoice->currency }}</span></div>
            @endif
            @if((float) ($invoice->tax_amount ?? 0) > 0)
                <div class="total-row"><span>{{ $invoice->tax_label ?: __('invoice.tax') }} @if((float) $invoice->tax_rate > 0)<span class="numeric">({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 4, '.', ''), '0'), '.') }}%)</span>@endif</span><span class="money">{{ number_format((float) $invoice->tax_amount, 2) }} {{ $invoice->currency }}</span></div>
            @endif
            <div class="total-row grand"><span>{{ __('invoice.total') }}</span><span class="money">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</span></div>
        </div>
    </section>

    <section class="signature-zone">
        <div>
            <div class="signature-assets">
                @if($signatureDataUri)<img src="{{ $signatureDataUri }}" alt="">@endif
                @if($stampDataUri)<img src="{{ $stampDataUri }}" alt="">@endif
            </div>
            <div class="signature-label">{{ __('invoice.authorized_signature') }}</div>
        </div>
        <div class="company-footer">
            <strong>{{ $companyName }}</strong>
            @if($companyLocation)<div>{{ $companyLocation }}</div>@endif
        </div>
    </section>

    <div class="page-foot">
        <span>{{ str_pad((string) $template['number'], 2, '0', STR_PAD_LEFT) }} / {{ mb_strtoupper($template['name']) }}</span>
        <span>{{ __('invoice.page') }} <span class="page-counter"></span></span>
    </div>
</div>
</body>
</html>
