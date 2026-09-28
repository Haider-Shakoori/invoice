@extends('central.layout')

@section('title','Security')
@section('heading','Account security')
@section('subheading','Change the password used to access the BusinessOS platform administration portal.')

@section('content')
<div style="max-width:720px">
<div class="card">
<h2 style="margin:0 0 6px;font-size:18px">Platform administrator</h2>
<p class="muted" style="margin:0 0 20px">Signed in as <strong>{{ Auth::guard('central')->user()->email }}</strong></p>
<form method="post" action="{{ route('central.security.password') }}">
@csrf @method('PUT')
<div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
<div class="grid grid-2">
<div class="field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" required></div>
<div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
</div>
<p class="muted">Use at least 10 characters with uppercase and lowercase letters and a number.</p>
<button class="btn" type="submit">Change password</button>
</form>
</div>
<div class="card"><h2 style="margin:0 0 8px;font-size:16px">Security note</h2><p class="muted" style="margin:0">This changes only your central Platform Admin account. Tenant business-owner and staff passwords are unaffected.</p></div>
</div>
@endsection
