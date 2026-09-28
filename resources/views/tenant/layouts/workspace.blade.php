<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Invoice Drafts')</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033;background:#f5f7fb}
        *{box-sizing:border-box}body{margin:0}.shell{min-height:100vh;display:grid;grid-template-columns:240px 1fr}
        aside{background:#111827;color:#fff;padding:24px 18px}.brand{font-size:20px;font-weight:800;margin-bottom:28px}.muted{color:#94a3b8}
        nav a{display:block;color:#cbd5e1;text-decoration:none;padding:10px 12px;border-radius:9px;margin:4px 0}nav a:hover,nav a.active{background:#1f2937;color:#fff}
        main{padding:28px;min-width:0}.topbar{display:flex;gap:16px;align-items:center;justify-content:space-between;margin-bottom:24px}.topbar h1{margin:0;font-size:26px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;box-shadow:0 2px 12px rgba(15,23,42,.04);margin-bottom:18px}
        .grid{display:grid;gap:16px}.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
        label{display:block;font-size:13px;font-weight:700;color:#475569;margin-bottom:6px}input,select,textarea{width:100%;padding:10px 11px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#111827}
        textarea{min-height:90px;resize:vertical}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:9px;padding:10px 14px;background:#111827;color:#fff;text-decoration:none;font-weight:700;cursor:pointer}
        .btn.secondary{background:#e2e8f0;color:#0f172a}.btn.danger{background:#b91c1c}.btn.small{padding:7px 10px;font-size:12px}.actions{display:flex;gap:8px;flex-wrap:wrap}
        table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #e5e7eb;vertical-align:top}th{font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
        .badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#e2e8f0;font-size:12px;font-weight:700}.badge.ok{background:#dcfce7;color:#166534}
        .alert{padding:12px 14px;border-radius:9px;margin-bottom:16px;background:#ecfeff;border:1px solid #a5f3fc}.alert.error{background:#fef2f2;border-color:#fecaca;color:#991b1b}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.stat{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px}.stat strong{display:block;font-size:22px}
        .right{text-align:right}.nowrap{white-space:nowrap}.empty{text-align:center;padding:34px;color:#64748b}.subtle{font-size:13px;color:#64748b}.field{margin-bottom:14px}
        @media(max-width:900px){.shell{grid-template-columns:1fr}aside{padding:14px 16px}nav{display:flex;gap:6px;overflow:auto}.brand{margin-bottom:12px}.grid-2,.grid-3,.stats{grid-template-columns:1fr}main{padding:18px}.topbar{align-items:flex-start;flex-direction:column}.table-wrap{overflow-x:auto}}
    </style>
    @stack('head')
</head>
<body>
<div class="shell">
    <aside>
        <div class="brand">Invoice Drafts</div>
        <nav>
            @auth
                @if(auth()->user()->hasTenantPermission('drafts.view'))
                    <a href="{{ route('tenant.invoices.index') }}" class="{{ request()->routeIs('tenant.invoices.*') ? 'active' : '' }}">Invoices</a>
                @endif
                @if(auth()->user()->hasTenantPermission('clients.view'))
                    <a href="{{ route('tenant.customers.index') }}" class="{{ request()->routeIs('tenant.customers.*') ? 'active' : '' }}">Clients</a>
                @endif
                @if(auth()->user()->hasTenantPermission('staff.manage'))
                    <a href="{{ route('tenant.staff.index') }}" class="{{ request()->routeIs('tenant.staff.*') ? 'active' : '' }}">Staff</a>
                @endif
                <a href="{{ route('tenant.subscription.status') }}">Subscription</a>
            @endauth
        </nav>
        @auth
            <div style="margin-top:28px" class="subtle">
                {{ auth()->user()->name }}<br>
                <span class="muted">{{ auth()->user()->email }}</span>
            </div>
            <form method="post" action="{{ route('tenant.logout') }}" style="margin-top:12px">
                @csrf
                <button class="btn secondary small" type="submit">Sign out</button>
            </form>
        @endauth
    </aside>
    <main>
        <div class="topbar">
            <div>
                <h1>@yield('heading', 'Workspace')</h1>
                @hasSection('subheading')<div class="subtle">@yield('subheading')</div>@endif
            </div>
            <div class="actions">@yield('actions')</div>
        </div>

        @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if($errors->any())
            <div class="alert error">
                <strong>Please correct the following:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
