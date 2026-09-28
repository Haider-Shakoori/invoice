<?php

namespace Tests\Feature\Localization;

use App\Services\Tenant\ChromiumPdfRenderer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChromiumPdfRendererTest extends TestCase
{
    public function test_chromium_generates_a_real_pdf_with_mixed_script_content_when_available(): void
    {
        $renderer = app(ChromiumPdfRenderer::class);

        if ($renderer->binary() === null) {
            $this->markTestSkipped('Chromium/Chrome is not installed on this runner.');
        }

        $filename = 'phase4-renderer-smoke.pdf';

        try {
            $result = $renderer->render(
                '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'.
                '<style>@page{size:A4;margin:15mm}body{font-family:"Noto Naskh Arabic","DejaVu Sans",sans-serif}</style>'.
                '</head><body><h1>Invoice — فاکتور — بل</h1><p>English · دری · پښتو</p></body></html>',
                $filename,
            );

            $this->assertTrue(Storage::disk('local')->exists($result['path']));

            $bytes = Storage::disk('local')->get($result['path']);

            $this->assertStringStartsWith('%PDF-', $bytes);
            $this->assertSame(hash('sha256', $bytes), $result['sha256']);
            $this->assertSame(strlen($bytes), $result['size_bytes']);
            $this->assertGreaterThan(1000, $result['size_bytes']);
        } finally {
            Storage::disk('local')->delete('exports/'.$filename);
        }
    }
}
