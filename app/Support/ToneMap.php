<?php

namespace App\Support;

/**
 * خريطة الألوان الموحّدة — ألوان الشارات والمؤشرات نفسها معرّفة كـ ui-tone-* في partials.shell.components-assets.
 */
class ToneMap
{
    /** لون تعبئة صلب (شرائط التقدم) */
    public static function solid(string $tone): string
    {
        return [
            'green'  => 'bg-brand-green',
            'blue'   => 'bg-blue-500',
            'purple' => 'bg-purple-500',
            'amber'  => 'bg-amber-400',
            'red'    => 'bg-red-400',
            'gray'   => 'bg-gray-300',
        ][$tone] ?? 'bg-gray-300';
    }

    /** تحويل نص الحالة إلى لون */
    public static function forStatus(string $status): string
    {
        $map = [
            // الطلبات
            'قيد المراجعة' => 'amber',
            'تم التأكيد'   => 'blue',
            'قيد التركيب'  => 'purple',
            'تم التسليم'   => 'green',
            'ملغي'         => 'red',
            'مرتجع'        => 'gray',
            // العملاء / الموظفون
            'نشط'      => 'green',
            'غير نشط'  => 'gray',
            'محتمل'    => 'blue',
            'إجازة'    => 'amber',
            'موقوف'    => 'red',
            // المنتجات
            'متاح'   => 'green',
            'منخفض'  => 'amber',
            'نفد'    => 'red',
            // التصنيفات
            'مفعّل' => 'green',
            'معطّل' => 'gray',
            // المدفوعات
            'مكتمل' => 'green',
            'معلق'  => 'amber',
            'فشل'   => 'red',
            // التقسيط
            'منتظم' => 'green',
            'متأخر' => 'red',
            'جديد'  => 'blue',
        ];

        return $map[$status] ?? 'gray';
    }
}
