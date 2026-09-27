<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TenantBaselineSeeder extends Seeder
{
    public function run(): void
    {
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
