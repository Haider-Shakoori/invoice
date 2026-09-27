<?php
namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('tenant.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials=$request->validate([
            'email'=>['required','email'],
            'password'=>['required','string'],
        ]);

        $credentials['is_active']=true;

        if (! Auth::guard('web')->attempt($credentials,$request->boolean('remember'))) {
            return back()->withErrors(['email'=>__('auth.failed')])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tenant.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
