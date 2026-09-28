<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Rules\AvailableTenantSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AvailableTenantSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserved_subdomain_is_rejected(): void
    {
        config()->set('invoice.reserved_subdomains', ['admin', 'api']);

        $validator = Validator::make(
            ['slug' => 'admin'],
            ['slug' => [new AvailableTenantSlug]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_existing_slug_is_rejected(): void
    {
        Tenant::query()->create([
            'id' => 'tenant-1',
            'slug' => 'acme',
            'provisioning_status' => 'pending',
        ]);

        $validator = Validator::make(
            ['slug' => 'acme'],
            ['slug' => [new AvailableTenantSlug]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_unused_slug_is_accepted(): void
    {
        config()->set('invoice.reserved_subdomains', []);

        $validator = Validator::make(
            ['slug' => 'kabul-traders'],
            ['slug' => [new AvailableTenantSlug]]
        );

        $this->assertFalse($validator->fails());
    }
}
