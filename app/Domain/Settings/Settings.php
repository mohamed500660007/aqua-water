<?php

namespace App\Domain\Settings;

use App\Domain\Settings\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * إعدادات النظام: سجل واحد بكل مفتاح (قسمه، نوعه، قيمته الافتراضية، حدوده) يبني صفحة الإعدادات
 * وقواعد التحقق وقيم العرض معًا. القيم المحفوظة تُقرأ باستعلام واحد عند أول طلب في الـ request،
 * وأي مفتاح غير محفوظ يرجع لقيمته الافتراضية — فالنظام يعمل كما هو قبل حفظ أي إعداد.
 */
class Settings
{
    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const INTEGER = 'integer';

    public const DECIMAL = 'decimal';

    /** قائمة: سطر لكل عنصر في النموذج، ومصفوفة نصوص في التخزين */
    public const LIST = 'list';

    /** @var array<string, mixed>|null القيم المحفوظة — تُحمَّل مرة واحدة لكل request */
    private ?array $stored = null;

    /**
     * أقسام الإعدادات وحقولها بترتيب العرض.
     *
     * @return array<string, array{label: string, icon: string, hint: string, fields: array<string, array<string, mixed>>}>
     */
    public static function sections(): array
    {
        return [
            'company' => [
                'label' => 'بيانات الشركة',
                'icon' => 'building-2',
                'hint' => 'تظهر في رأس اللوحة والتقارير والإيصالات وقسائم المرتبات',
                'fields' => [
                    'company_name' => ['type' => self::TEXT, 'label' => 'اسم الشركة', 'default' => 'أكوا ووتر', 'max' => 60, 'required' => true],
                    'company_tagline' => ['type' => self::TEXT, 'label' => 'الوصف المختصر', 'default' => 'لأنظمة تنقية المياه', 'max' => 80],
                    'company_phone' => ['type' => self::TEXT, 'label' => 'تليفون الشركة', 'default' => config('app.company_phone'), 'max' => 40,
                        'hint' => 'يُطبع على كل إيصال تحصيل'],
                    'company_address' => ['type' => self::TEXT, 'label' => 'العنوان', 'default' => null, 'max' => 200],
                    'company_email' => ['type' => self::TEXT, 'label' => 'البريد الإلكتروني', 'default' => null, 'max' => 120, 'email' => true],
                    'commercial_register' => ['type' => self::TEXT, 'label' => 'رقم السجل التجاري', 'default' => null, 'max' => 60],
                    'tax_number' => ['type' => self::TEXT, 'label' => 'رقم البطاقة الضريبية', 'default' => null, 'max' => 60],
                    'manager_name' => ['type' => self::TEXT, 'label' => 'اسم مسؤولة الحسابات في الترحيب', 'default' => 'هبة', 'max' => 40, 'required' => true,
                        'hint' => 'يظهر في «مرحبًا بكِ يا أستاذة …» على الصفحة الرئيسية'],
                ],
            ],
            'sales' => [
                'label' => 'العقود والأقساط',
                'icon' => 'file-text',
                'hint' => 'القيم التي يبدأ بها نموذج العقد الجديد — يمكن تغييرها في كل عقد',
                'fields' => [
                    'default_installments_count' => ['type' => self::INTEGER, 'label' => 'عدد الأقساط الافتراضي', 'default' => 6, 'min' => 1, 'max' => 120],
                    'default_installment_period' => ['type' => self::INTEGER, 'label' => 'القسط كل كام شهر', 'default' => 1, 'min' => 1, 'max' => 12],
                    'first_due_after_months' => ['type' => self::INTEGER, 'label' => 'أول قسط بعد كام شهر من التعاقد', 'default' => 1, 'min' => 0, 'max' => 12],
                    'default_interest_rate' => ['type' => self::DECIMAL, 'label' => 'نسبة الفائدة الافتراضية %', 'default' => 0, 'min' => 0, 'max' => 100,
                        'hint' => 'من المتبقي بعد المقدم — تُحسب تلقائيًا في نموذج العقد ويمكن تعديلها'],
                ],
            ],
            'printing' => [
                'label' => 'الإيصالات والطباعة',
                'icon' => 'printer',
                'hint' => 'نصوص ثابتة تُطبع على كل الإيصالات والتقارير بلا استثناء',
                'fields' => [
                    'receipt_terms' => ['type' => self::TEXTAREA, 'label' => 'شرط التأخير على الإيصال', 'max' => 300, 'required' => true,
                        'default' => 'في حالة تأخير القسط للشهر التالي تُضاف 50 جنيه — في حالة التأخير ليوم آخر إضافة 10 جنيه'],
                    'receipt_footnote' => ['type' => self::TEXTAREA, 'label' => 'ملاحظة أسفل الإيصال', 'max' => 300,
                        'default' => 'هذا الإيصال لا يُعتد به إلا بعد التوقيع والختم — يُرجى الاحتفاظ به لحين انتهاء الأقساط.'],
                    'report_signatures' => ['type' => self::LIST, 'label' => 'خانات التوقيع أسفل التقارير', 'max_items' => 4, 'required' => true,
                        'default' => ['أعدّه', 'راجعه', 'اعتماد المدير'], 'hint' => 'سطر لكل خانة — حتى 4 خانات'],
                ],
            ],
            'lists' => [
                'label' => 'قوائم الاختيار',
                'icon' => 'list',
                'hint' => 'الخيارات التي تظهر في النماذج والفلاتر — سطر لكل عنصر',
                'fields' => [
                    'governorates' => ['type' => self::LIST, 'label' => 'المحافظات', 'required' => true,
                        'default' => ['القاهرة', 'الجيزة', 'الإسكندرية', 'الدقهلية', 'الغربية', 'الشرقية', 'أسيوط', 'بورسعيد', 'القليوبية']],
                    'departments' => ['type' => self::LIST, 'label' => 'أقسام الشركة', 'required' => true,
                        'default' => ['الإدارة', 'السكرتارية', 'المبيعات', 'الفنيين', 'الحسابات', 'المحصّلين', 'الجودة', 'الأرشيف']],
                    'branches' => ['type' => self::LIST, 'label' => 'الفروع', 'required' => true,
                        'default' => ['القاهرة', 'الجيزة', 'الإسكندرية', 'الدقهلية', 'الشرقية']],
                    'qualifications' => ['type' => self::LIST, 'label' => 'المؤهلات الدراسية (مقترحات)',
                        'default' => ['بدون مؤهل', 'إعدادية', 'دبلوم صنايع', 'دبلوم تجارة', 'دبلوم زراعة', 'ثانوية عامة', 'معهد فوق متوسط', 'بكالوريوس', 'ليسانس', 'دراسات عليا']],
                    'relations' => ['type' => self::LIST, 'label' => 'صلة القرابة لجهة الطوارئ (مقترحات)',
                        'default' => ['الأب', 'الأم', 'الزوج', 'الزوجة', 'الأخ', 'الأخت', 'الابن', 'الابنة', 'قريب', 'صديق']],
                    'religions' => ['type' => self::LIST, 'label' => 'الديانات', 'required' => true, 'default' => ['مسلم', 'مسيحي']],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> تعريف مفتاح واحد مع اسم قسمه */
    public static function field(string $key): array
    {
        foreach (self::sections() as $section => $definition) {
            if (isset($definition['fields'][$key])) {
                return $definition['fields'][$key] + ['section' => $section];
            }
        }

        throw new InvalidArgumentException("Unknown setting [{$key}].");
    }

    public function get(string $key): mixed
    {
        $field = self::field($key);
        $stored = $this->stored();

        return array_key_exists($key, $stored) && $stored[$key] !== null ? $stored[$key] : $field['default'];
    }

    /** @return list<string> */
    public function list(string $key): array
    {
        return array_values((array) $this->get($key));
    }

    /** @return array<string, mixed> قيم قسم كامل كما تُعرض في النموذج */
    public function section(string $section): array
    {
        return collect(self::sections()[$section]['fields'])->map(fn ($field, string $key) => $this->get($key))->all();
    }

    /**
     * يحفظ قيم قسم واحد بعد التحقق منها، داخل معاملة واحدة.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(string $section, array $values): void
    {
        DB::transaction(function () use ($section, $values) {
            foreach (array_keys(self::sections()[$section]['fields']) as $key) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => $this->normalize($key, $values[$key] ?? null)]);
            }
        });

        $this->stored = null;
    }

    /** يعيد قسمًا لقيمه الافتراضية بحذف المحفوظ منه */
    public function reset(string $section): void
    {
        Setting::query()->whereKey(array_keys(self::sections()[$section]['fields']))->delete();

        $this->stored = null;
    }

    private function normalize(string $key, mixed $value): mixed
    {
        return match (self::field($key)['type']) {
            self::INTEGER => $value === null || $value === '' ? null : (int) $value,
            self::DECIMAL => $value === null || $value === '' ? null : round((float) $value, 2),
            self::LIST => self::lines($value),
            default => trim((string) $value) === '' ? null : trim((string) $value),
        };
    }

    /** نص بسطر لكل عنصر (أو مصفوفة) إلى قائمة نظيفة بلا تكرار */
    public static function lines(mixed $value): array
    {
        $items = is_array($value) ? $value : preg_split('/\R/u', (string) $value);

        return array_values(array_unique(array_filter(array_map(fn ($item) => trim((string) $item), $items), fn (string $item) => $item !== '')));
    }

    /** @return array<string, mixed> */
    private function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        try {
            return $this->stored = Setting::query()->pluck('value', 'key')->all();
        } catch (QueryException) {
            // قاعدة لم تُرحَّل بعد (أوامر التثبيت الأولى): تعمل القيم الافتراضية
            return $this->stored = [];
        }
    }
}
