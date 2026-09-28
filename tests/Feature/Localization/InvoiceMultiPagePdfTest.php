<?php

namespace Tests\Feature\Localization;

use App\Models\Tenant\BusinessProfile;
use App\Models\Tenant\Customer;
use App\Models\Tenant\DocumentExport;
use App\Models\Tenant\InvoiceTemplate;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\Tenant\ChromiumPdfRenderer;
use App\Services\Tenant\InvoiceDocumentService;
use App\Services\Tenant\InvoiceDraftService;
use Database\Seeders\TenantBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InvoiceMultiPagePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        app(TenantBaselineSeeder::class)->run();
    }

    public function test_standard_stress_invoice_spans_at_least_two_a4_pages(): void
    {
        $this->assertStressPdf(lineCount: 40, templateNumber: 1, locale: 'en', minimumPages: 2);
    }

    public function test_large_rtl_stress_invoice_spans_at_least_five_a4_pages(): void
    {
        $this->assertStressPdf(lineCount: 120, templateNumber: 10, locale: 'ps', minimumPages: 5);
    }

    private function assertStressPdf(
        int $lineCount,
        int $templateNumber,
        string $locale,
        int $minimumPages,
    ): void {
        if (app(ChromiumPdfRenderer::class)->binary() === null) {
            $this->markTestSkipped('Chromium/Chrome is not installed on this runner.');
        }

        if (! $this->commandExists('pdfinfo')) {
            $this->markTestSkipped('pdfinfo is not installed on this runner.');
        }

        [$invoice, $owner] = $this->fixture($lineCount, $templateNumber, $locale);
        $export = null;

        try {
            $export = app(InvoiceDocumentService::class)->export(
                $invoice,
                $owner,
                $invoice->invoice_template_id,
                $locale,
            );

            $path = Storage::disk('local')->path($export->storage_path);
            $info = new Process(['pdfinfo', $path]);
            $info->setTimeout(15);
            $info->mustRun();

            $this->assertMatchesRegularExpression('/Page size:\s+.*A4/i', $info->getOutput());
            $this->assertMatchesRegularExpression('/Pages:\s+(\d+)/', $info->getOutput());

            preg_match('/Pages:\s+(\d+)/', $info->getOutput(), $matches);
            $pages = (int) ($matches[1] ?? 0);

            $this->assertGreaterThanOrEqual($minimumPages, $pages);

            if ($this->commandExists('pdftotext')) {
                $text = new Process(['pdftotext', $path, '-']);
                $text->setTimeout(15);
                $text->mustRun();

                $this->assertStringContainsString($invoice->number, $text->getOutput());
                $this->assertStringContainsString('Stress line 001', $text->getOutput());
            }
        } finally {
            if ($export instanceof DocumentExport) {
                Storage::disk('local')->delete($export->storage_path);
            }
        }
    }

    /**
     * @return array{0:\App\Models\Tenant\InvoiceDraft,1:User}
     */
    private function fixture(int $lineCount, int $templateNumber, string $locale): array
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'stress-owner@example.test'],
            [
                'name' => 'Stress Owner',
                'password' => 'StrongPass123',
                'role' => 'owner',
                'is_active' => true,
            ],
        );

        $ownerRole = Role::query()->where('key', 'owner')->firstOrFail();
        $owner->roles()->sync([$ownerRole->id]);

        BusinessProfile::query()->firstOrCreate([], [
            'display_name' => 'North Star Company',
            'secondary_name' => 'شرکت ستاره شمال',
            'address' => 'Kabul, Afghanistan',
            'city_province' => 'Kabul',
            'phone' => '+93 700 000 000',
            'default_locale' => 'en',
            'default_currency' => 'AFN',
            'onboarding_completed' => true,
            'onboarding_step' => 3,
        ]);

        $customer = Customer::query()->firstOrCreate(
            ['name' => 'Stress Customer'],
            [
                'company_name' => 'شرکت آزمایشی',
                'address' => 'Kabul',
                'phone' => '+93 700 111 222',
                'is_active' => true,
            ],
        );

        $template = InvoiceTemplate::query()->where('template_number', $templateNumber)->firstOrFail();

        $lines = [];

        for ($index = 1; $index <= $lineCount; $index++) {
            $lines[] = [
                'description' => sprintf(
                    'Stress line %03d — کابل — پښتو — long professional invoice description for pagination verification',
                    $index,
                ),
                'quantity' => '1.000',
                'unit' => 'service',
                'unit_price' => '10.00',
                'discount_percent' => '0',
            ];
        }

        $invoice = app(InvoiceDraftService::class)->create([
            'customer_id' => $customer->id,
            'invoice_template_id' => $template->id,
            'locale' => $locale,
            'currency' => 'AFN',
            'issue_date' => '2026-09-28',
            'notes' => 'Pagination stress fixture — کابل — پښتو',
            'terms' => 'Payment terms for a multi-page production invoice.',
            'lines' => $lines,
        ], $owner);

        return [$invoice, $owner];
    }

    private function commandExists(string $command): bool
    {
        $process = new Process(['which', $command]);
        $process->setTimeout(5);
        $process->run();

        return $process->isSuccessful();
    }
}
