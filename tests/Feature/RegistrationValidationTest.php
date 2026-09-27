<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_reserved_subdomain_before_provisioning(): void
    {
        config()->set('tenancy.central_domains',['invoice.test']);
        config()->set('invoice.reserved_subdomains',['admin']);

        $this->from('http://invoice.test/register')
            ->post('http://invoice.test/register',[
                'company_name'=>'Kabul Trading',
                'owner_name'=>'Owner',
                'owner_email'=>'owner@example.test',
                'slug'=>'admin',
                'password'=>'StrongPass123',
                'password_confirmation'=>'StrongPass123',
            ])
            ->assertRedirect('http://invoice.test/register')
            ->assertSessionHasErrors('slug');
    }

    public function test_registration_requires_confirmed_strong_password(): void
    {
        config()->set('tenancy.central_domains',['invoice.test']);

        $this->from('http://invoice.test/register')
            ->post('http://invoice.test/register',[
                'company_name'=>'Kabul Trading',
                'owner_name'=>'Owner',
                'owner_email'=>'owner@example.test',
                'slug'=>'kabul-trading',
                'password'=>'weak',
                'password_confirmation'=>'different',
            ])
            ->assertSessionHasErrors('password');
    }
}
