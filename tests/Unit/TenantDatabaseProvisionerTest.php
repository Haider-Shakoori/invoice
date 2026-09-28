<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Services\Tenancy\TenantDatabaseProvisioner;
use Tests\TestCase;

class TenantDatabaseProvisionerTest extends TestCase
{
    public function test_cpanel_database_name_is_safe_bounded_and_deterministic(): void
    {
        config(['tenancy.database.prefix' => 'businessos_inv_']);

        $tenant = new Tenant([
            'id' => '11111111-2222-3333-4444-555555555555',
            'slug' => 'very-long-company-name-with-many-segments-and-extra-characters-for-cpanel',
        ]);

        $service = app(TenantDatabaseProvisioner::class);
        $first = $service->databaseName($tenant);
        $second = $service->databaseName($tenant);

        $this->assertSame($first, $second);
        $this->assertStringStartsWith('businessos_inv_', $first);
        $this->assertLessThanOrEqual(63, strlen($first));
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $first);
    }
}
