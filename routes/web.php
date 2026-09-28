<?php

use App\Http\Controllers\Central\ActivationRequestController;
use App\Http\Controllers\Central\Auth\LoginController as CentralLoginController;
use App\Http\Controllers\Central\CommercialController;
use App\Http\Controllers\Central\CommercialInvoiceController;
use App\Http\Controllers\Central\CommissionController;
use App\Http\Controllers\Central\ReadinessController;
use App\Http\Controllers\Central\RegistrationController;
use App\Http\Controllers\Central\SellerController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::domain('www.'.config('tenancy.tenant_base_domain'))->group(function (): void {
    Route::any('/{path?}', function (?string $path = null) {
        $target = 'https://'.config('tenancy.tenant_base_domain').'/'.ltrim((string) $path, '/');

        if (request()->getQueryString()) {
            $target .= '?'.request()->getQueryString();
        }

        return redirect()->away($target, 301);
    })->where('path', '.*');
});

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->middleware('request.context')->group(function (): void {
        Route::view('/', 'central.landing')->name('central.home');
        Route::get('/health/ready', ReadinessController::class)->name('central.health.ready');

        Route::middleware('guest:central')->group(function (): void {
            Route::get('/login', [CentralLoginController::class, 'create'])->name('central.login');
            Route::post('/login', [CentralLoginController::class, 'store'])->name('central.login.store');
            Route::get('/register', [RegistrationController::class, 'create'])->name('central.register');
            Route::post('/register', [RegistrationController::class, 'store'])->name('central.register.store');
        });

        Route::middleware('auth:central')->group(function (): void {
            Route::get('/admin', fn () => redirect()->route('central.commercial'))->name('central.admin');
            Route::get('/admin/commercial', [CommercialController::class, 'index'])->name('central.commercial');
            Route::post('/admin/businesses/{business}/activation-invoice', [CommercialInvoiceController::class, 'activation'])->name('central.commercial.activation-invoice');
            Route::post('/admin/businesses/{business}/renewal-invoice', [CommercialInvoiceController::class, 'renewal'])->name('central.commercial.renewal-invoice');
            Route::post('/admin/platform-invoices/{platformInvoice}/payments', [CommercialInvoiceController::class, 'recordPayment'])->name('central.commercial.payments.store');
            Route::post('/admin/sellers', [SellerController::class, 'store'])->name('central.sellers.store');
            Route::get('/admin/commissions', [CommissionController::class, 'index'])->name('central.commissions.index');
            Route::post('/admin/commissions/{sellerCommission}/paid', [CommissionController::class, 'markPaid'])->name('central.commissions.paid');
            Route::get('/admin/activation-requests', [ActivationRequestController::class, 'index'])->name('central.activation-requests.index');
            Route::post('/admin/activation-requests/{activationRequest}/approve', [ActivationRequestController::class, 'approve'])->name('central.activation-requests.approve');
            Route::post('/admin/activation-requests/{activationRequest}/reject', [ActivationRequestController::class, 'reject'])->name('central.activation-requests.reject');
            Route::post('/logout', [CentralLoginController::class, 'destroy'])->name('central.logout');
        });
    });
}

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'tenant.locale',
    'request.context',
])->group(base_path('routes/tenant.php'));
