<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * عمولة الصيانة في كشف المرتب أصبحت مبلغًا محسوبًا من عمليات الصيانة نفسها (لكل شمعة أو لعقد الباكيج)
 * بدل «عدد عملاء × سعر ثابت». maintenance_commission صار إجمالي المبلغ، وmaintenance_count عدد العمليات.
 * الكشوف المحفوظة تُحوَّل بنفس قيمتها حتى لا يتغير صافي أي مرتب سابق.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payrolls')->update([
            'maintenance_commission' => DB::raw('maintenance_count * maintenance_commission'),
        ]);
    }

    public function down(): void
    {
        DB::table('payrolls')->where('maintenance_count', '>', 0)->update([
            'maintenance_commission' => DB::raw('maintenance_commission / maintenance_count'),
        ]);
    }
};
