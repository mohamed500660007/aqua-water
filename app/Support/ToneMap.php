<?php

namespace App\Support;

/**
 * خريطة الألوان الموحّدة للشارات والأيقونات — تحافظ على نفس نظام التصميم.
 */
class ToneMap
{
    /** خلفية ناعمة + لون نص (تُستخدم لمربعات الأيقونات) */
    public static function soft(string $tone): string
    {
        return [
            'green'  => 'bg-brand-green/25 text-green-700',
            'blue'   => 'bg-blue-50 text-blue-500',
            'purple' => 'bg-purple-50 text-purple-600',
            'amber'  => 'bg-amber-50 text-amber-600',
            'red'    => 'bg-red-50 text-red-500',
            'gray'   => 'bg-gray-100 text-gray-500',
            'teal'   => 'bg-teal-50 text-teal-600',
            'orange' => 'bg-orange-50 text-orange-500',
        ][$tone] ?? 'bg-gray-100 text-gray-500';
    }

    /** شارة الحالة (badge) */
    public static function badge(string $tone): string
    {
        return [
            'green'  => 'bg-brand-green/30 text-green-700',
            'blue'   => 'bg-blue-100 text-blue-600',
            'purple' => 'bg-purple-100 text-purple-600',
            'amber'  => 'bg-amber-100 text-amber-700',
            'red'    => 'bg-red-100 text-red-600',
            'gray'   => 'bg-gray-100 text-gray-600',
            'teal'   => 'bg-teal-100 text-teal-700',
            'orange' => 'bg-orange-100 text-orange-600',
        ][$tone] ?? 'bg-gray-100 text-gray-600';
    }

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
