<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('tenant.staff.index', [
            'users' => User::query()->with('roles')->orderBy('name')->get(),
            'roles' => Role::query()->whereIn('key', ['admin', 'staff', 'read-only'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'staff', 'read-only'])],
        ]);

        $role = Role::query()->where('key', $data['role'])->firstOrFail();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role->key,
            'is_active' => true,
        ]);

        $user->roles()->sync([$role->id]);

        return back()->with('status', __('ui.flash.staff_created'));
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_if($staff->roles()->where('key', 'owner')->exists(), 422, 'The owner account cannot be changed from staff management.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'role' => ['required', Rule::in(['admin', 'staff', 'read-only'])],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
        ]);

        abort_if($staff->is($request->user()) && ! $request->boolean('is_active'), 422, 'You cannot deactivate your own account.');

        $role = Role::query()->where('key', $data['role'])->firstOrFail();

        $staff->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $role->key,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $staff->password = Hash::make($data['password']);
        }

        $staff->save();
        $staff->roles()->sync([$role->id]);

        return back()->with('status', __('ui.flash.staff_updated'));
    }
}
