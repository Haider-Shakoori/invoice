@extends('central.layout')

@section('title','Operators')
@section('heading','Platform operators')
@section('subheading','Create and manage people who can sign in to the central SaaS administration portal.')

@section('content')
<div class="grid grid-2" style="align-items:start">
<div class="card">
<h2 style="margin-top:0">Add operator</h2>
<form method="post" action="{{ route('central.operators.store') }}">
@csrf
<div class="field"><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
<div class="field"><label>Email</label><input name="email" type="email" value="{{ old('email') }}" required></div>
<div class="field"><label>Role</label><select name="role"><option value="operator">Operator</option><option value="head_operator">Head operator</option></select></div>
<div class="grid grid-2"><div class="field"><label>Password</label><input name="password" type="password" required></div><div class="field"><label>Confirm password</label><input name="password_confirmation" type="password" required></div></div>
<p class="muted">At least 10 characters with uppercase and lowercase letters and a number.</p>
<button class="btn">Create operator</button>
</form>
</div>
<div class="card"><h2 style="margin-top:0">Roles</h2><p><strong>Head operator</strong></p><p class="muted">Full central access, including creating operators, changing roles and resetting operator passwords.</p><p><strong>Operator</strong></p><p class="muted">Can use the central operational screens but cannot manage platform operators.</p></div>
</div>

<div class="card">
<h2 style="margin-top:0">Operator accounts</h2>
<div class="table-wrap"><table><thead><tr><th>Operator</th><th>Role</th><th>Status</th><th>Account</th></tr></thead><tbody>
@foreach($operators as $operator)
<tr>
<td><strong>{{ $operator->name }}</strong><div class="muted">{{ $operator->email }}</div></td>
<td><span class="badge">{{ $operator->role === 'head_operator' ? 'Head operator' : 'Operator' }}</span></td>
<td><span class="badge {{ $operator->is_active ? 'ok' : 'bad' }}">{{ $operator->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>
<details><summary class="btn secondary small">Manage</summary>
<div class="card" style="margin-top:10px;min-width:320px">
<form method="post" action="{{ route('central.operators.update',$operator) }}">@csrf @method('PUT')
<div class="field"><label>Name</label><input name="name" value="{{ $operator->name }}" required></div>
<div class="field"><label>Email</label><input name="email" type="email" value="{{ $operator->email }}" required></div>
<div class="grid grid-2"><div class="field"><label>Role</label><select name="role"><option value="operator" @selected($operator->role==='operator')>Operator</option><option value="head_operator" @selected($operator->role==='head_operator')>Head operator</option></select></div><div class="field"><label>Status</label><select name="is_active"><option value="1" @selected($operator->is_active)>Active</option><option value="0" @selected(!$operator->is_active)>Inactive</option></select></div></div>
<button class="btn small">Save account</button>
</form>
<hr style="border:0;border-top:1px solid #e2e8f0;margin:18px 0">
<form method="post" action="{{ route('central.operators.password',$operator) }}">@csrf @method('PUT')
<div class="grid grid-2"><div class="field"><label>New password</label><input name="password" type="password" required></div><div class="field"><label>Confirm</label><input name="password_confirmation" type="password" required></div></div>
<button class="btn secondary small">Change password</button>
</form>
</div>
</details>
</td>
</tr>
@endforeach
</tbody></table></div>
</div>
@endsection
