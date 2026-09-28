<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnknownTenantHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_tenant_host_fails_closed(): void
    {
        config()->set('tenancy.central_domains', ['invoice.test']);
        config()->set('tenancy.tenant_base_domain', 'invoice.test');
        $this->get('http://does-not-exist.invoice.test/')->assertNotFound();
    }
}
