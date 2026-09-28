<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class OperatorController extends Controller
{
    private function authorizeHead(Request $request): void
    {
        abort_unless($request->user('central')?->role === 'head_operator', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeHead($request);

        return view('central.operators.index', [
            'operators' => AdminUser::query()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeHead($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:admin_users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            'role' => ['required', Rule::in(['head_operator', 'operator'])],
        ]);

        AdminUser::query()->create($data + ['is_active' => true]);

        return back()->with('status', 'Platform operator created.');
    }

    public function update(Request $request, AdminUser $operator): RedirectResponse
    {
        $this->authorizeHead($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('admin_users', 'email')->ignore($operator->id)],
            'role' => ['required', Rule::in(['head_operator', 'operator'])],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($operator->is($request->user('central')) && (! $data['is_active'] || $data['role'] !== 'head_operator')) {
            return back()->withErrors(['operator' => 'You cannot deactivate or demote your own head operator account.']);
        }

        $operator->update($data);

        return back()->with('status', 'Platform operator updated.');
    }

    public function resetPassword(Request $request, AdminUser $operator): RedirectResponse
    {
        $this->authorizeHead($request);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $operator->forceFill(['password' => $data['password'], 'remember_token' => null])->save();

        return back()->with('status', 'Operator password changed.');
    }
}
