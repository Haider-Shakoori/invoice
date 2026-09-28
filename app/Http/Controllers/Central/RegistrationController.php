<?php

namespace App\Http\Controllers\Central;

use App\Actions\Tenancy\ProvisionTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\RegisterBusinessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('central.register');
    }

    public function store(RegisterBusinessRequest $request, ProvisionTenant $provisionTenant): RedirectResponse
    {
        $tenant = $provisionTenant->handle($request->validated());

        return redirect()->away('https://'.$tenant->domains()->firstOrFail()->domain.'/login');
    }
}
