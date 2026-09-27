<?php

namespace Tests\Feature\Tenancy;

use App\Actions\Tenancy\ProvisionTenant;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDatabaseIsolationMysqlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! env('RUN_TENANCY_MYSQL_TESTS')) {
            $this->markTestSkipped('MySQL tenancy integration tests are disabled.');
        }
    }

    public function test_two_tenants_cannot_read_each_others_users(): void
    {
        config()->set('tenancy.tenant_base_domain','invoice.test');

        $provision=app(ProvisionTenant::class);

        $tenantA=$provision->handle([
            'company_name'=>'Tenant Alpha',
            'owner_name'=>'Alpha Owner',
            'owner_email'=>'owner-alpha@example.test',
            'owner_phone'=>null,
            'slug'=>'tenant-alpha',
            'password'=>'StrongPass123',
        ]);

        $tenantB=$provision->handle([
            'company_name'=>'Tenant Beta',
            'owner_name'=>'Beta Owner',
            'owner_email'=>'owner-beta@example.test',
            'owner_phone'=>null,
            'slug'=>'tenant-beta',
            'password'=>'StrongPass123',
        ]);

        $tenantA->run(function (): void {
            User::query()->create([
                'name'=>'Alpha Staff',
                'email'=>'alpha-staff@example.test',
                'password'=>'StrongPass123',
                'role'=>'staff',
                'is_active'=>true,
            ]);

            $this->assertTrue(User::query()->where('email','alpha-staff@example.test')->exists());
            $this->assertFalse(User::query()->where('email','beta-staff@example.test')->exists());
        });

        $tenantB->run(function (): void {
            User::query()->create([
                'name'=>'Beta Staff',
                'email'=>'beta-staff@example.test',
                'password'=>'StrongPass123',
                'role'=>'staff',
                'is_active'=>true,
            ]);

            $this->assertTrue(User::query()->where('email','beta-staff@example.test')->exists());
            $this->assertFalse(User::query()->where('email','alpha-staff@example.test')->exists());
        });

        $tenantA->run(function (): void {
            $this->assertTrue(User::query()->where('email','alpha-staff@example.test')->exists());
            $this->assertFalse(User::query()->where('email','beta-staff@example.test')->exists());
        });
    }
}
