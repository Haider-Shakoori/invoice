<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['fa','ps'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', __('ui.product'))</title>
    <style>
:root{font-family:Inter,"Noto Naskh Arabic","Noto Sans Arabic","DejaVu Sans",ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:#172033;background:#f6f8fc;line-height:1.5;--nav:#0f172a;--brand:#2563eb;--line:#e2e8f0;--muted:#64748b}*{box-sizing:border-box}body{margin:0;background:#f6f8fc}.shell{min-height:100vh;display:grid;grid-template-columns:260px minmax(0,1fr)}aside{background:linear-gradient(180deg,#0f172a,#111827);color:#fff;padding:25px 18px;position:sticky;top:0;height:100vh;overflow-y:auto}.brand{font-size:19px;font-weight:850;margin:0 8px 28px;display:flex;align-items:center;gap:10px}.brand:before{content:"I";display:grid;place-items:center;width:36px;height:36px;border-radius:10px;background:#2563eb;color:#fff}nav a{display:flex;align-items:center;color:#cbd5e1;text-decoration:none;padding:10px 12px;border-radius:10px;margin:3px 0;font-size:14px;font-weight:650;white-space:nowrap}nav a:hover,nav a.active{background:#1e293b;color:#fff}main{padding:30px;min-width:0;max-width:1500px;width:100%;margin:0 auto}.topbar{display:flex;gap:18px;align-items:flex-start;justify-content:space-between;margin-bottom:24px}.topbar h1{margin:0;font-size:28px;letter-spacing:-.035em}.topbar .subtle{margin-top:4px}.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(15,23,42,.035);margin-bottom:18px}.card h2{font-size:17px;margin:0 0 17px}.grid{display:grid;gap:15px}.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}label{display:block;font-size:12px;font-weight:750;color:#475569;margin-bottom:6px}input,select,textarea{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#111827;font:inherit;outline:none}input:focus,select:focus,textarea:focus{border-color:#3b82f6;box-shadow:0 0 0 3px #dbeafe}textarea{min-height:96px;resize:vertical}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:10px;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;font-size:13px;font-weight:750;cursor:pointer}.btn:hover{background:#1d4ed8}.btn.secondary{background:#eef2f7;color:#334155}.btn.danger{background:#b91c1c}.btn.small{padding:7px 10px;font-size:12px}.actions{display:flex;gap:8px;flex-wrap:wrap}table{width:100%;border-collapse:collapse;min-width:680px}th,td{text-align:start;padding:12px 10px;border-bottom:1px solid #edf0f4;vertical-align:middle}th{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.08em;font-weight:800}td{font-size:13px}.badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:800}.badge.ok{background:#dcfce7;color:#166534}.alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}.alert.error{background:#fef2f2;border-color:#fecaca;color:#991b1b}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.stat{background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:14px}.stat strong{display:block;font-size:21px;letter-spacing:-.02em}.right{text-align:end}.nowrap{white-space:nowrap}.empty{text-align:center;padding:38px;color:#64748b}.subtle{font-size:12px;color:#64748b}.field{margin-bottom:14px}.locale-form{margin-top:22px;padding-top:18px;border-top:1px solid #1e293b}.locale-form label{color:#94a3b8}.locale-form select{background:#1e293b;border-color:#334155;color:#fff}.numeric{direction:ltr;unicode-bidi:isolate}.skip-link{position:fixed;inset-inline-start:12px;top:-60px;z-index:9999;background:#fff;color:#111827;padding:10px 14px;border-radius:8px;text-decoration:none;font-weight:800}.skip-link:focus{top:12px}:focus-visible{outline:3px solid #60a5fa;outline-offset:2px}.table-wrap{overflow-x:auto;border-radius:10px}.table-wrap::-webkit-scrollbar{height:7px}.table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:9px}details summary{list-style:none}details[open]{position:relative}details[open]>form{background:#fff;border:1px solid var(--line);border-radius:12px;padding:14px;box-shadow:0 12px 35px rgba(15,23,42,.12);z-index:4}
@media(max-width:980px){.shell{grid-template-columns:1fr}aside{height:auto;position:static;padding:13px 16px;overflow:visible}.brand{margin:0 0 11px;font-size:16px}.brand:before{width:30px;height:30px}nav{display:flex;gap:5px;overflow-x:auto;padding-bottom:3px}nav a{margin:0;padding:9px 11px}.locale-form{margin:10px 0 0;padding:0;border:0;display:flex;align-items:center;gap:8px}.locale-form label{margin:0}.locale-form select{width:auto;padding:7px 9px}aside>.subtle,aside>form[style]{display:none}main{padding:20px}.grid-3{grid-template-columns:repeat(2,minmax(0,1fr))}.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:640px){main{padding:16px}.topbar{flex-direction:column;margin-bottom:18px}.topbar h1{font-size:24px}.grid-2,.grid-3,.stats{grid-template-columns:1fr}.card{padding:15px;border-radius:13px}.actions{width:100%}.topbar>.actions .btn{flex:1}.field{margin-bottom:12px}nav a{font-size:12px}.right{text-align:start}}
</style>
    @stack('head')
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="shell">
    <aside>
        <div class="brand">{{ __('ui.product') }}</div>
        <nav>
            @auth
                @if(auth()->user()->hasTenantPermission('drafts.view'))
                    <a href="{{ route('tenant.invoices.index') }}" class="{{ request()->routeIs('tenant.invoices.*') ? 'active' : '' }}">{{ __('ui.nav.invoices') }}</a>
                    <a href="{{ route('tenant.templates.index') }}" class="{{ request()->routeIs('tenant.templates.*') ? 'active' : '' }}">{{ __('ui.nav.templates') }}</a>
                @endif
                @if(auth()->user()->hasTenantPermission('clients.view'))
                    <a href="{{ route('tenant.customers.index') }}" class="{{ request()->routeIs('tenant.customers.*') ? 'active' : '' }}">{{ __('ui.nav.clients') }}</a>
                @endif
                @if(auth()->user()->hasTenantPermission('staff.manage'))
                    <a href="{{ route('tenant.staff.index') }}" class="{{ request()->routeIs('tenant.staff.*') ? 'active' : '' }}">{{ __('ui.nav.staff') }}</a>
                @endif
                @if(auth()->user()->hasTenantPermission('settings.manage'))
                    <a href="{{ route('tenant.onboarding.show') }}" class="{{ request()->routeIs('tenant.onboarding.*') ? 'active' : '' }}">{{ __('ui.nav.company') }}</a>
                @endif
                <a href="{{ route('tenant.subscription.status') }}">{{ __('ui.nav.subscription') }}</a>
            @endauth
        </nav>

        <form class="locale-form" method="post" action="{{ route('tenant.locale.update') }}">
            @csrf
            <label for="tenant-locale">{{ __('ui.nav.language') }}</label>
            <select id="tenant-locale" name="locale" onchange="this.form.submit()">
                @foreach(config('invoice.locales') as $locale)
                    <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>{{ __('ui.languages.'.$locale) }}</option>
                @endforeach
            </select>
        </form>

        @auth
            <div style="margin-top:28px" class="subtle">
                {{ auth()->user()->name }}<br>
                <span class="muted">{{ auth()->user()->email }}</span>
            </div>
            <form method="post" action="{{ route('tenant.logout') }}" style="margin-top:12px">
                @csrf
                <button class="btn secondary small" type="submit">{{ __('ui.nav.sign_out') }}</button>
            </form>
        @endauth
    </aside>
    <main id="main-content" tabindex="-1">
        <div class="topbar">
            <div>
                <h1>@yield('heading', __('ui.workspace'))</h1>
                @hasSection('subheading')<div class="subtle">@yield('subheading')</div>@endif
            </div>
            <div class="actions">@yield('actions')</div>
        </div>

        @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if($errors->any())
            <div class="alert error">
                <strong>{{ __('ui.common.please_correct') }}</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
