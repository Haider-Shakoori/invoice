<?php
namespace App\Actions\Tenancy;

use App\Enums\ProvisioningStatus;
use App\Models\Central\Business;
use App\Models\Central\Tenant;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\Tenancy\ProvisioningRecorder;
use Database\Seeders\TenantBaselineSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class ResumeTenantProvisioning
{
    public function __construct(private readonly ProvisioningRecorder $recorder)
    {
    }

    public function handle(Tenant $tenant, string $ownerPassword): Tenant
    {
        if (! in_array($tenant->provisioning_status, [
            ProvisioningStatus::Pending->value,
            ProvisioningStatus::Failed->value,
        ], true)) {
            throw new RuntimeException('Only pending or failed tenants may be reprovisioned.');
        }

        $business=Business::query()
            ->where('tenant_id',$tenant->getTenantKey())
            ->firstOrFail();

        try {
            $migration=$this->recorder->start($tenant->getTenantKey(),'retry_tenant_migration');
            try {
                Artisan::call('tenants:migrate',['--tenants'=>[$tenant->getTenantKey()]]);
                $this->recorder->success($migration);
            } catch (Throwable $e) {
                $this->recorder->failure($migration,$e);
                throw $e;
            }

            $seed=$this->recorder->start($tenant->getTenantKey(),'retry_baseline_seed_and_owner');
            try {
                $tenant->run(function () use ($business,$ownerPassword): void {
                    app(TenantBaselineSeeder::class)->run();

                    $owner=User::query()->updateOrCreate(
                        ['email'=>$business->owner_email],
                        [
                            'name'=>$business->owner_name,
                            'password'=>Hash::make($ownerPassword),
                            'role'=>'owner',
                            'is_active'=>true,
                        ],
                    );

                    $ownerRole=Role::query()->where('key','owner')->firstOrFail();
                    $owner->roles()->sync([$ownerRole->id]);
                });

                $this->recorder->success($seed);
            } catch (Throwable $e) {
                $this->recorder->failure($seed,$e);
                throw $e;
            }

            $domainName=$tenant->slug.'.'.config('tenancy.tenant_base_domain');
            $domain=$this->recorder->start($tenant->getTenantKey(),'retry_attach_domain',['domain'=>$domainName]);

            try {
                $tenant->domains()->firstOrCreate(['domain'=>$domainName]);
                $this->recorder->success($domain);
            } catch (Throwable $e) {
                $this->recorder->failure($domain,$e);
                throw $e;
            }

            $tenant->forceFill([
                'provisioning_status'=>ProvisioningStatus::Ready->value,
                'ready_at'=>now(),
            ])->save();

            $business->update([
                'provisioning_status'=>ProvisioningStatus::Ready->value,
            ]);

            return $tenant->fresh();
        } catch (Throwable $e) {
            $tenant->forceFill([
                'provisioning_status'=>ProvisioningStatus::Failed->value,
            ])->save();

            $business->update([
                'provisioning_status'=>ProvisioningStatus::Failed->value,
            ]);

            throw $e;
        }
    }
}
