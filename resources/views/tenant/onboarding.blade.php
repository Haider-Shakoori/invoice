@extends('tenant.layouts.workspace')

@section('title', __('ui.onboarding.title'))
@section('heading', __('ui.onboarding.title'))
@section('subheading', __('ui.onboarding.subtitle'))

@section('content')
<form method="post" action="{{ route('tenant.onboarding.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="step" value="3">

    <div class="card">
        <h2>{{ __('ui.onboarding.identity') }}</h2>
        <div class="grid grid-2">
            <div class="field"><label>{{ __('ui.onboarding.company_name') }} *</label><input name="display_name" value="{{ old('display_name', $profile->display_name) }}" required></div>
            <div class="field"><label>{{ __('ui.onboarding.secondary_name') }}</label><input name="secondary_name" value="{{ old('secondary_name', $profile->secondary_name) }}"></div>
            <div class="field"><label>{{ __('ui.onboarding.reference_number') }}</label><input name="reference_number" value="{{ old('reference_number', $profile->reference_number) }}"></div>
            <div class="field"><label>{{ __('ui.onboarding.city_province') }}</label><input name="city_province" value="{{ old('city_province', $profile->city_province) }}"></div>
        </div>
        <div class="field"><label>{{ __('ui.common.address') }}</label><input name="address" value="{{ old('address', $profile->address) }}"></div>
    </div>

    <div class="card">
        <h2>{{ __('ui.onboarding.contact') }}</h2>
        <div class="grid grid-2">
            <div class="field"><label>{{ __('ui.common.phone') }} *</label><input name="phone" value="{{ old('phone', $profile->phone) }}" required></div>
            <div class="field"><label>WhatsApp</label><input name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}"></div>
            <div class="field"><label>{{ __('ui.common.email') }}</label><input type="email" name="email" value="{{ old('email', $profile->email) }}"></div>
            <div class="field"><label>{{ __('ui.onboarding.website') }}</label><input type="url" name="website" value="{{ old('website', $profile->website) }}"></div>
        </div>
    </div>

    <div class="card">
        <h2>{{ __('ui.onboarding.branding') }}</h2>
        <div class="grid grid-3">
            <div class="field"><label>{{ __('ui.onboarding.logo') }}</label><input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp">@if($profile->logo_path)<div class="subtle">{{ basename($profile->logo_path) }}</div>@endif</div>
            <div class="field"><label>{{ __('ui.onboarding.signature') }}</label><input type="file" name="signature" accept=".png,.jpg,.jpeg,.webp">@if($profile->signature_path)<div class="subtle">{{ basename($profile->signature_path) }}</div>@endif</div>
            <div class="field"><label>{{ __('ui.onboarding.stamp') }}</label><input type="file" name="stamp" accept=".png,.jpg,.jpeg,.webp">@if($profile->stamp_path)<div class="subtle">{{ basename($profile->stamp_path) }}</div>@endif</div>
        </div>
    </div>

    <div class="card">
        <h2>{{ __('ui.onboarding.invoice_defaults') }}</h2>
        <div class="grid grid-2">
            <div class="field">
                <label>{{ __('ui.onboarding.default_language') }}</label>
                <select name="default_locale">
                    @foreach(config('invoice.locales') as $locale)
                        <option value="{{ $locale }}" @selected(old('default_locale', $profile->default_locale) === $locale)>{{ __('ui.languages.'.$locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>{{ __('ui.onboarding.currency') }}</label>
                <select name="default_currency">
                    <option value="AFN" @selected(old('default_currency', $profile->default_currency) === 'AFN')>AFN — Afghani</option>
                    <option value="USD" @selected(old('default_currency', $profile->default_currency) === 'USD')>USD — US Dollar</option>
                </select>
            </div>
        </div>
        <div class="actions">
            <button class="btn secondary" type="submit" name="complete" value="0">{{ __('ui.onboarding.save_later') }}</button>
            <button class="btn" type="submit" name="complete" value="1">{{ __('ui.onboarding.finish') }}</button>
        </div>
    </div>
</form>
@endsection
