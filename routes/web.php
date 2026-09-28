<?php

use App\Http\Controllers\Central\Auth\LoginController as CentralLoginController;
use App\Http\Controllers\Central\RegistrationController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', fn () => response()->json(['product' => 'Invoice Drafts SaaS', 'scope' => 'central', 'status' => 'ok']));

        Route::middleware('guest:central')->group(function (): void {
            Route::get('/login', [CentralLoginController::class, 'create'])->name('central.login');
            Route::post('/login', [CentralLoginController::class, 'store'])->name('central.login.store');
            Route::get('/register', [RegistrationController::class, 'create'])->name('central.register');
            Route::post('/register', [RegistrationController::class, 'store'])->name('central.register.store');
        });

        Route::middleware('auth:central')->group(function (): void {
            Route::get('/admin', fn () => response()->json(['message' => 'Central operator interface foundation']))->name('central.admin');
            Route::post('/logout', [CentralLoginController::class, 'destroy'])->name('central.logout');
        });
    });
}

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(base_path('routes/tenant.php'));
