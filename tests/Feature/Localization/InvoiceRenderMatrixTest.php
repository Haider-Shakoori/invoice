<?php

namespace Tests\Feature\Localization;

use App\Models\Tenant\BusinessProfile;
use App\Models\Tenant\Customer;
use App\Models\Tenant\InvoiceTemplate;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\Tenant\InvoiceDocumentService;
use App\Services\Tenant\InvoiceDraftService;
use Database\Seeders\TenantBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InvoiceRenderMatrixTest extends TestCase
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

    public function test_all_twenty_templates_render_in_english_dari_and_pashto_without_mutating_invoice(): void
    {
        [$invoice, $owner] = $this->fixture();

        $original = [
            'number' => $invoice->number,
            'total' => $invoice->total,
            'locale' => $invoice->locale,
            'template_id' => $invoice->invoice_template_id,
            'version_no' => $invoice->version_no,
        ];

        $locales = [
            'en' => ['dir="ltr"', 'INVOICE'],
            'fa' => ['dir="rtl"', 'فاکتور'],
            'ps' => ['dir="rtl"', 'بل'],
        ];

        $rendered = 0;

        foreach (InvoiceTemplate::query()->orderBy('template_number')->get() as $template) {
            foreach ($locales as $locale => [$direction, $translatedTitle]) {
                $preview = app(InvoiceDocumentService::class)->preview(
                    $invoice->fresh(),
                    $template->id,
                    $locale,
                );

                $html = $preview['html'];

                $this->assertStringContainsString('lang="'.$locale.'"', $html);
                $this->assertStringContainsString($direction, $html);
                $this->assertStringContainsString('template-'.$template->key, $html);
                $this->assertStringContainsString($invoice->number, $html);
                $this->assertStringContainsString($translatedTitle, $html);
                $this->assertStringContainsString('Mixed English — کابل — پښتو', $html);
                $this->assertStringContainsString('104.50', $html);

                $rendered++;
            }
        }

        $this->assertSame(60, $rendered);

        $invoice->refresh();

        $this->assertSame($original['number'], $invoice->number);
        $this->assertSame($original['total'], $invoice->total);
        $this->assertSame($original['locale'], $invoice->locale);
        $this->assertSame($original['template_id'], $invoice->invoice_template_id);
        $this->assertSame($original['version_no'], $invoice->version_no);
        $this->assertSame(0, $invoice->exports()->count());

        $this->assertAuthenticatedAs($owner);
    }

    /**
     * @return array{0:\App\Models\Tenant\InvoiceDraft,1:User}
     */
    private function fixture(): array
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'phase4-owner@example.test',
            'password' => 'StrongPass123',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $ownerRole = Role::query()->where('key', 'owner')->firstOrFail();
        $owner->roles()->sync([$ownerRole->id]);
        $this->actingAs($owner);

        BusinessProfile::query()->create([
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

        $customer = Customer::query()->create([
            'name' => 'Kabul Trading Co.',
            'company_name' => 'شرکت تجارتی کابل',
            'address' => 'Shahr-e-Naw, Kabul',
            'phone' => '+93 700 111 222',
            'is_active' => true,
        ]);

        $template = InvoiceTemplate::query()->where('template_number', 1)->firstOrFail();

        $invoice = app(InvoiceDraftService::class)->create([
            'customer_id' => $customer->id,
            'invoice_template_id' => $template->id,
            'locale' => 'en',
            'currency' => 'AFN',
            'issue_date' => '2026-09-28',
            'discount_type' => 'fixed',
            'discount_value' => '10.00',
            'additional_charge_label' => 'Delivery',
            'additional_charge_amount' => '5.00',
            'tax_label' => 'Tax',
            'tax_rate' => '10.0000',
            'notes' => 'Mixed English — کابل — پښتو',
            'terms' => 'Payment terms',
            'lines' => [
                [
                    'description' => 'Mixed English — کابل — پښتو',
                    'quantity' => '1.000',
                    'unit' => 'service',
                    'unit_price' => '100.00',
                    'discount_percent' => '0',
                ],
            ],
        ], $owner);

        return [$invoice, $owner];
    }
}
