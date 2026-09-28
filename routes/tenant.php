<?php

use App\Http\Controllers\Tenant\ActivationRequestController;
use App\Http\Controllers\Tenant\Auth\LoginController as TenantLoginController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\InvoiceDocumentController;
use App\Http\Controllers\Tenant\LocaleController;
use App\Http\Controllers\Tenant\OnboardingController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\SubscriptionStatusController;
use App\Http\Controllers\Tenant\TemplateGalleryController;
use App\Http\Controllers\Tenant\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', [LocaleController::class, 'update'])->name('tenant.locale.update');

Route::middleware('guest:web')->group(function (): void {
    Route::get('/login', [TenantLoginController::class, 'create'])->name('tenant.login');
    Route::post('/login', [TenantLoginController::class, 'store'])->name('tenant.login.store');
});

Route::middleware('auth:web')->group(function (): void {
    Route::get('/subscription-status', SubscriptionStatusController::class)->name('tenant.subscription.status');
    Route::post('/subscription/activation-request', [ActivationRequestController::class, 'store'])->name('tenant.subscription.activation-request');

    Route::middleware('subscription.access')->group(function (): void {
        Route::get('/', WorkspaceController::class)->name('tenant.home');

        Route::get('/onboarding', [OnboardingController::class, 'show'])
            ->middleware('tenant.permission:settings.manage')
            ->name('tenant.onboarding.show');
        Route::put('/onboarding', [OnboardingController::class, 'update'])
            ->middleware('tenant.permission:settings.manage')
            ->name('tenant.onboarding.update');

        Route::middleware('onboarding.complete')->group(function (): void {
            Route::get('/clients', [CustomerController::class, 'index'])
                ->middleware('tenant.permission:clients.view')
                ->name('tenant.customers.index');
            Route::post('/clients', [CustomerController::class, 'store'])
                ->middleware('tenant.permission:clients.manage')
                ->name('tenant.customers.store');
            Route::put('/clients/{customer}', [CustomerController::class, 'update'])
                ->middleware('tenant.permission:clients.manage')
                ->name('tenant.customers.update');
            Route::delete('/clients/{customer}', [CustomerController::class, 'destroy'])
                ->middleware('tenant.permission:clients.manage')
                ->name('tenant.customers.destroy');

            Route::get('/templates', [TemplateGalleryController::class, 'index'])
                ->middleware('tenant.permission:drafts.view')
                ->name('tenant.templates.index');

            Route::get('/invoices', [InvoiceController::class, 'index'])
                ->middleware('tenant.permission:drafts.view')
                ->name('tenant.invoices.index');
            Route::get('/invoices/create', [InvoiceController::class, 'create'])
                ->middleware('tenant.permission:drafts.manage')
                ->name('tenant.invoices.create');
            Route::post('/invoices', [InvoiceController::class, 'store'])
                ->middleware('tenant.permission:drafts.manage')
                ->name('tenant.invoices.store');
            Route::get('/invoices/{invoice}/preview', [InvoiceDocumentController::class, 'preview'])
                ->middleware('tenant.permission:drafts.view')
                ->name('tenant.invoices.preview');
            Route::post('/invoices/{invoice}/export', [InvoiceDocumentController::class, 'export'])
                ->middleware('tenant.permission:pdf.export')
                ->name('tenant.invoices.export');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
                ->middleware('tenant.permission:drafts.view')
                ->name('tenant.invoices.show');
            Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])
                ->middleware('tenant.permission:drafts.manage')
                ->name('tenant.invoices.edit');
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])
                ->middleware('tenant.permission:drafts.manage')
                ->name('tenant.invoices.update');
            Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])
                ->middleware('tenant.permission:drafts.manage')
                ->name('tenant.invoices.duplicate');
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])
                ->middleware('tenant.permission:drafts.delete')
                ->name('tenant.invoices.destroy');

            Route::get('/exports/{documentExport}/download', [InvoiceDocumentController::class, 'download'])
                ->middleware('tenant.permission:pdf.export')
                ->name('tenant.exports.download');

            Route::get('/staff', [StaffController::class, 'index'])
                ->middleware('tenant.permission:staff.manage')
                ->name('tenant.staff.index');
            Route::post('/staff', [StaffController::class, 'store'])
                ->middleware('tenant.permission:staff.manage')
                ->name('tenant.staff.store');
            Route::put('/staff/{staff}', [StaffController::class, 'update'])
                ->middleware('tenant.permission:staff.manage')
                ->name('tenant.staff.update');
        });
    });

    Route::post('/logout', [TenantLoginController::class, 'destroy'])->name('tenant.logout');
});
