<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function edit(): View
    {
        return view('central.security');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:central'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $admin = $request->user('central');
        $admin->forceFill(['password' => $data['password'], 'remember_token' => null])->save();
        $request->session()->regenerate();

        return back()->with('status', 'Your platform administrator password has been changed.');
    }
}
