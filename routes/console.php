<?php

use App\Enums\PlatformInvoiceStatus;
use App\Models\Central\Business;
use App\Models\Central\PlatformInvoice;
use App\Services\Commercial\StartTrialSubscription;
use App\Services\Commercial\SubscriptionStatusSynchronizer;
use App\Services\Operations\BackupManager;
use App\Services\Operations\ReleaseReadiness;
use App\Services\Operations\TenantMigrationRunner;
use Database\Seeders\HeadOperatorSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('invoice:about', function (): void {
    $this->info('Invoice Drafts SaaS');
    $this->line('Central commercial DB + isolated database per tenant.');
})->purpose('Display the Invoice Drafts product boundary');

Artisan::command('invoice:bootstrap-operator', function (): int {
    $this->call(HeadOperatorSeeder::class);

    $this->info('Central head operator is ready.');

    return self::SUCCESS;
})->purpose('Create or update the central head operator from CENTRAL_ADMIN_* environment values');

Artisan::command('invoice:sync-subscriptions', function (): int {
    $created = 0;

    Business::query()->chunkById(100, function ($businesses) use (&$created): void {
        foreach ($businesses as $business) {
            if ($business->subscription()->exists()) {
                continue;
            }

            app(StartTrialSubscription::class)->handle(
                $business,
                startedAt: $business->created_at,
                endsAt: $business->trial_ends_at,
            );

            $created++;
        }
    });

    $changed = app(SubscriptionStatusSynchronizer::class)->handle();

    $overdue = PlatformInvoice::query()
        ->where('status', PlatformInvoiceStatus::Issued->value)
        ->whereNotNull('due_at')
        ->where('due_at', '<', now())
        ->update(['status' => PlatformInvoiceStatus::Overdue->value]);

    $this->info("Subscriptions created: {$created}");
    $this->info("Subscription statuses changed: {$changed}");
    $this->info("Invoices marked overdue: {$overdue}");

    return self::SUCCESS;
})->purpose('Backfill and synchronize commercial subscription state without deleting tenant data');

Artisan::command('invoice:doctor {--json : Output machine-readable JSON}', function (ReleaseReadiness $readiness): int {
    $result = $readiness->inspect();

    if ($this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result['ready'] ? self::SUCCESS : self::FAILURE;
    }

    $this->info('Invoice Drafts release readiness');
    $this->line('Status: '.$result['status']);
    $this->newLine();

    $rows = collect($result['checks'])
        ->map(fn (array $check, string $name) => [
            $name,
            strtoupper($check['status']),
            $check['message'],
        ])
        ->values()
        ->all();

    $this->table(['Check', 'Status', 'Message'], $rows);

    return $result['ready'] ? self::SUCCESS : self::FAILURE;
})->purpose('Verify database, private storage, cache, PDF runtime, RTL fonts and production guards');

Artisan::command('invoice:migrate-tenants {--tenant=* : Tenant UUID or slug; repeat for multiple tenants} {--dry-run : Show migration status without changing tenant databases}', function (TenantMigrationRunner $runner): int {
    $targets = array_values(array_filter(array_map('strval', (array) $this->option('tenant'))));
    $result = $runner->run($targets, (bool) $this->option('dry-run'));

    foreach ($result['results'] as $tenant) {
        $this->line(sprintf(
            '[%s] %s (%s)',
            strtoupper((string) $tenant['status']),
            (string) $tenant['slug'],
            (string) $tenant['tenant_id'],
        ));

        if ($this->option('dry-run') && ($tenant['message'] ?? '') !== '') {
            $this->line((string) $tenant['message']);
        }
    }

    $this->newLine();
    $this->info(sprintf(
        'Tenants: %d | succeeded: %d | failed: %d',
        $result['total'],
        $result['succeeded'],
        $result['failed'],
    ));

    return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
})->purpose('Safely dry-run or apply tenant migrations one tenant at a time with audit events');

Artisan::command('invoice:backup {--path= : Private backup directory; defaults under storage/app/backups}', function (BackupManager $backups): int {
    $manifest = $backups->create($this->option('path') ?: null);

    $this->info('Backup created and hashed.');
    $this->line('Path: '.$manifest['path']);
    $this->line('Files: '.$manifest['file_count']);
    $this->line('Tenants: '.count($manifest['tenants']));
    $this->line('Manifest SHA-256: '.$manifest['manifest_sha256']);

    return self::SUCCESS;
})->purpose('Create a private central + tenant MySQL and tenant-file backup bundle');

Artisan::command('invoice:backup-verify {path : Backup bundle directory}', function (BackupManager $backups): int {
    $result = $backups->verify((string) $this->argument('path'));

    if ($result['valid']) {
        $this->info('Backup integrity verified.');
        $this->line('Files checked: '.$result['checked']);

        return self::SUCCESS;
    }

    $this->error('Backup verification failed.');

    foreach ($result['errors'] as $error) {
        $this->line('- '.$error);
    }

    return self::FAILURE;
})->purpose('Verify every backup file against its recorded SHA-256 and byte size');


Schedule::command('invoice:sync-subscriptions')
    ->hourly()
    ->withoutOverlapping();
