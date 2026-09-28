<?php

namespace Tests\Feature\Central;

use Tests\TestCase;

class CentralCanonicalHostTest extends TestCase
{
    public function test_www_central_host_redirects_to_canonical_registration_url(): void
    {
        config([
            'tenancy.central_domains' => ['invoice.test'],
            'tenancy.tenant_base_domain' => 'invoice.test',
        ]);

        $this->get('http://www.invoice.test/register?source=home')
            ->assertStatus(301)
            ->assertRedirect('https://invoice.test/register?source=home');
    }
}
