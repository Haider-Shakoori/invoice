@extends('central.layout')
@section('title','Start Free Trial')
@section('auth')
<div class="auth-shell">
<section class="auth-panel"><div class="auth-card" style="width:min(520px,100%)">
<div class="auth-brand"><span class="logo-mark">I</span>Invoice Drafts <span class="muted">by BusinessOS</span></div>
<h1>Start your 7-day trial</h1><p>Create your private invoice workspace. No payment is required to start.</p>
@if($errors->any())<div class="alert"><ul style="margin:0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('central.register.store') }}">@csrf
<div class="grid grid-2"><div class="field"><label>Company name</label><input name="company_name" value="{{ old('company_name') }}" required></div><div class="field"><label>Your name</label><input name="owner_name" value="{{ old('owner_name') }}" required></div></div>
<div class="grid grid-2"><div class="field"><label>Email address</label><input type="email" name="owner_email" value="{{ old('owner_email') }}" required></div><div class="field"><label>Phone <span class="muted">(optional)</span></label><input name="owner_phone" value="{{ old('owner_phone') }}"></div></div>
<div class="field"><label>Workspace address</label><div style="display:flex;align-items:center;border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;background:#fff"><input style="border:0;border-radius:0;box-shadow:none" name="slug" value="{{ old('slug') }}" placeholder="your-company" required><span style="padding:0 12px;color:#64748b;font-size:12px;white-space:nowrap">.invoice.businessos.af</span></div></div>
<div class="grid grid-2"><div class="field"><label>Password</label><input type="password" name="password" required></div><div class="field"><label>Confirm password</label><input type="password" name="password_confirmation" required></div></div>
<button class="btn" style="width:100%;margin-top:5px" type="submit">Create my workspace</button>
</form><div class="auth-links">Already have platform access? <a href="{{ route('central.login') }}">Sign in</a></div>
</div></section>
<section class="auth-art"><div style="font-size:13px;color:#93c5fd;font-weight:800">7 DAYS FREE</div><h2>Your invoices deserve better than spreadsheets.</h2><p>20 professional designs, multilingual PDF output, client management and private tenant data—without inventory or accounting complexity.</p></section>
</div>
@endsection