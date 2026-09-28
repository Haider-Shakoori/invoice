<?php

namespace Tests\Feature\Commercial;

use App\Enums\PaymentMethod;
use App\Enums\PlatformInvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Central\AdminUser;
use App\Models\Central\Business;
use App\Models\Central\Seller;
use App\Models\Central\Tenant;
use App\Services\Commercial\IssueActivationInvoice;
use App\Services\Commercial\IssueRenewalInvoice;
use App\Services\Commercial\RecordPlatformPayment;
use App\Services\Commercial\StartTrialSubscription;
use App\Services\Commercial\SubscriptionLifecycle;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_activation_payment_preserves_remaining_trial_and_activates_for_one_year(): void
    {
        Carbon::setTestNow('2026-09-28 08:00:00');

        $business = $this->business();
        $seller = Seller::query()->create([
            'name' => 'Kabul Seller',
            'commission_type' => 'percent',
            'commission_rate' => 10,
            'is_active' => true,
        ]);

        $subscription = app(StartTrialSubscription::class)->handle($business, $seller->id);
        $invoice = app(IssueActivationInvoice::class)->handle($subscription);

        $this->assertSame(5000, $invoice->total_afn);
        $this->assertCount(2, $invoice->lines);

        $admin = AdminUser::query()->create([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => 'StrongPass123',
            'role' => 'operator',
            'is_active' => true,
        ]);

        app(RecordPlatformPayment::class)->handle(
            $invoice,
            2000,
            PaymentMethod::Cash,
            null,
            null,
            null,
            $admin,
        );

        $this->assertSame(PlatformInvoiceStatus::Issued, $invoice->fresh()->status);
        $this->assertSame(SubscriptionStatus::Trialing, $subscription->fresh()->status);

        app(RecordPlatformPayment::class)->handle(
            $invoice,
            3000,
            PaymentMethod::Hawala,
            null,
            'HW-001',
            null,
            $admin,
        );

        $invoice = $invoice->fresh();
        $subscription = $subscription->fresh();

        $this->assertSame(PlatformInvoiceStatus::Paid, $invoice->status);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame('2026-10-05', $subscription->current_period_start->toDateString());
        $this->assertSame('2027-10-05', $subscription->current_period_end->toDateString());
        $this->assertSame(500, $seller->commissions()->sum('amount_afn'));
        $this->assertSame('active', $business->fresh()->status);
    }

    public function test_renewal_extends_from_existing_paid_period_when_paid_early(): void
    {
        Carbon::setTestNow('2026-09-28 08:00:00');

        $business = $this->business();
        $subscription = app(StartTrialSubscription::class)->handle($business);

        $activation = app(IssueActivationInvoice::class)->handle($subscription);
        app(RecordPlatformPayment::class)->handle($activation, 5000, PaymentMethod::BankTransfer);

        $firstEnd = $subscription->fresh()->current_period_end;

        Carbon::setTestNow('2027-09-01 08:00:00');

        $renewal = app(IssueRenewalInvoice::class)->handle($subscription->fresh());
        app(RecordPlatformPayment::class)->handle($renewal, 3000, PaymentMethod::Cash);

        $renewed = $subscription->fresh();

        $this->assertSame(
            $firstEnd->copy()->addYear()->toDateString(),
            $renewed->current_period_end->toDateString(),
        );
    }

    public function test_expired_trial_is_locked_without_deleting_commercial_records(): void
    {
        Carbon::setTestNow('2026-09-28 08:00:00');

        $business = $this->business();
        $subscription = app(StartTrialSubscription::class)->handle($business);

        Carbon::setTestNow('2026-10-06 08:00:00');

        $subscription = app(SubscriptionLifecycle::class)->synchronize($subscription->fresh());

        $this->assertSame(SubscriptionStatus::Expired, $subscription->status);
        $this->assertNotNull($subscription->locked_at);
        $this->assertSame('expired', $business->fresh()->status);
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);
    }

    public function test_payment_cannot_exceed_invoice_outstanding_amount(): void
    {
        $business = $this->business();
        $subscription = app(StartTrialSubscription::class)->handle($business);
        $invoice = app(IssueActivationInvoice::class)->handle($subscription);

        $this->expectException(DomainException::class);

        app(RecordPlatformPayment::class)->handle($invoice, 5001, PaymentMethod::Cash);
    }

    public function test_fixed_commission_is_earned_once_when_invoice_is_fully_paid(): void
    {
        $business = $this->business();
        $seller = Seller::query()->create([
            'name' => 'Fixed Seller',
            'commission_type' => 'fixed',
            'commission_rate' => 250,
            'is_active' => true,
        ]);

        $subscription = app(StartTrialSubscription::class)->handle($business, $seller->id);
        $invoice = app(IssueActivationInvoice::class)->handle($subscription);

        app(RecordPlatformPayment::class)->handle($invoice, 2000, PaymentMethod::Cash);
        $this->assertSame(0, $seller->commissions()->count());

        app(RecordPlatformPayment::class)->handle($invoice, 3000, PaymentMethod::Cash);

        $this->assertSame(1, $seller->commissions()->count());
        $this->assertSame(250, $seller->commissions()->sum('amount_afn'));
    }

    private function business(): Business
    {
        $suffix = strtolower(bin2hex(random_bytes(4)));

        $tenant = Tenant::query()->create([
            'id' => 'tenant-'.$suffix,
            'slug' => 'tenant-'.$suffix,
            'provisioning_status' => 'ready',
            'ready_at' => now(),
        ]);

        return Business::query()->create([
            'tenant_id' => $tenant->getTenantKey(),
            'display_name' => 'Commercial Test Business',
            'owner_name' => 'Owner',
            'owner_email' => 'owner-'.$suffix.'@example.test',
            'status' => 'trial',
            'provisioning_status' => 'ready',
        ]);
    }
}
