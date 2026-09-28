<?php

use App\Http\Middleware\EnsureTenantPermission;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
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

        // Both central and tenant login endpoints live at /login on their own host.
        // Returning a relative path keeps authentication redirects on the current domain.
        $middleware->redirectGuestsTo(fn () => '/login');

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
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $exception, Request $request) {
            return response('Not Found',404);
        });
    })
    ->create();
