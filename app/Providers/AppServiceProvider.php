<?php

namespace App\Providers;

use App\Services\Tenancy\TenantDatabaseProvisioner;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\DatabaseConfig;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (config('invoice.tenancy.database_provisioner') !== 'cpanel') {
            return;
        }

        $provisioner = app(TenantDatabaseProvisioner::class);

        DatabaseConfig::generateDatabaseNamesUsing(
            fn ($tenant): string => $provisioner->databaseName($tenant),
        );
    }
}
