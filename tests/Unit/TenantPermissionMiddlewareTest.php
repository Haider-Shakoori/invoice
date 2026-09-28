<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureTenantPermission;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenantPermissionMiddlewareTest extends TestCase
{
    public function test_allowed_permission_reaches_the_route(): void
    {
        $user = new class extends User
        {
            public function hasTenantPermission(string $permission): bool
            {
                return $permission === 'pdf.export';
            }
        };

        $request = Request::create('/export', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureTenantPermission)->handle(
            $request,
            fn () => response('ok', 200),
            'pdf.export'
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_missing_permission_is_forbidden(): void
    {
        $user = new class extends User
        {
            public function hasTenantPermission(string $permission): bool
            {
                return false;
            }
        };

        $request = Request::create('/settings', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);

        (new EnsureTenantPermission)->handle(
            $request,
            fn () => response('ok', 200),
            'settings.manage'
        );
    }
}
