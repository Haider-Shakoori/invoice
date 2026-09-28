<?php

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class ChromiumPdfRenderer
{
    /**
     * @return array{path:string,sha256:string,size_bytes:int}
     */
    public function render(string $html, string $filename): array
    {
        $binary = $this->binary();

        if ($binary === null) {
            throw new RuntimeException('Chromium/Chrome is not configured. Set PDF_CHROMIUM_BINARY to enable PDF export.');
        }

        $token = bin2hex(random_bytes(12));
        $htmlPath = "tmp/pdf/{$token}.html";
        $pdfPath = 'exports/'.$filename;

        Storage::disk('local')->put($htmlPath, $html);

        $input = Storage::disk('local')->path($htmlPath);
        $output = Storage::disk('local')->path($pdfPath);

        $directory = dirname($output);

        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the tenant PDF export directory.');
        }

        try {
            $process = new Process([
                $binary,
                '--headless=new',
                '--no-sandbox',
                '--disable-gpu',
                '--allow-file-access-from-files',
                '--no-pdf-header-footer',
                '--print-to-pdf='.$output,
                'file://'.$input,
            ]);

            $process->setTimeout((float) config('invoice.pdf.timeout_seconds', 30));
            $process->run();

            if (! $process->isSuccessful() || ! is_file($output)) {
                throw new RuntimeException(
                    'PDF rendering failed: '.trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            $bytes = file_get_contents($output);

            if ($bytes === false || ! str_starts_with($bytes, '%PDF-')) {
                throw new RuntimeException('Chromium did not produce a valid PDF document.');
            }

            return [
                'path' => $pdfPath,
                'sha256' => hash('sha256', $bytes),
                'size_bytes' => strlen($bytes),
            ];
        } finally {
            Storage::disk('local')->delete($htmlPath);
        }
    }

    public function binary(): ?string
    {
        $configured = trim((string) config('invoice.pdf.chromium_binary'));

        if ($configured !== '' && is_executable($configured)) {
            return $configured;
        }

        foreach ([
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
        ] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
