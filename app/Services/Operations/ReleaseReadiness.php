<?php

namespace App\Services\Operations;

use App\Services\Tenant\ChromiumPdfRenderer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class ReleaseReadiness
{
    public function __construct(private readonly ChromiumPdfRenderer $pdfRenderer) {}

    /**
     * @return array{ready:bool,status:string,checked_at:string,checks:array<string,array{status:string,message:string}>}
     */
    public function inspect(): array
    {
        $checks = [
            'database' => $this->database(),
            'private_storage' => $this->storage(),
            'cache' => $this->cache(),
            'pdf_renderer' => $this->pdfRenderer(),
            'rtl_font' => $this->rtlFont(),
            'production_config' => $this->productionConfig(),
        ];

        $ready = collect($checks)->every(fn (array $check) => $check['status'] !== 'fail');
        $warnings = collect($checks)->contains(fn (array $check) => $check['status'] === 'warn');

        return [
            'ready' => $ready,
            'status' => $ready ? ($warnings ? 'ready_with_warnings' : 'ready') : 'not_ready',
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status:string,message:string}
     */
    private function database(): array
    {
        try {
            DB::select('select 1 as readiness_check');

            return $this->pass('Central database connection is available.');
        } catch (Throwable $exception) {
            return $this->fail('Central database check failed: '.$this->safeMessage($exception));
        }
    }

    /**
     * @return array{status:string,message:string}
     */
    private function storage(): array
    {
        $path = 'health/'.Str::uuid().'.txt';

        try {
            Storage::disk('local')->put($path, 'ready');

            if (Storage::disk('local')->get($path) !== 'ready') {
                return $this->fail('Private storage round-trip returned unexpected content.');
            }

            return $this->pass('Private storage is writable and readable.');
        } catch (Throwable $exception) {
            return $this->fail('Private storage check failed: '.$this->safeMessage($exception));
        } finally {
            try {
                Storage::disk('local')->delete($path);
            } catch (Throwable) {
                // Do not mask the primary readiness result.
            }
        }
    }

    /**
     * @return array{status:string,message:string}
     */
    private function cache(): array
    {
        $key = 'invoice:readiness:'.Str::uuid();

        try {
            Cache::put($key, 'ready', 30);

            if (Cache::get($key) !== 'ready') {
                return $this->fail('Cache round-trip returned unexpected content.');
            }

            return $this->pass('Cache is writable and readable.');
        } catch (Throwable $exception) {
            return $this->fail('Cache check failed: '.$this->safeMessage($exception));
        } finally {
            try {
                Cache::forget($key);
            } catch (Throwable) {
                // Do not mask the primary readiness result.
            }
        }
    }

    /**
     * @return array{status:string,message:string}
     */
    private function pdfRenderer(): array
    {
        $binary = $this->pdfRenderer->binary();

        return $binary
            ? $this->pass('Chromium/Chrome PDF renderer is available.')
            : $this->fail('Chromium/Chrome PDF renderer is not available.');
    }

    /**
     * @return array{status:string,message:string}
     */
    private function rtlFont(): array
    {
        try {
            $process = new Process(['fc-match', '--format=%{family}', 'Noto Naskh Arabic']);
            $process->setTimeout(5);
            $process->run();

            if (! $process->isSuccessful()) {
                return $this->warn('Fontconfig could not verify the preferred RTL font.');
            }

            $family = trim($process->getOutput());

            if ($family === '') {
                return $this->warn('No RTL-capable font family was reported by Fontconfig.');
            }

            return str_contains(Str::lower($family), 'noto')
                ? $this->pass('Preferred Noto Arabic-script font is available.')
                : $this->warn('Preferred Noto Arabic-script font is missing; fallback is '.$family.'.');
        } catch (Throwable $exception) {
            return $this->warn('RTL font verification unavailable: '.$this->safeMessage($exception));
        }
    }

    /**
     * @return array{status:string,message:string}
     */
    private function productionConfig(): array
    {
        if (! app()->environment('production')) {
            return $this->pass('Non-production environment; production-only guards are not required.');
        }

        $issues = [];

        if (config('app.debug')) {
            $issues[] = 'APP_DEBUG must be false';
        }

        if (config('queue.default') === 'sync') {
            $issues[] = 'QUEUE_CONNECTION should not be sync';
        }

        if ((string) config('app.key') === '') {
            $issues[] = 'APP_KEY is missing';
        }

        return $issues === []
            ? $this->pass('Production configuration guards passed.')
            : $this->fail(implode('; ', $issues).'.');
    }

    /**
     * @return array{status:string,message:string}
     */
    private function pass(string $message): array
    {
        return ['status' => 'pass', 'message' => $message];
    }

    /**
     * @return array{status:string,message:string}
     */
    private function warn(string $message): array
    {
        return ['status' => 'warn', 'message' => $message];
    }

    /**
     * @return array{status:string,message:string}
     */
    private function fail(string $message): array
    {
        return ['status' => 'fail', 'message' => $message];
    }

    private function safeMessage(Throwable $exception): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', $exception->getMessage()) ?: 'unknown error', 180);
    }
}
