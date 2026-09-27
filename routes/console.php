<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('invoice:about', function (): void {
    $this->info('Invoice Drafts SaaS');
    $this->line('Central commercial DB + isolated database per tenant.');
})->purpose('Display the Invoice Drafts product boundary');
