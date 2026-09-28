<?php

namespace Tests\Unit;

use App\Services\Operations\BackupManager;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupManagerTest extends TestCase
{
    public function test_backup_manifest_verification_detects_tampering(): void
    {
        $root = storage_path('framework/testing/backup-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($root.'/database');

        try {
            $relative = 'database/central.sql';
            $absolute = $root.'/'.$relative;
            file_put_contents($absolute, 'CREATE TABLE example (id INT);');

            $manifest = [
                'format' => 1,
                'files' => [[
                    'path' => $relative,
                    'kind' => 'central_database',
                    'tenant_id' => null,
                    'size_bytes' => filesize($absolute),
                    'sha256' => hash_file('sha256', $absolute),
                ]],
            ];

            file_put_contents(
                $root.'/manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            );

            $manager = app(BackupManager::class);

            $valid = $manager->verify($root);

            $this->assertTrue($valid['valid']);
            $this->assertSame(1, $valid['checked']);
            $this->assertSame([], $valid['errors']);

            file_put_contents($absolute, 'tampered');

            $invalid = $manager->verify($root);

            $this->assertFalse($invalid['valid']);
            $this->assertNotEmpty($invalid['errors']);
            $this->assertTrue(
                collect($invalid['errors'])->contains(
                    fn (string $error) => str_contains($error, 'SHA-256 mismatch')
                        || str_contains($error, 'Size mismatch'),
                ),
            );
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_backup_manifest_rejects_unsafe_paths(): void
    {
        $root = storage_path('framework/testing/backup-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($root);

        try {
            file_put_contents($root.'/manifest.json', json_encode([
                'format' => 1,
                'files' => [[
                    'path' => '../escape.sql',
                    'size_bytes' => 0,
                    'sha256' => hash('sha256', ''),
                ]],
            ]));

            $result = app(BackupManager::class)->verify($root);

            $this->assertFalse($result['valid']);
            $this->assertContains('Manifest contains an unsafe file path.', $result['errors']);
        } finally {
            File::deleteDirectory($root);
        }
    }
}
