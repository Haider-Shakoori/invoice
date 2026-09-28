<?php

namespace Tests\Feature\Operations;

use App\Services\Tenant\ChromiumPdfRenderer;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    public function test_central_readiness_endpoint_reports_ready_and_returns_request_id(): void
    {
        $this->mock(ChromiumPdfRenderer::class, function ($mock): void {
            $mock->shouldReceive('binary')->andReturn('/usr/bin/chromium');
        });

        $response = $this
            ->withHeader('X-Request-ID', 'release-ready-123456')
            ->get('http://invoice.test/health/ready');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', 'release-ready-123456')
            ->assertJsonPath('ready', true)
            ->assertJsonPath('checks.database.status', 'pass')
            ->assertJsonPath('checks.private_storage.status', 'pass')
            ->assertJsonPath('checks.cache.status', 'pass')
            ->assertJsonPath('checks.pdf_renderer.status', 'pass');
    }

    public function test_invalid_incoming_request_id_is_replaced(): void
    {
        $response = $this
            ->withHeader('X-Request-ID', 'bad id')
            ->get('http://invoice.test/');

        $response->assertOk();

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertNotSame('bad id', $requestId);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $requestId);
    }
}
