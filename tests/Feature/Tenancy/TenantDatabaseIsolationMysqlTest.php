<?php

namespace Tests\Feature\Tenancy;

use App\Actions\Tenancy\ProvisionTenant;
use App\Actions\Tenancy\ResumeTenantProvisioning;
use App\Models\Central\Business;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Tests\TestCase;

class TenantDatabaseIsolationMysqlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! env('RUN_TENANCY_MYSQL_TESTS')) {
            $this->markTestSkipped('MySQL tenancy integration tests are disabled.');
        }

        config()->set('tenancy.tenant_base_domain', 'invoice.test');
    }

    public function test_two_tenants_cannot_read_each_others_users(): void
    {
        [$tenantA, $tenantB] = $this->provisionPair();

        $this->assertNotSame($tenantA->database()->getName(), $tenantB->database()->getName());

        $tenantA->run(function (): void {
            User::query()->create([
                'name' => 'Alpha Staff',
                'email' => 'alpha-staff@example.test',
                'password' => 'StrongPass123',
                'role' => 'staff',
                'is_active' => true,
            ]);

            $this->assertTrue(User::query()->where('email', 'alpha-staff@example.test')->exists());
            $this->assertFalse(User::query()->where('email', 'beta-staff@example.test')->exists());
        });

        $tenantB->run(function (): void {
            User::query()->create([
                'name' => 'Beta Staff',
                'email' => 'beta-staff@example.test',
                'password' => 'StrongPass123',
                'role' => 'staff',
                'is_active' => true,
            ]);

            $this->assertTrue(User::query()->where('email', 'beta-staff@example.test')->exists());
            $this->assertFalse(User::query()->where('email', 'alpha-staff@example.test')->exists());
        });

        $tenantA->run(function (): void {
            $this->assertTrue(User::query()->where('email', 'alpha-staff@example.test')->exists());
            $this->assertFalse(User::query()->where('email', 'beta-staff@example.test')->exists());
        });
    }

    public function test_tenant_files_are_isolated_by_storage_root(): void
    {
        [$tenantA, $tenantB] = $this->provisionPair();

        $alphaRoot = $tenantA->run(function (): string {
            Storage::disk('local')->put('isolation/marker.txt', 'alpha');

            $this->assertSame('alpha', Storage::disk('local')->get('isolation/marker.txt'));

            return (string) config('filesystems.disks.local.root');
        });

        $betaRoot = $tenantB->run(function (): string {
            $this->assertFalse(Storage::disk('local')->exists('isolation/marker.txt'));

            Storage::disk('local')->put('isolation/marker.txt', 'beta');
            $this->assertSame('beta', Storage::disk('local')->get('isolation/marker.txt'));

            return (string) config('filesystems.disks.local.root');
        });

        $this->assertNotSame($alphaRoot, $betaRoot);

        $tenantA->run(function (): void {
            $this->assertSame('alpha', Storage::disk('local')->get('isolation/marker.txt'));
        });
    }

    public function test_queue_payload_carries_the_current_tenant_and_reverts_to_central(): void
    {
        [$tenantA, $tenantB] = $this->provisionPair();

        /** @var QueueTenancyBootstrapper $queueBootstrapper */
        $queueBootstrapper = app(QueueTenancyBootstrapper::class);

        $tenantA->run(function () use ($queueBootstrapper, $tenantA): void {
            $payload = $queueBootstrapper->getPayload('sync');

            $this->assertSame($tenantA->getTenantKey(), $payload['tenant_id'] ?? null);
        });

        $this->assertSame([], $queueBootstrapper->getPayload('sync'));

        $tenantB->run(function () use ($queueBootstrapper, $tenantB): void {
            $payload = $queueBootstrapper->getPayload('sync');

            $this->assertSame($tenantB->getTenantKey(), $payload['tenant_id'] ?? null);
        });

        $this->assertSame([], $queueBootstrapper->getPayload('sync'));
    }

    public function test_central_operator_session_does_not_authorize_tenant_routes(): void
    {
        [$tenantA] = $this->provisionPair();

        $admin = AdminUser::query()->create([
            'name' => 'Central Operator',
            'email' => 'central-boundary@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'central');

        $domain = $tenantA->domains()->firstOrFail()->domain;

        $response = $this->get("http://{$domain}/");

        $response->assertRedirect('/login');
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'central');
    }

    public function test_failed_provisioning_can_resume_without_destroying_tenant_data(): void
    {
        [$tenantA] = $this->provisionPair();

        $tenantA->run(function (): void {
            User::query()->create([
                'name' => 'Durable Staff',
                'email' => 'durable-staff@example.test',
                'password' => 'StrongPass123',
                'role' => 'staff',
                'is_active' => true,
            ]);
        });

        $tenantA->forceFill([
            'provisioning_status' => 'failed',
            'ready_at' => null,
        ])->save();

        Business::query()
            ->where('tenant_id', $tenantA->getTenantKey())
            ->update(['provisioning_status' => 'failed']);

        /** @var ResumeTenantProvisioning $resume */
        $resume = app(ResumeTenantProvisioning::class);
        $resumed = $resume->handle($tenantA->fresh(), 'NewStrongPass123');

        $this->assertSame('ready', $resumed->provisioning_status);
        $this->assertSame(1, $resumed->domains()->count());

        $resumed->run(function (): void {
            $this->assertTrue(User::query()->where('email', 'durable-staff@example.test')->exists());

            $owner = User::query()->where('role', 'owner')->firstOrFail();
            $this->assertTrue(Hash::check('NewStrongPass123', $owner->password));
            $this->assertTrue($owner->roles()->where('key', 'owner')->exists());
        });
    }

    /**
     * @return array{0: Tenant, 1: Tenant}
     */
    private function provisionPair(): array
    {
        $suffix = strtolower(bin2hex(random_bytes(3)));

        $provision = app(ProvisionTenant::class);

        $tenantA = $provision->handle([
            'company_name' => 'Tenant Alpha',
            'owner_name' => 'Alpha Owner',
            'owner_email' => "owner-alpha-{$suffix}@example.test",
            'owner_phone' => null,
            'slug' => "tenant-alpha-{$suffix}",
            'password' => 'StrongPass123',
        ]);

        $tenantB = $provision->handle([
            'company_name' => 'Tenant Beta',
            'owner_name' => 'Beta Owner',
            'owner_email' => "owner-beta-{$suffix}@example.test",
            'owner_phone' => null,
            'slug' => "tenant-beta-{$suffix}",
            'password' => 'StrongPass123',
        ]);

        return [$tenantA, $tenantB];
    }
}
