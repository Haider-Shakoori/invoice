@extends('tenant.layouts.workspace')

@section('title', 'Staff')
@section('heading', 'Staff & permissions')
@section('subheading', 'Roles are enforced server-side, not only hidden in the interface.')

@section('content')
<div class="card">
    <h2>Add staff account</h2>
    <form method="post" action="{{ route('tenant.staff.store') }}">
        @csrf
        <div class="grid grid-3">
            <div class="field"><label>Name</label><input name="name" required></div>
            <div class="field"><label>Email</label><input type="email" name="email" required></div>
            <div class="field"><label>Role</label><select name="role">@foreach($roles as $role)<option value="{{ $role->key }}">{{ $role->name }}</option>@endforeach</select></div>
            <div class="field"><label>Password</label><input type="password" name="password" required></div>
            <div class="field"><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
        </div>
        <button class="btn" type="submit">Create account</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong><div class="subtle">{{ $user->email }}</div></td>
                    <td>{{ $user->roles->pluck('name')->join(', ') ?: $user->role }}</td>
                    <td><span class="badge {{ $user->is_active ? 'ok' : '' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="right">
                        @if(!$user->roles->contains('key', 'owner'))
                        <details>
                            <summary class="btn secondary small">Edit</summary>
                            <form method="post" action="{{ route('tenant.staff.update', $user) }}" style="text-align:left;margin-top:12px;min-width:300px">
                                @csrf @method('PUT')
                                <div class="field"><label>Name</label><input name="name" value="{{ $user->name }}" required></div>
                                <div class="field"><label>Email</label><input type="email" name="email" value="{{ $user->email }}" required></div>
                                <div class="field"><label>Role</label><select name="role">@foreach($roles as $role)<option value="{{ $role->key }}" @selected($user->role === $role->key)>{{ $role->name }}</option>@endforeach</select></div>
                                <div class="field"><label>New password (optional)</label><input type="password" name="password"></div>
                                <div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation"></div>
                                <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($user->is_active) style="width:auto"> Active</label>
                                <div style="margin-top:10px"><button class="btn small" type="submit">Save</button></div>
                            </form>
                        </details>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
