@extends('tenant.layouts.workspace')

@section('title', __('ui.staff.title'))
@section('heading', __('ui.staff.title'))
@section('subheading', __('ui.staff.subtitle'))

@section('content')
<div class="card">
    <h2>{{ __('ui.staff.add') }}</h2>
    <form method="post" action="{{ route('tenant.staff.store') }}">
        @csrf
        <div class="grid grid-3">
            <div class="field"><label>{{ __('ui.common.name') }}</label><input name="name" required></div>
            <div class="field"><label>{{ __('ui.common.email') }}</label><input type="email" name="email" required></div>
            <div class="field"><label>{{ __('ui.common.role') }}</label><select name="role">@foreach($roles as $role)<option value="{{ $role->key }}">{{ $role->name }}</option>@endforeach</select></div>
            <div class="field"><label>{{ __('ui.common.password') }}</label><input type="password" name="password" required></div>
            <div class="field"><label>{{ __('ui.common.confirm_password') }}</label><input type="password" name="password_confirmation" required></div>
        </div>
        <button class="btn" type="submit">{{ __('ui.staff.create') }}</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>{{ __('ui.common.name') }}</th><th>{{ __('ui.common.role') }}</th><th>{{ __('ui.common.status') }}</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong><div class="subtle">{{ $user->email }}</div></td>
                    <td>{{ $user->roles->pluck('name')->join(', ') ?: $user->role }}</td>
                    <td><span class="badge {{ $user->is_active ? 'ok' : '' }}">{{ $user->is_active ? __('ui.common.active') : __('ui.common.inactive') }}</span></td>
                    <td class="right">
                        @if(!$user->roles->contains('key', 'owner'))
                        <details>
                            <summary class="btn secondary small">{{ __('ui.common.edit') }}</summary>
                            <form method="post" action="{{ route('tenant.staff.update', $user) }}" style="text-align:start;margin-top:12px;min-width:300px">
                                @csrf @method('PUT')
                                <div class="field"><label>{{ __('ui.common.name') }}</label><input name="name" value="{{ $user->name }}" required></div>
                                <div class="field"><label>{{ __('ui.common.email') }}</label><input type="email" name="email" value="{{ $user->email }}" required></div>
                                <div class="field"><label>{{ __('ui.common.role') }}</label><select name="role">@foreach($roles as $role)<option value="{{ $role->key }}" @selected($user->role === $role->key)>{{ $role->name }}</option>@endforeach</select></div>
                                <div class="field"><label>{{ __('ui.staff.new_password') }}</label><input type="password" name="password"></div>
                                <div class="field"><label>{{ __('ui.staff.confirm_new_password') }}</label><input type="password" name="password_confirmation"></div>
                                <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($user->is_active) style="width:auto"> {{ __('ui.common.active') }}</label>
                                <div style="margin-top:10px"><button class="btn small" type="submit">{{ __('ui.common.save') }}</button></div>
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
