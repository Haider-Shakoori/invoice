@extends('tenant.layouts.workspace')

@section('title', 'Company Setup')
@section('heading', 'Company setup')
@section('subheading', 'Complete this once. You can resume later without losing what you entered.')

@section('content')
<form method="post" action="{{ route('tenant.onboarding.update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="step" value="3">

    <div class="card">
        <h2>Company identity</h2>
        <div class="grid grid-2">
            <div class="field"><label>Company name *</label><input name="display_name" value="{{ old('display_name', $profile->display_name) }}" required></div>
            <div class="field"><label>Secondary / local name</label><input name="secondary_name" value="{{ old('secondary_name', $profile->secondary_name) }}"></div>
            <div class="field"><label>Reference / registration number</label><input name="reference_number" value="{{ old('reference_number', $profile->reference_number) }}"></div>
            <div class="field"><label>City / Province</label><input name="city_province" value="{{ old('city_province', $profile->city_province) }}"></div>
        </div>
        <div class="field"><label>Address</label><input name="address" value="{{ old('address', $profile->address) }}"></div>
    </div>

    <div class="card">
        <h2>Contact</h2>
        <div class="grid grid-2">
            <div class="field"><label>Phone *</label><input name="phone" value="{{ old('phone', $profile->phone) }}" required></div>
            <div class="field"><label>WhatsApp</label><input name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}"></div>
            <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $profile->email) }}"></div>
            <div class="field"><label>Website</label><input type="url" name="website" value="{{ old('website', $profile->website) }}"></div>
        </div>
    </div>

    <div class="card">
        <h2>Invoice defaults</h2>
        <div class="grid grid-2">
            <div class="field">
                <label>Default language</label>
                <select name="default_locale">
                    <option value="en" @selected(old('default_locale', $profile->default_locale) === 'en')>English</option>
                    <option value="fa" @selected(old('default_locale', $profile->default_locale) === 'fa')>Dari</option>
                    <option value="ps" @selected(old('default_locale', $profile->default_locale) === 'ps')>Pashto</option>
                </select>
            </div>
            <div class="field"><label>Currency</label><select name="default_currency"><option value="AFN">AFN — Afghani</option></select></div>
        </div>
        <div class="actions">
            <button class="btn secondary" type="submit" name="complete" value="0">Save and continue later</button>
            <button class="btn" type="submit" name="complete" value="1">Finish setup</button>
        </div>
    </div>
</form>
@endsection
