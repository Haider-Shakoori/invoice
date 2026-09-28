<?php

namespace Tests\Feature\Auth;

use App\Models\Central\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InactiveCentralLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_central_operator_cannot_sign_in(): void
    {
        config()->set('tenancy.central_domains', ['invoice.test']);

        $admin = AdminUser::query()->create([
            'name' => 'Disabled Operator',
            'email' => 'disabled@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'operator',
            'is_active' => false,
        ]);

        $this->from('http://invoice.test/login')
            ->post('http://invoice.test/login', [
                'email' => $admin->email,
                'password' => 'StrongPass123',
            ])
            ->assertRedirect('http://invoice.test/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('central');
    }
}
