<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['fa','ps'], true) ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ __('ui.auth.title') }}</title>
<style>
body{font-family:Inter,"Noto Naskh Arabic","Noto Sans Arabic","DejaVu Sans",system-ui,sans-serif;background:#f5f7fb;margin:0;color:#172033}
.wrap{max-width:420px;margin:8vh auto;padding:20px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(15,23,42,.07)}
h1{margin:0 0 20px}label{display:block;font-weight:700;font-size:13px;margin:14px 0 6px}input,select{width:100%;box-sizing:border-box;padding:11px;border:1px solid #cbd5e1;border-radius:9px;font:inherit}
.inline{display:flex;align-items:center;gap:8px}.inline input{width:auto}.btn{width:100%;margin-top:18px;padding:11px;border:0;border-radius:9px;background:#111827;color:#fff;font-weight:800;cursor:pointer}.error{color:#991b1b;margin-top:12px}
.locale{margin-bottom:16px}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <form class="locale" method="post" action="{{ route('tenant.locale.update') }}">
            @csrf
            <label>{{ __('ui.nav.language') }}</label>
            <select name="locale" onchange="this.form.submit()">
                @foreach(config('invoice.locales') as $locale)
                    <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>{{ __('ui.languages.'.$locale) }}</option>
                @endforeach
            </select>
        </form>
        <h1>{{ __('ui.auth.title') }}</h1>
        <form method="post" action="{{ route('tenant.login.store') }}">
            @csrf
            <label>{{ __('ui.auth.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            <label>{{ __('ui.auth.password') }}</label>
            <input type="password" name="password" required>
            <label class="inline"><input type="checkbox" name="remember" value="1"> {{ __('ui.auth.remember') }}</label>
            <button class="btn" type="submit">{{ __('ui.auth.sign_in') }}</button>
        </form>
        @if($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
    </div>
</div>
</body>
</html>
