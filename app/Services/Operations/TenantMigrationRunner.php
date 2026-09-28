<?php

namespace App\Services\Operations;

use App\Enums\ProvisioningStatus;
use App\Models\Central\Tenant;
use App\Services\Tenancy\ProvisioningRecorder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class TenantMigrationRunner
{
    public function __construct(private readonly ProvisioningRecorder $recorder) {}

    /**
     * @param  array<int, string>  $targets
     * @return array{total:int,succeeded:int,failed:int,results:array<int,array<string,mixed>>}
     */
    public function run(array $targets = [], bool $dryRun = false): array
    {
        $tenants = $this->tenants($targets);

        $results = [];
        $succeeded = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                if ($dryRun) {
                    $output = $tenant->run(function (): string {
                        $exit = Artisan::call('migrate:status', [
                            '--path' => database_path('migrations/tenant'),
                            '--realpath' => true,
                        ]);

                        if ($exit !== 0) {
                            throw new RuntimeException('migrate:status returned a non-zero exit code.');
                        }

                        return trim(Artisan::output());
                    });

                    $results[] = [
                        'tenant_id' => $tenant->getTenantKey(),
                        'slug' => $tenant->slug,
                        'status' => 'dry-run',
                        'message' => $output,
                    ];
                    $succeeded++;

                    continue;
                }

                $event = $this->recorder->start(
                    $tenant->getTenantKey(),
                    'release_tenant_migration',
                    ['slug' => $tenant->slug],
                );

                try {
                    $exit = Artisan::call('tenants:migrate', [
                        '--tenants' => [$tenant->getTenantKey()],
                    ]);

                    if ($exit !== 0) {
                        throw new RuntimeException('Tenant migration command returned a non-zero exit code.');
                    }

                    $message = trim(Artisan::output()) ?: 'Tenant migrations completed.';
                    $this->recorder->success($event, $message);

                    $results[] = [
                        'tenant_id' => $tenant->getTenantKey(),
                        'slug' => $tenant->slug,
                        'status' => 'success',
                        'message' => $message,
                    ];
                    $succeeded++;
                } catch (Throwable $exception) {
                    $this->recorder->failure($event, $exception);
                    throw $exception;
                }
            } catch (Throwable $exception) {
                $results[] = [
                    'tenant_id' => $tenant->getTenantKey(),
                    'slug' => $tenant->slug,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ];
                $failed++;
            }
        }

        return [
            'total' => $tenants->count(),
            'succeeded' => $succeeded,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * @param  array<int, string>  $targets
     * @return Collection<int, Tenant>
     */
    private function tenants(array $targets): Collection
    {
        $query = Tenant::query()
            ->where('provisioning_status', ProvisioningStatus::Ready->value)
            ->orderBy('slug');

        if ($targets !== []) {
            $query->where(function ($nested) use ($targets): void {
                $nested->whereIn('id', $targets)->orWhereIn('slug', $targets);
            });
        }

        $tenants = $query->get();

        if ($targets !== [] && $tenants->count() !== count(array_unique($targets))) {
            $found = $tenants
                ->flatMap(fn (Tenant $tenant) => [$tenant->getTenantKey(), $tenant->slug])
                ->all();

            $missing = array_values(array_filter(
                array_unique($targets),
                fn (string $target) => ! in_array($target, $found, true),
            ));

            if ($missing !== []) {
                throw new RuntimeException('Ready tenant(s) not found: '.implode(', ', $missing));
            }
        }

        return $tenants;
    }
}
