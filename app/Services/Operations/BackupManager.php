<?php

namespace App\Services\Operations;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class BackupManager
{
    /**
     * @return array<string, mixed>
     */
    public function create(?string $requestedPath = null): array
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            throw new RuntimeException('Release backup currently supports the MySQL production connection only.');
        }

        $root = $this->backupRoot($requestedPath);
        $this->makePrivateDirectory($root);
        $this->makePrivateDirectory($root.'/database');
        $this->makePrivateDirectory($root.'/tenants');

        $manifest = [
            'format' => 1,
            'product' => 'Invoice Drafts SaaS',
            'created_at' => now()->toIso8601String(),
            'app_env' => app()->environment(),
            'central_database' => config('database.connections.mysql.database'),
            'files' => [],
            'tenants' => [],
        ];

        $centralRelative = 'database/central.sql';
        $centralAbsolute = $root.'/'.$centralRelative;

        $this->dumpDatabase((string) config('database.connections.mysql.database'), $centralAbsolute);
        $manifest['files'][] = $this->entry($root, $centralRelative, 'central_database');

        foreach (Tenant::query()->orderBy('slug')->cursor() as $tenant) {
            $tenantKey = (string) $tenant->getTenantKey();
            $safeTenant = preg_replace('/[^A-Za-z0-9._-]/', '_', $tenantKey) ?: 'tenant';
            $tenantRoot = 'tenants/'.$safeTenant;
            $databaseRelative = $tenantRoot.'/database.sql';

            $this->makePrivateDirectory($root.'/'.$tenantRoot);
            $this->makePrivateDirectory($root.'/'.$tenantRoot.'/files');

            $databaseName = $tenant->database()->getName();
            $this->dumpDatabase($databaseName, $root.'/'.$databaseRelative);
            $manifest['files'][] = $this->entry(
                $root,
                $databaseRelative,
                'tenant_database',
                $tenantKey,
            );

            $fileCount = $tenant->run(function () use ($root, $tenantRoot, $tenantKey, &$manifest): int {
                $count = 0;

                foreach (Storage::disk('local')->allFiles() as $path) {
                    $relative = $tenantRoot.'/files/'.ltrim($path, '/');
                    $absolute = $root.'/'.$relative;

                    $this->makePrivateDirectory(dirname($absolute));

                    $stream = Storage::disk('local')->readStream($path);

                    if (is_resource($stream) === false) {
                        throw new RuntimeException("Unable to read tenant file [{$path}].");
                    }

                    $destination = fopen($absolute, 'wb');

                    if ($destination === false) {
                        fclose($stream);

                        throw new RuntimeException("Unable to create backup file [{$relative}].");
                    }

                    try {
                        stream_copy_to_stream($stream, $destination);
                    } finally {
                        fclose($stream);
                        fclose($destination);
                    }

                    chmod($absolute, 0600);
                    $manifest['files'][] = $this->entry(
                        $root,
                        $relative,
                        'tenant_file',
                        $tenantKey,
                    );
                    $count++;
                }

                return $count;
            });

            $manifest['tenants'][] = [
                'id' => $tenantKey,
                'slug' => $tenant->slug,
                'database' => $databaseName,
                'private_file_count' => $fileCount,
            ];
        }

        $manifest['file_count'] = count($manifest['files']);
        $manifestPath = $root.'/manifest.json';
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false || file_put_contents($manifestPath, $json) === false) {
            throw new RuntimeException('Unable to write backup manifest.');
        }

        chmod($manifestPath, 0600);

        return [
            ...$manifest,
            'path' => $root,
            'manifest_sha256' => hash_file('sha256', $manifestPath),
        ];
    }

    /**
     * @return array{valid:bool,path:string,checked:int,errors:array<int,string>}
     */
    public function verify(string $path): array
    {
        $root = realpath($path);

        if ($root === false || is_dir($root) === false) {
            throw new RuntimeException('Backup directory does not exist.');
        }

        $manifestPath = $root.'/manifest.json';

        if (is_file($manifestPath) === false) {
            throw new RuntimeException('Backup manifest.json is missing.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (
            is_array($manifest) === false
            || ($manifest['format'] ?? null) !== 1
            || is_array($manifest['files'] ?? null) === false
        ) {
            throw new RuntimeException('Backup manifest is invalid or unsupported.');
        }

        $errors = [];
        $checked = 0;

        foreach ($manifest['files'] as $entry) {
            $relative = (string) ($entry['path'] ?? '');

            if ($relative === '' || str_contains($relative, '..')) {
                $errors[] = 'Manifest contains an unsafe file path.';

                continue;
            }

            $absolute = $root.'/'.$relative;

            if (is_file($absolute) === false) {
                $errors[] = "Missing backup file: {$relative}";

                continue;
            }

            $checked++;

            $hash = hash_file('sha256', $absolute);
            $size = filesize($absolute);

            if (hash_equals((string) ($entry['sha256'] ?? ''), (string) $hash) === false) {
                $errors[] = "SHA-256 mismatch: {$relative}";
            }

            if ((int) ($entry['size_bytes'] ?? -1) !== $size) {
                $errors[] = "Size mismatch: {$relative}";
            }
        }

        return [
            'valid' => $errors === [],
            'path' => $root,
            'checked' => $checked,
            'errors' => $errors,
        ];
    }

    private function dumpDatabase(string $database, string $output): void
    {
        $binary = $this->mysqldumpBinary();
        $config = config('database.connections.mysql');

        $process = new Process([
            $binary,
            '--host='.(string) $config['host'],
            '--port='.(string) $config['port'],
            '--user='.(string) $config['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--events',
            '--triggers',
            '--hex-blob',
            '--skip-comments',
            '--result-file='.$output,
            $database,
        ]);

        $process->setEnv([
            ...getenv(),
            'MYSQL_PWD' => (string) ($config['password'] ?? ''),
        ]);
        $process->setTimeout((float) config('invoice.operations.backup_timeout_seconds', 900));
        $process->run();

        if ($process->isSuccessful() === false || is_file($output) === false) {
            throw new RuntimeException(
                "Database backup failed for [{$database}]: ".trim(
                    $process->getErrorOutput() ?: $process->getOutput()
                )
            );
        }

        chmod($output, 0600);
    }

    private function mysqldumpBinary(): string
    {
        $configured = trim((string) config('invoice.operations.mysqldump_binary'));

        if ($configured !== '') {
            if (is_executable($configured) === false) {
                throw new RuntimeException('Configured MYSQLDUMP_BINARY is not executable.');
            }

            return $configured;
        }

        foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('mysqldump is not available. Configure MYSQLDUMP_BINARY.');
    }

    private function backupRoot(?string $requestedPath): string
    {
        if ($requestedPath !== null && trim($requestedPath) !== '') {
            $path = $requestedPath;

            if (str_starts_with($path, DIRECTORY_SEPARATOR) === false) {
                $path = base_path($path);
            }

            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        return storage_path('app/backups/'.now()->format('Ymd_His'));
    }

    private function makePrivateDirectory(string $path): void
    {
        if (
            is_dir($path) === false
            && mkdir($path, 0700, true) === false
            && is_dir($path) === false
        ) {
            throw new RuntimeException("Unable to create private backup directory [{$path}].");
        }

        chmod($path, 0700);
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(
        string $root,
        string $relative,
        string $kind,
        ?string $tenantId = null,
    ): array {
        $absolute = $root.'/'.$relative;

        return [
            'path' => $relative,
            'kind' => $kind,
            'tenant_id' => $tenantId,
            'size_bytes' => filesize($absolute),
            'sha256' => hash_file('sha256', $absolute),
        ];
    }
}
