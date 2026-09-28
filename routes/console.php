<?php

use App\Enums\PlatformInvoiceStatus;
use App\Models\Central\Business;
use App\Models\Central\PlatformInvoice;
use App\Services\Commercial\StartTrialSubscription;
use App\Services\Commercial\SubscriptionStatusSynchronizer;
use App\Services\Operations\ReleaseReadiness;
use Database\Seeders\HeadOperatorSeeder;
use Illuminate\Support\Facades\Artisan;

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
