@extends('central.layout')
@section('title','Platform Login')
@section('auth')
<div class="auth-shell">
<section class="auth-panel"><div class="auth-card">
<div class="auth-brand"><span class="logo-mark">I</span>Invoice Drafts <span class="muted">by BusinessOS</span></div>
<h1>Welcome back</h1><p>Sign in to manage subscriptions, businesses and platform billing.</p>
@if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('central.login.store') }}">@csrf
<div class="field"><label>Email address</label><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div>
<div class="field"><label>Password</label><input type="password" name="password" autocomplete="current-password" required></div>
<label style="display:flex;gap:8px;align-items:center;font-size:13px;color:#475569"><input style="width:auto" type="checkbox" name="remember" value="1"> Remember me</label>
<button class="btn" style="width:100%;margin-top:20px" type="submit">Sign in to platform</button>
</form><div class="auth-links"><a href="{{ route('central.register') }}">Create a new business instead →</a></div>
</div></section>
<section class="auth-art"><div class="eyebrow" style="color:#93c5fd">BusinessOS · Invoice Drafts</div><h2>One focused workspace for professional invoices.</h2><p>Central administration for subscriptions, trials, activations and isolated business workspaces.</p></section>
</div>
@endsection