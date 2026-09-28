<?php

namespace Tests\Feature\Central;

use App\Models\Central\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CentralUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_central_pages_render_html(): void
    {
        $this->get('/')->assertOk()->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Create polished invoices in minutes.');

        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->get('/register')->assertOk()->assertSee('Start your 7-day trial');
    }

    public function test_authenticated_platform_pages_render_web_ui_instead_of_json(): void
    {
        $admin = AdminUser::query()->create([
            'name' => 'UI Test Operator',
            'email' => 'ui-test@example.test',
            'password' => Hash::make('TestPassword123'),
            'role' => 'head_operator',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'central');

        $this->get('/admin')->assertRedirect(route('central.commercial'));
        $this->get('/admin/commercial')->assertOk()->assertSee('Platform overview');
        $this->get('/admin/activation-requests')->assertOk()->assertSee('Activation requests');
        $this->get('/admin/commissions')->assertOk()->assertSee('Seller commissions');
    }
}
