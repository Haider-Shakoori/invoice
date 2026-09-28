<?php

namespace Tests\Feature\TenantWorkspace;

use App\Models\Tenant\BusinessProfile;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\Tenant\InvoiceDraftService;
use Database\Seeders\TenantBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InvoiceDraftServiceTest extends TestCase
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

    public function test_draft_create_update_and_duplicate_preserve_history_and_snapshots(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'StrongPass123',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $ownerRole = Role::query()->where('key', 'owner')->firstOrFail();
        $owner->roles()->sync([$ownerRole->id]);

        BusinessProfile::query()->create([
            'display_name' => 'Example Trading',
            'phone' => '0700000000',
            'default_locale' => 'en',
            'default_currency' => 'AFN',
            'onboarding_completed' => true,
            'onboarding_step' => 3,
        ]);

        $customer = Customer::query()->create([
            'name' => 'Original Client',
            'company_name' => 'Client Company',
            'phone' => '0799999999',
            'is_active' => true,
        ]);

        $service = app(InvoiceDraftService::class);

        $invoice = $service->create([
            'customer_id' => $customer->id,
            'locale' => 'en',
            'issue_date' => '2026-09-28',
            'discount_type' => 'fixed',
            'discount_value' => '10',
            'lines' => [
                [
                    'description' => 'Consulting',
                    'quantity' => '2.000',
                    'unit' => 'hour',
                    'unit_price' => '125.50',
                    'discount_percent' => '0',
                ],
            ],
        ], $owner);

        $this->assertMatchesRegularExpression('/^INV-\d{4}-000001$/', $invoice->number);
        $this->assertSame('251.00', $invoice->subtotal);
        $this->assertSame('241.00', $invoice->total);
        $this->assertSame('Original Client', $invoice->customer_snapshot['name']);
        $this->assertSame(1, $invoice->versions()->count());
        $this->assertSame(1, $invoice->activity()->where('event', 'created')->count());

        $customer->update(['name' => 'Renamed Client']);

        $this->assertSame('Original Client', $invoice->fresh()->customer_snapshot['name']);

        $updated = $service->update($invoice->fresh(), [
            'customer_id' => $customer->id,
            'locale' => 'en',
            'issue_date' => '2026-09-28',
            'discount_type' => null,
            'discount_value' => 0,
            'lines' => [
                [
                    'description' => 'Consulting',
                    'quantity' => '3.000',
                    'unit' => 'hour',
                    'unit_price' => '100.00',
                    'discount_percent' => '0',
                ],
            ],
        ], $owner);

        $this->assertSame(2, $updated->version_no);
        $this->assertSame('300.00', $updated->total);
        $this->assertSame('Renamed Client', $updated->customer_snapshot['name']);
        $this->assertSame(2, $updated->versions()->count());

        $copy = $service->duplicate($updated, $owner);

        $this->assertNotSame($updated->number, $copy->number);
        $this->assertSame($updated->id, $copy->duplicated_from_id);
        $this->assertSame($updated->total, $copy->total);
        $this->assertSame(1, $copy->versions()->count());
        $this->assertSame(1, $copy->activity()->where('event', 'duplicated')->count());
    }
}
