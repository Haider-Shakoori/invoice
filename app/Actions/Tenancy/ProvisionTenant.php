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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\DatabaseManager as TenancyDatabaseManager;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Throwable;

class ProvisionTenant
{
    public function __construct(private readonly ProvisioningRecorder $recorder)
    {
    }

    public function handle(array $data): Tenant
    {
        $slug=Str::lower($data['slug']);

        $tenant=DB::transaction(function () use ($data,$slug): Tenant {
            $tenant=Tenant::query()->create([
                'id'=>(string) Str::uuid(),
                'slug'=>$slug,
                'provisioning_status'=>ProvisioningStatus::Pending->value,
            ]);

            Business::query()->create([
                'tenant_id'=>$tenant->getTenantKey(),
                'display_name'=>$data['company_name'],
                'owner_name'=>$data['owner_name'],
                'owner_email'=>$data['owner_email'],
                'owner_phone'=>$data['owner_phone'] ?? null,
                'status'=>'trial',
                'trial_ends_at'=>now()->addDays((int) config('invoice.trial_days',7)),
                'provisioning_status'=>ProvisioningStatus::Pending->value,
            ]);

            return $tenant;
        });

        try {
            $database=$this->recorder->start($tenant->getTenantKey(),'create_database');
            try {
                (new CreateDatabase($tenant))->handle(app(TenancyDatabaseManager::class));

                $tenant->forceFill([
                    'provisioning_status'=>ProvisioningStatus::DatabaseCreated->value,
                ])->save();

                Business::query()->where('tenant_id',$tenant->getTenantKey())
                    ->update(['provisioning_status'=>ProvisioningStatus::DatabaseCreated->value]);

                $this->recorder->success($database);
            } catch (Throwable $e) {
                $this->recorder->failure($database,$e);
                throw $e;
            }

            $migration=$this->recorder->start($tenant->getTenantKey(),'tenant_migration');
            try {
                Artisan::call('tenants:migrate',['--tenants'=>[$tenant->getTenantKey()]]);
                $this->recorder->success($migration);
            } catch (Throwable $e) {
                $this->recorder->failure($migration,$e);
                throw $e;
            }

            $seed=$this->recorder->start($tenant->getTenantKey(),'baseline_seed_and_owner');
            try {
                $tenant->run(function () use ($data): void {
                    app(TenantBaselineSeeder::class)->run();

                    $owner=User::query()->create([
                        'name'=>$data['owner_name'],
                        'email'=>$data['owner_email'],
                        'password'=>Hash::make($data['password']),
                        'role'=>'owner',
                        'is_active'=>true,
                    ]);

                    $ownerRole=Role::query()->where('key','owner')->firstOrFail();
                    $owner->roles()->sync([$ownerRole->id]);
                });

                $this->recorder->success($seed);
            } catch (Throwable $e) {
                $this->recorder->failure($seed,$e);
                throw $e;
            }

            $domain=$this->recorder->start($tenant->getTenantKey(),'attach_domain',['slug'=>$slug]);
            try {
                $tenant->domains()->create([
                    'domain'=>$slug.'.'.config('tenancy.tenant_base_domain'),
                ]);

                $this->recorder->success($domain);
            } catch (Throwable $e) {
                $this->recorder->failure($domain,$e);
                throw $e;
            }

            $tenant->forceFill([
                'provisioning_status'=>ProvisioningStatus::Ready->value,
                'ready_at'=>now(),
            ])->save();

            Business::query()->where('tenant_id',$tenant->getTenantKey())
                ->update(['provisioning_status'=>ProvisioningStatus::Ready->value]);

            return $tenant->fresh();
        } catch (Throwable $e) {
            $tenant->forceFill(['provisioning_status'=>ProvisioningStatus::Failed->value])->save();

            Business::query()->where('tenant_id',$tenant->getTenantKey())
                ->update(['provisioning_status'=>ProvisioningStatus::Failed->value]);

            throw $e;
        }
    }
}
