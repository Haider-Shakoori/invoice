<?php
namespace App\Models\Central;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;
    public static function getCustomColumns(): array { return ['id','slug','provisioning_status','ready_at']; }
    protected $fillable=['id','slug','provisioning_status','ready_at'];
    protected function casts(): array { return ['ready_at'=>'datetime']; }
}
