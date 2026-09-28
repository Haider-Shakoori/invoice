<?php

use App\Http\Middleware\EnsureOnboardingCompleted;
use App\Http\Middleware\EnsureSubscriptionAccess;
use App\Http\Middleware\EnsureTenantPermission;
use App\Http\Middleware\RequestContext;
use App\Http\Middleware\SetTenantLocale;
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
            'subscription.access' => EnsureSubscriptionAccess::class,
            'onboarding.complete' => EnsureOnboardingCompleted::class,
            'tenant.locale' => SetTenantLocale::class,
            'request.context' => RequestContext::class,
        ]);

        $middleware->redirectGuestsTo(fn () => '/login');

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
            return response('Not Found', 404);
        });
    })
    ->create();
