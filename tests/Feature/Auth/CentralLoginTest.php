<?php

namespace Tests\Feature\Auth;

use App\Models\Central\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CentralLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_operator_can_sign_in(): void
    {
        config()->set('tenancy.central_domains', ['invoice.test']);

        $admin = AdminUser::query()->create([
            'name' => 'Head Operator',
            'email' => 'operator@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'head_operator',
            'is_active' => true,
        ]);

        $this->post('http://invoice.test/login', [
            'email' => $admin->email,
            'password' => 'StrongPass123',
        ])->assertRedirect(route('central.admin'));

        $this->assertAuthenticatedAs($admin, 'central');
    }
}
