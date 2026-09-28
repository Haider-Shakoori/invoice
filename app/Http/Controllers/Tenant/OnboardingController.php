<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'default_currency' => ['required', Rule::in(['AFN', 'USD'])],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'stamp' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'step' => ['nullable', 'integer', 'between:1,3'],
            'complete' => ['nullable', 'boolean'],
        ]);

        $profile->fill(collect($data)->except([
            'step',
            'complete',
            'logo',
            'signature',
            'stamp',
        ])->all());

        foreach ([
            'logo' => 'logo_path',
            'signature' => 'signature_path',
            'stamp' => 'stamp_path',
        ] as $input => $column) {
            if ($request->hasFile($input)) {
                if ($profile->{$column}) {
                    Storage::disk('local')->delete($profile->{$column});
                }

                $profile->{$column} = $this->storeBrandAsset($request->file($input), $input);
            }
        }

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
            return redirect()->route('tenant.invoices.index')->with('status', __('ui.flash.company_completed'));
        }

        return back()->with('status', __('ui.flash.company_saved'));
    }

    private function storeBrandAsset(UploadedFile $file, string $kind): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');

        return $file->storeAs(
            'branding',
            $kind.'.'.$extension,
            'local',
        );
    }
}
