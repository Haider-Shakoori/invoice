<?php

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
