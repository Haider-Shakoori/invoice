@extends('tenant.layouts.workspace')

@section('title', 'Clients')
@section('heading', 'Clients')
@section('subheading', 'Maintain client contact details. Existing invoice snapshots are not rewritten when a client changes.')

@section('content')
@if(auth()->user()->hasTenantPermission('clients.manage'))
<div class="card">
    <h2>Add client</h2>
    <form method="post" action="{{ route('tenant.customers.store') }}">
        @csrf
        <div class="grid grid-3">
            <div class="field"><label>Name *</label><input name="name" required></div>
            <div class="field"><label>Company</label><input name="company_name"></div>
            <div class="field"><label>Client code</label><input name="code"></div>
            <div class="field"><label>Phone</label><input name="phone"></div>
            <div class="field"><label>WhatsApp</label><input name="whatsapp"></div>
            <div class="field"><label>Email</label><input type="email" name="email"></div>
            <div class="field"><label>City / Province</label><input name="city_province"></div>
            <div class="field"><label>Reference number</label><input name="reference_number"></div>
        </div>
        <div class="field"><label>Address</label><input name="address"></div>
        <div class="field"><label>Notes</label><textarea name="notes"></textarea></div>
        <button class="btn" type="submit">Add client</button>
    </form>
</div>
@endif

<div class="card">
    <form method="get" class="actions" style="margin-bottom:14px">
        <input name="q" value="{{ $search }}" placeholder="Search clients…" style="max-width:360px">
        <button class="btn secondary" type="submit">Search</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Client</th><th>Contact</th><th>Location</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->name }}</strong><div class="subtle">{{ $customer->company_name ?: $customer->code }}</div></td>
                    <td>{{ $customer->phone }}<div class="subtle">{{ $customer->email }}</div></td>
                    <td>{{ $customer->city_province }}<div class="subtle">{{ $customer->address }}</div></td>
                    <td><span class="badge {{ $customer->is_active ? 'ok' : '' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="right">
                        @if(auth()->user()->hasTenantPermission('clients.manage'))
                        <details>
                            <summary class="btn secondary small">Edit</summary>
                            <form method="post" action="{{ route('tenant.customers.update', $customer) }}" style="text-align:left;margin-top:12px;min-width:280px">
                                @csrf @method('PUT')
                                <div class="field"><label>Name</label><input name="name" value="{{ $customer->name }}" required></div>
                                <div class="field"><label>Company</label><input name="company_name" value="{{ $customer->company_name }}"></div>
                                <div class="field"><label>Code</label><input name="code" value="{{ $customer->code }}"></div>
                                <div class="field"><label>Phone</label><input name="phone" value="{{ $customer->phone }}"></div>
                                <div class="field"><label>Email</label><input type="email" name="email" value="{{ $customer->email }}"></div>
                                <div class="field"><label>Address</label><input name="address" value="{{ $customer->address }}"></div>
                                <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($customer->is_active) style="width:auto"> Active</label>
                                <button class="btn small" type="submit">Save</button>
                            </form>
                        </details>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No clients yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</div>
@endsection
