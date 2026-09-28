<?php

namespace Tests\Feature\Security;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteAuthorizationAuditTest extends TestCase
{
    /**
     * @param  array<int, string>  $required
     */
    #[DataProvider('protectedTenantRoutes')]
    public function test_sensitive_tenant_routes_keep_required_server_middleware(string $name, array $required): void
    {
        $route = app('router')->getRoutes()->getByName($name);

        $this->assertInstanceOf(Route::class, $route, "Missing route [{$name}].");

        $middleware = $route->gatherMiddleware();

        foreach ($required as $expected) {
            $this->assertContains($expected, $middleware, "Route [{$name}] is missing middleware [{$expected}].");
        }
    }

    /**
     * @return array<string, array{0:string,1:array<int,string>}>
     */
    public static function protectedTenantRoutes(): array
    {
        return [
            'company settings view' => [
                'tenant.onboarding.show',
                ['auth:web', 'subscription.access', 'tenant.permission:settings.manage'],
            ],
            'company settings update' => [
                'tenant.onboarding.update',
                ['auth:web', 'subscription.access', 'tenant.permission:settings.manage'],
            ],
            'invoice export' => [
                'tenant.invoices.export',
                ['auth:web', 'subscription.access', 'onboarding.complete', 'tenant.permission:pdf.export'],
            ],
            'export download' => [
                'tenant.exports.download',
                ['auth:web', 'subscription.access', 'onboarding.complete', 'tenant.permission:pdf.export'],
            ],
            'invoice delete' => [
                'tenant.invoices.destroy',
                ['auth:web', 'subscription.access', 'onboarding.complete', 'tenant.permission:drafts.delete'],
            ],
            'staff update' => [
                'tenant.staff.update',
                ['auth:web', 'subscription.access', 'onboarding.complete', 'tenant.permission:staff.manage'],
            ],
        ];
    }

    public function test_central_commercial_mutations_require_central_authentication(): void
    {
        foreach ([
            'central.commercial.activation-invoice',
            'central.commercial.renewal-invoice',
            'central.commercial.payments.store',
            'central.activation-requests.approve',
            'central.activation-requests.reject',
        ] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertContains('auth:central', $route->gatherMiddleware(), "Route [{$name}] lost central auth.");
        }
    }
}
