<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\BusinessProfile;
use Illuminate\Http\RedirectResponse;

class WorkspaceController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        if (! BusinessProfile::query()->where('onboarding_completed', true)->exists()) {
            return redirect()->route('tenant.onboarding.show');
        }

        return redirect()->route('tenant.invoices.index');
    }
}
