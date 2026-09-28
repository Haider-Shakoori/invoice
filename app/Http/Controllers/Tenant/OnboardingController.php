<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $profile = BusinessProfile::query()->firstOrCreate([], [
            'default_locale' => 'en',
            'default_currency' => 'AFN',
            'onboarding_step' => 1,
            'onboarding_completed' => false,
        ]);

        if ($profile->onboarding_completed) {
            return redirect()->route('tenant.invoices.index');
        }

        return view('tenant.onboarding', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = BusinessProfile::query()->firstOrCreate([]);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'secondary_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city_province' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'default_locale' => ['required', Rule::in(config('invoice.locales'))],
            'default_currency' => ['required', 'in:AFN'],
            'step' => ['nullable', 'integer', 'between:1,3'],
            'complete' => ['nullable', 'boolean'],
        ]);

        $profile->fill(collect($data)->except(['step', 'complete'])->all());
        $profile->onboarding_step = max(
            $profile->onboarding_step ?? 1,
            (int) ($data['step'] ?? 3),
        );

        if ($request->boolean('complete')) {
            $profile->onboarding_step = 3;
            $profile->onboarding_completed = true;
        }

        $profile->save();

        if ($profile->onboarding_completed) {
            return redirect()->route('tenant.invoices.index')->with('status', 'Company setup completed.');
        }

        return back()->with('status', 'Company setup saved.');
    }
}
