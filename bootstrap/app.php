<?php

use App\Http\Middleware\EnsureTenantPermission;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.permission' => EnsureTenantPermission::class,
        ]);

        // Tenancy identification must run before authentication so unknown hosts
        // fail closed instead of being redirected by Laravel's auth middleware.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: InitializeTenancyByDomain::class,
        );

        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: PreventAccessFromCentralDomains::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
