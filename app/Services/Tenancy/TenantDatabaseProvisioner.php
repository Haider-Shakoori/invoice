<?php

namespace App\Services\Tenancy;

use App\Models\Central\Tenant;
use RuntimeException;
use Stancl\Tenancy\Database\DatabaseManager as TenancyDatabaseManager;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Symfony\Component\Process\Process;

class TenantDatabaseProvisioner
{
    public function ensure(Tenant $tenant): string
    {
        $databaseName = (string) $tenant->database()->getName();

        if (config('invoice.tenancy.database_provisioner') === 'cpanel') {
            $this->ensureViaCpanel($databaseName);

            return $databaseName;
        }

        $manager = $tenant->database()->manager();

        if (! $manager->databaseExists($databaseName)) {
            (new CreateDatabase($tenant))->handle(app(TenancyDatabaseManager::class));
        }

        return $databaseName;
    }

    public function databaseName(Tenant $tenant): string
    {
        $prefix = (string) config('tenancy.database.prefix', 'invoice_tenant_');
        $slug = strtolower((string) ($tenant->slug ?: $tenant->getTenantKey()));
        $safe = preg_replace('/[^a-z0-9_]+/', '_', $slug) ?: 'tenant';
        $safe = trim($safe, '_');
        $hash = substr(hash('sha256', (string) $tenant->getTenantKey()), 0, 8);
        $available = max(8, 63 - strlen($prefix) - strlen($hash) - 1);
        $suffix = substr($safe, 0, $available);

        return $prefix.$suffix.'_'.$hash;
    }

    /**
     * @return array<string, mixed>
     */
    private function uapi(string $module, string $function, array $arguments = []): array
    {
        $binary = (string) config('invoice.tenancy.cpanel_uapi_binary', '/usr/bin/uapi');

        if (! is_executable($binary)) {
            throw new RuntimeException('Configured cPanel UAPI binary is not executable.');
        }

        $command = [$binary, '--output=json', $module, $function];

        foreach ($arguments as $name => $value) {
            $command[] = $name.'='.$value;
        }

        $process = new Process($command);
        $process->setTimeout((float) config('invoice.tenancy.cpanel_uapi_timeout_seconds', 30));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'cPanel UAPI failed: '.trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        $payload = json_decode($process->getOutput(), true);

        if (! is_array($payload) || (int) ($payload['result']['status'] ?? 0) !== 1) {
            $errors = $payload['result']['errors'] ?? ['Unknown cPanel UAPI error.'];

            throw new RuntimeException('cPanel UAPI failed: '.implode('; ', array_filter((array) $errors)));
        }

        return (array) ($payload['result'] ?? []);
    }

    private function ensureViaCpanel(string $databaseName): void
    {
        $listing = $this->uapi('Mysql', 'list_databases');
        $exists = collect($listing['data'] ?? [])
            ->contains(fn (array $database): bool => ($database['database'] ?? null) === $databaseName);

        if (! $exists) {
            $this->uapi('Mysql', 'create_database', ['name' => $databaseName]);
        }

        $mysqlUser = (string) config(
            'invoice.tenancy.cpanel_mysql_user',
            config('database.connections.mysql.username'),
        );

        if ($mysqlUser === '') {
            throw new RuntimeException('CPANEL_MYSQL_USER is not configured.');
        }

        $this->uapi('Mysql', 'set_privileges_on_database', [
            'user' => $mysqlUser,
            'database' => $databaseName,
            'privileges' => 'ALL PRIVILEGES',
        ]);
    }
}
