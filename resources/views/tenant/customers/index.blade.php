@extends('tenant.layouts.workspace')

@section('title', __('ui.clients.title'))
@section('heading', __('ui.clients.title'))
@section('subheading', __('ui.clients.subtitle'))

@section('content')
@if(auth()->user()->hasTenantPermission('clients.manage'))
<div class="card">
    <h2>{{ __('ui.clients.add') }}</h2>
    <form method="post" action="{{ route('tenant.customers.store') }}">
        @csrf
        <div class="grid grid-3">
            <div class="field"><label>{{ __('ui.common.name') }} *</label><input name="name" required></div>
            <div class="field"><label>{{ __('ui.clients.company') }}</label><input name="company_name"></div>
            <div class="field"><label>{{ __('ui.clients.code') }}</label><input name="code"></div>
            <div class="field"><label>{{ __('ui.common.phone') }}</label><input name="phone"></div>
            <div class="field"><label>{{ __('ui.clients.whatsapp') }}</label><input name="whatsapp"></div>
            <div class="field"><label>{{ __('ui.common.email') }}</label><input type="email" name="email"></div>
            <div class="field"><label>{{ __('ui.clients.city_province') }}</label><input name="city_province"></div>
            <div class="field"><label>{{ __('ui.clients.reference_number') }}</label><input name="reference_number"></div>
        </div>
        <div class="field"><label>{{ __('ui.common.address') }}</label><input name="address"></div>
        <div class="field"><label>{{ __('ui.clients.notes') }}</label><textarea name="notes"></textarea></div>
        <button class="btn" type="submit">{{ __('ui.clients.add') }}</button>
    </form>
</div>
@endif

<div class="card">
    <form method="get" class="actions" style="margin-bottom:14px">
        <input name="q" value="{{ $search }}" placeholder="{{ __('ui.clients.search_placeholder') }}" style="max-width:360px">
        <button class="btn secondary" type="submit">{{ __('ui.common.search') }}</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>{{ __('ui.clients.client') }}</th><th>{{ __('ui.clients.contact') }}</th><th>{{ __('ui.clients.location') }}</th><th>{{ __('ui.common.status') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->name }}</strong><div class="subtle">{{ $customer->company_name ?: $customer->code }}</div></td>
                    <td><span class="numeric">{{ $customer->phone }}</span><div class="subtle">{{ $customer->email }}</div></td>
                    <td>{{ $customer->city_province }}<div class="subtle">{{ $customer->address }}</div></td>
                    <td><span class="badge {{ $customer->is_active ? 'ok' : '' }}">{{ $customer->is_active ? __('ui.common.active') : __('ui.common.inactive') }}</span></td>
                    <td class="right">
                        @if(auth()->user()->hasTenantPermission('clients.manage'))
                        <details>
                            <summary class="btn secondary small">{{ __('ui.common.edit') }}</summary>
                            <form method="post" action="{{ route('tenant.customers.update', $customer) }}" style="text-align:start;margin-top:12px;min-width:280px">
                                @csrf @method('PUT')
                                <div class="field"><label>{{ __('ui.common.name') }}</label><input name="name" value="{{ $customer->name }}" required></div>
                                <div class="field"><label>{{ __('ui.clients.company') }}</label><input name="company_name" value="{{ $customer->company_name }}"></div>
                                <div class="field"><label>{{ __('ui.clients.code') }}</label><input name="code" value="{{ $customer->code }}"></div>
                                <div class="field"><label>{{ __('ui.common.phone') }}</label><input name="phone" value="{{ $customer->phone }}"></div>
                                <div class="field"><label>{{ __('ui.common.email') }}</label><input type="email" name="email" value="{{ $customer->email }}"></div>
                                <div class="field"><label>{{ __('ui.common.address') }}</label><input name="address" value="{{ $customer->address }}"></div>
                                <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($customer->is_active) style="width:auto"> {{ __('ui.common.active') }}</label>
                                <button class="btn small" type="submit">{{ __('ui.common.save') }}</button>
                            </form>
                        </details>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">{{ __('ui.clients.no_clients') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</div>
@endsection
