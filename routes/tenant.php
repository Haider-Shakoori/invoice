<?php

use App\Http\Controllers\Tenant\ActivationRequestController;
use App\Http\Controllers\Tenant\Auth\LoginController as TenantLoginController;
use App\Http\Controllers\Tenant\SubscriptionStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:web')->group(function (): void {
    Route::get('/login', [TenantLoginController::class, 'create'])->name('tenant.login');
    Route::post('/login', [TenantLoginController::class, 'store'])->name('tenant.login.store');
});

Route::middleware('auth:web')->group(function (): void {
    Route::get('/subscription-status', SubscriptionStatusController::class)->name('tenant.subscription.status');
    Route::post('/subscription/activation-request', [ActivationRequestController::class, 'store'])->name('tenant.subscription.activation-request');

    Route::middleware('subscription.access')->group(function (): void {
        Route::get('/', fn () => response()->json([
            'product' => 'Invoice Drafts SaaS',
            'scope' => 'tenant',
            'tenant' => tenant('id'),
        ]))->name('tenant.home');
    });

    Route::post('/logout', [TenantLoginController::class, 'destroy'])->name('tenant.logout');
});
