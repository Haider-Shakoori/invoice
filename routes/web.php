<?php
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', fn()=>response()->json(['product'=>'Invoice Drafts SaaS','scope'=>'central','status'=>'ok']));
        Route::get('/admin', fn()=>response()->json(['message'=>'Central operator interface foundation']))->name('central.admin');
    });
}

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(base_path('routes/tenant.php'));
