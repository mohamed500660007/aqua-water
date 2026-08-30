<?php

namespace App\Support;

/**
 * قوائم ثابتة تخص إعدادات النظام فقط (تنقّل + قوائم اختيار) — لا تحتوي أي بيانات عرض.
 */
class Lookups
{
    /**
     * أقسام لوحة التحكم مجمّعة حسب المجال — تُستخدم في الشريط الجانبي وقائمة الهاتف.
     * كل عنصر يشير إلى مسار حقيقي موجود في التطبيق (لا روابط معطّلة).
     */
    public static function navGroups(): array
    {
        return [
            [
                'label' => 'المبيعات',
                'items' => [
                    ['route' => 'contracts.index',  'icon' => 'file-text',  'label' => 'عقود البيع'],
                    ['route' => 'contracts.create', 'icon' => 'file-plus',  'label' => 'عقد جديد'],
                    ['route' => 'customers.index',  'icon' => 'users',      'label' => 'العملاء'],
                    ['route' => 'customers.create', 'icon' => 'user-plus',  'label' => 'عميل جديد'],
                ],
            ],
            [
                'label' => 'المنتجات والمخزون',
                'items' => [
                    ['route' => 'products.index',                  'icon' => 'package',           'label' => 'المنتجات'],
                    ['route' => 'inventory.index',                 'icon' => 'boxes',             'label' => 'أرصدة المخزن'],
                    ['route' => 'inventory.movements.index',       'icon' => 'arrow-left-right',  'label' => 'حركات المخزون'],
                    ['route' => 'inventory.employee-stocks.index', 'icon' => 'truck',             'label' => 'مخزون الموظفين'],
                ],
            ],
            [
                'label' => 'الصيانة والفريق',
                'items' => [
                    ['route' => 'maintenance-plans.index', 'icon' => 'shield-check', 'label' => 'خطط الصيانة'],
                    ['route' => 'users.index',             'icon' => 'user-cog',     'label' => 'الموظفون'],
                ],
            ],
        ];
    }

    /** كل عناصر التنقل في قائمة واحدة مسطّحة */
    public static function navItems(): array
    {
        return collect(static::navGroups())->flatMap(fn (array $group) => $group['items'])->all();
    }

    /**
     * هل هذا الرابط هو عنصر التنقل النشط للصفحة الحالية؟
     *
     * تُطبَّق قاعدة "أطول مسار مطابق" نفسها المستخدمة في الواجهة ($store.nav)،
     * حتى تتطابق الحالة المرسومة من الخادم مع ما يحسبه Alpine فلا يحدث أي وميض:
     * صفحة /contracts/create تُفعّل «عقد جديد» وحدها لا «عقود البيع» معها.
     */
    public static function isNavActive(string $href): bool
    {
        return static::normalizePath($href) === static::activeNavPath();
    }

    /** المسار النشط حاليًا — يُحسب مرة واحدة لكل طلب */
    private static function activeNavPath(): string
    {
        static $cache = [];

        $path = static::normalizePath(request()->getPathInfo());

        if (array_key_exists($path, $cache)) {
            return $cache[$path];
        }

        $best = '';

        foreach (static::navItems() as $item) {
            $candidate = static::normalizePath(route($item['route']));

            if ($path !== $candidate && ! str_starts_with($path, $candidate.'/')) {
                continue;
            }

            if (strlen($candidate) > strlen($best)) {
                $best = $candidate;
            }
        }

        return $cache[$path] = $best;
    }

    private static function normalizePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return rtrim($path, '/') ?: '/';
    }

    /** الأقسام الأساسية في شريط التنقل السفلي على الهاتف */
    public static function bottomNav(): array
    {
        return [
            ['route' => 'contracts.index', 'icon' => 'file-text', 'label' => 'العقود'],
            ['route' => 'customers.index', 'icon' => 'users',     'label' => 'العملاء'],
            ['route' => 'products.index',  'icon' => 'package',   'label' => 'المنتجات'],
            ['route' => 'inventory.index', 'icon' => 'boxes',     'label' => 'المخزون'],
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
