<?php
namespace Database\Seeders;

use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $permissions=[
            'clients.view'=>'View clients',
            'clients.manage'=>'Create and edit clients',
            'drafts.view'=>'View invoice drafts',
            'drafts.manage'=>'Create and edit invoice drafts',
            'drafts.delete'=>'Delete invoice drafts',
            'templates.manage'=>'Change invoice templates',
            'settings.manage'=>'Change company/settings',
            'pdf.export'=>'Export invoice PDFs',
            'staff.manage'=>'Manage tenant staff',
        ];

        foreach ($permissions as $key=>$name) {
            Permission::query()->updateOrCreate(['key'=>$key],['name'=>$name]);
        }

        $rolePermissions=[
            'owner'=>array_keys($permissions),
            'admin'=>array_keys($permissions),
            'staff'=>['clients.view','clients.manage','drafts.view','drafts.manage','pdf.export'],
            'read-only'=>['clients.view','drafts.view'],
        ];

        foreach ($rolePermissions as $roleKey=>$permissionKeys) {
            $role=Role::query()->updateOrCreate(
                ['key'=>$roleKey],
                ['name'=>ucwords(str_replace('-',' ',$roleKey)),'is_system'=>true]
            );

            $role->permissions()->sync(
                Permission::query()->whereIn('key',$permissionKeys)->pluck('id')
            );
        }

        $templates=[
            [1,'executive-navy','Executive Navy'],[2,'minimal-white','Minimal White'],
            [3,'classic-ledger','Classic Ledger'],[4,'modern-indigo','Modern Indigo'],
            [5,'emerald-business','Emerald Business'],[6,'corporate-blue','Corporate Blue'],
            [7,'warm-sand','Warm Sand'],[8,'charcoal-pro','Charcoal Pro'],
            [9,'editorial','Editorial'],[10,'compact-trade','Compact Trade'],
            [11,'signature','Signature'],[12,'borderline','Borderline'],
            [13,'azure-wave','Azure Wave'],[14,'slate-grid','Slate Grid'],
            [15,'gold-accent','Gold Accent'],[16,'mono-statement','Mono Statement'],
            [17,'split-header','Split Header'],[18,'letterhead','Letterhead'],
            [19,'soft-blue','Soft Blue'],[20,'precision','Precision'],
        ];

        foreach ($templates as [$number,$key,$name]) {
            DB::table('invoice_templates')->updateOrInsert(
                ['template_number'=>$number],
                ['key'=>$key,'name'=>$name,'version'=>1,'is_active'=>true,'meta'=>json_encode(['built_in'=>true]),'created_at'=>now(),'updated_at'=>now()]
            );
        }

        DB::table('settings')->updateOrInsert(
            ['key'=>'document.locales'],
            ['value'=>json_encode(['en','fa','ps']),'created_at'=>now(),'updated_at'=>now()]
        );
    }
}
