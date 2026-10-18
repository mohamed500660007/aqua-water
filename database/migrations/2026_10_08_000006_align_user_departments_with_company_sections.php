<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * أقسام الشركة الفعلية (تقرير المرتبات حسب القسم) تختلف عن القائمة المبدئية،
 * فتُنقل الأقسام القديمة إلى ما يقابلها حتى لا يُرفض تعديل الموظف لاحقًا.
 */
return new class extends Migration
{
    private const MAP = [
        'المالية' => 'الحسابات',
        'العمليات' => 'الفنيين',
        'خدمة العملاء' => 'السكرتارية',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('users')->where('department', $old)->update(['department' => $new]);
        }
    }

    public function down(): void
    {
        // التحويل لا يُعكس: بعد الترحيل لا يمكن تمييز من كان في القسم القديم ممن أُضيف للجديد.
    }
};
