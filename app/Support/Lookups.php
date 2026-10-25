<?php

namespace App\Support;

use App\Domain\Settings\Settings;

/**
 * عناصر التنقل وقوائم الاختيار. القوائم القابلة للتعديل (المحافظات، الأقسام...) تُقرأ من الإعدادات،
 * والأدوار ثابتة لأن صلاحيات العقود والعهد والصيانة مرتبطة بأسمائها.
 */
class Lookups
{
    /**
     * أقسام لوحة التحكم مجمّعة حسب المجال — تُستخدم في شريط التنقل العلوي وقائمة الهاتف.
     * كل عنصر يشير إلى مسار حقيقي موجود في التطبيق (لا روابط معطّلة).
     * hint وtone يظهران في لوحة القسم المنبثقة، وaction هو الاختصار السريع للمجموعة.
     */
    public static function navGroups(): array
    {
        return [
            [
                'label' => 'المبيعات',
                'icon' => 'trending-up',
                'tagline' => 'العملاء والعقود وتحصيل الأقساط في مكان واحد.',
                'action' => ['route' => 'contracts.create', 'label' => 'عقد جديد', 'icon' => 'plus'],
                'items' => [
                    ['route' => 'customers.index',    'icon' => 'users',     'label' => 'العملاء',    'tone' => 'blue',   'hint' => 'قاعدة العملاء وأرقامهم وعناوينهم'],
                    ['route' => 'contracts.index',    'icon' => 'file-text', 'label' => 'عقود البيع', 'tone' => 'purple', 'hint' => 'عقود الأجهزة والصيانة نقدًا وتقسيطًا'],
                    ['route' => 'installments.index', 'icon' => 'wallet',    'label' => 'الأقساط',    'tone' => 'green',  'hint' => 'المستحق والمتأخر والتحصيل والإيصالات'],
                ],
            ],
            [
                'label' => 'المنتجات والمخزون',
                'icon' => 'boxes',
                'tagline' => 'الأصناف والمخازن وما يحمله كل موظف من عهدة.',
                'action' => ['route' => 'inventory.purchase-orders.create', 'label' => 'طلبية وارد', 'icon' => 'truck'],
                'items' => [
                    ['route' => 'products.index',                      'icon' => 'package',          'label' => 'المنتجات',       'tone' => 'orange', 'hint' => 'الأجهزة وقطع الغيار وأسعارها'],
                    ['route' => 'inventory.purchase-orders.index',     'icon' => 'store',            'label' => 'المخزن',         'tone' => 'teal',   'hint' => 'طلبيات الوارد وأرصدة المخازن'],
                    ['route' => 'inventory.employee-stocks.index',     'icon' => 'user-round-check', 'label' => 'مخزون الموظفين', 'tone' => 'blue',   'hint' => 'عهدة المناديب والفنيين'],
                    ['route' => 'inventory.custody-settlements.index', 'icon' => 'scale',            'label' => 'تسويات العهد',   'tone' => 'amber',  'hint' => 'تصحيح العجز والزيادة في العهد'],
                ],
            ],
            [
                'label' => 'الإدارة والتقارير',
                'icon' => 'clipboard-list',
                'tagline' => 'الصورة الكاملة للشركة: المالية والمبيعات والمخزون والفريق.',
                'action' => ['route' => 'reports.executive', 'label' => 'الملخص التنفيذي', 'icon' => 'gauge'],
                'items' => [
                    ['route' => 'reports.index', 'icon' => 'clipboard-list', 'label' => 'ملف التقارير', 'tone' => 'purple', 'hint' => 'كل تقارير الشركة في مكان واحد'],
                    ['route' => 'settings.index', 'icon' => 'settings', 'label' => 'الإعدادات', 'tone' => 'gray', 'hint' => 'بيانات الشركة والطباعة والقوائم'],
                ],
            ],
            [
                'label' => 'الصيانة والفريق',
                'icon' => 'sparkles',
                'tagline' => 'خدمة ما بعد البيع وكل ما يخص فريق العمل.',
                'action' => ['route' => 'complaints.create', 'label' => 'شكوى جديدة', 'icon' => 'message-square-plus'],
                'items' => [
                    ['route' => 'maintenance.file',        'icon' => 'calendar-clock',         'label' => 'ملف الصيانة',        'tone' => 'green',  'hint' => 'الشمع اللي لسه ما اتغيرش'],
                    ['route' => 'maintenance.plan',        'icon' => 'shield-check',           'label' => 'خطة الصيانة',        'tone' => 'teal',   'hint' => 'سبع شمعات بمواعيد ثابتة'],
                    ['route' => 'complaints.index',        'icon' => 'message-square-warning', 'label' => 'الشكاوى',            'tone' => 'red',    'hint' => 'بلاغات العملاء وزيارات الفنيين'],
                    ['route' => 'gifts.index',             'icon' => 'gift',                   'label' => 'الهدايا',            'tone' => 'purple', 'hint' => 'قطع مجانية للعملاء'],
                    ['route' => 'users.index',             'icon' => 'user-cog',               'label' => 'الموظفون',           'tone' => 'blue',   'hint' => 'بيانات الفريق وأدوارهم'],
                    ['route' => 'payroll.index',           'icon' => 'banknote',               'label' => 'المرتبات والعمولات', 'tone' => 'green',  'hint' => 'الكشوف الشهرية والصرف'],
                ],
            ],
        ];
    }

    /** المجموعة التي تنتمي إليها الصفحة الحالية — تظهر كعنوان صغير فوق عنوان الصفحة */
    public static function activeNavGroup(): ?array
    {
        foreach (static::navGroups() as $group) {
            foreach ($group['items'] as $item) {
                if (static::isNavActive(route($item['route']))) {
                    return $group;
                }
            }
        }

        return null;
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
            ['route' => 'products.index',                  'icon' => 'package', 'label' => 'المنتجات'],
            ['route' => 'inventory.employee-stocks.index', 'icon' => 'boxes',   'label' => 'المخزون'],
        ];
    }

    public static function religions(): array
    {
        return app(Settings::class)->list('religions');
    }

    /** مقترحات لخانة المؤهل الدراسي (يمكن كتابة غيرها) */
    public static function qualifications(): array
    {
        return app(Settings::class)->list('qualifications');
    }

    /** مقترحات لصلة القرابة لجهة اتصال الطوارئ */
    public static function relations(): array
    {
        return app(Settings::class)->list('relations');
    }

    public static function governorates(): array
    {
        return app(Settings::class)->list('governorates');
    }

    public static function roles(): array
    {
        return ['مدير النظام', 'مدير مبيعات', 'مندوب مبيعات', 'مندوبة', 'فني تركيب', 'محصل', 'سكرتارية', 'خدمة عملاء', 'محاسب'];
    }

    public static function departments(): array
    {
        return app(Settings::class)->list('departments');
    }

    public static function branches(): array
    {
        return app(Settings::class)->list('branches');
    }
}
