<?php

namespace App\Support;

/**
 * قوائم ثابتة تخص إعدادات النظام فقط (تنقّل + قوائم اختيار) — لا تحتوي أي بيانات عرض.
 */
class Lookups
{
    /** أقسام لوحة التحكم المستخدمة في التنقل */
    public static function navItems(): array
    {
        return [
            ['route' => 'contracts.index',         'icon' => 'file-text',    'label' => 'عقود البيع',   'match' => 'contracts.*'],
            ['route' => 'customers.index',         'icon' => 'users',        'label' => 'العملاء',      'match' => 'customers.*'],
            ['route' => 'products.index',          'icon' => 'package',      'label' => 'المنتجات',     'match' => 'products.*'],
            ['route' => 'inventory.index',         'icon' => 'boxes',        'label' => 'المخزون',      'match' => 'inventory.*'],
            ['route' => 'maintenance-plans.index', 'icon' => 'shield-check', 'label' => 'خطط الصيانة',  'match' => 'maintenance-plans.*'],
            ['route' => 'users.index',             'icon' => 'user-cog',     'label' => 'الموظفون',     'match' => 'users.*'],
        ];
    }

    /** الأقسام الأساسية في شريط التنقل السفلي على الهاتف */
    public static function bottomNav(): array
    {
        return [
            ['route' => 'contracts.index', 'icon' => 'file-text', 'label' => 'العقود',   'match' => 'contracts.*'],
            ['route' => 'customers.index', 'icon' => 'users',     'label' => 'العملاء',  'match' => 'customers.*'],
            ['route' => 'products.index',  'icon' => 'package',   'label' => 'المنتجات', 'match' => 'products.*'],
            ['route' => 'inventory.index', 'icon' => 'boxes',     'label' => 'المخزون',  'match' => 'inventory.*'],
        ];
    }

    public static function governorates(): array
    {
        return ['القاهرة', 'الجيزة', 'الإسكندرية', 'الدقهلية', 'الغربية', 'الشرقية', 'أسيوط', 'بورسعيد', 'القليوبية'];
    }

    public static function roles(): array
    {
        return ['مدير النظام', 'مدير مبيعات', 'مندوب مبيعات', 'فني تركيب', 'خدمة عملاء', 'محاسب'];
    }

    public static function departments(): array
    {
        return ['الإدارة', 'المبيعات', 'العمليات', 'خدمة العملاء', 'المالية'];
    }

    public static function branches(): array
    {
        return ['القاهرة', 'الجيزة', 'الإسكندرية', 'الدقهلية', 'الشرقية'];
    }
}
