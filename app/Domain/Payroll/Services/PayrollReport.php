<?php

namespace App\Domain\Payroll\Services;

use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Users\Models\User;
use Illuminate\Support\Collection;

/**
 * تقرير المرتبات والعمولات لفترة من شهر أو أكثر. يعتمد على PayrollService::monthlyRows نفسه
 * الذي يبني صفحة المرتبات (الكشف المعتمد إن وُجد، وإلا المحسوب تلقائيًا من حركة الشهر)،
 * فتطابق أرقام التقرير ما يظهر في الصفحة. عدد الاستعلامات ثابت لكل شهر، لا لكل موظف.
 */
class PayrollReport
{
    /** مكوّنات المستحق كما تُعرض في التقرير: [المفتاح، العنوان، مفتاح العدد إن وُجد] */
    public const COMPONENTS = [
        ['base', 'المرتب الثابت', null],
        ['installments', 'عمولة تحصيل الأقساط', 'installment_count'],
        ['installation', 'عمولة تركيب الأجهزة', 'installed_device_count'],
        ['marketing', 'عمولة تسويق الأجهزة', 'marketed_device_count'],
        ['maintenance', 'عمولة الصيانة', 'maintenance_count'],
        ['bonus', 'المكافآت', null],
    ];

    /** حالات الصرف لفلتر التقرير — «غير معتمد» حالة مستقلة لا تدخل فيها */
    public const PAYMENTS = ['unpaid' => 'لم يُصرف', 'partial' => 'صُرف جزئيًا', 'paid' => 'تم الصرف'];

    public function __construct(private readonly PayrollService $payrolls) {}

    /**
     * صف لكل موظف مجمّعًا على كل شهور الفترة.
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(Collection $users, ReportPeriod $period): Collection
    {
        $rows = $users->mapWithKeys(fn (User $user) => [$user->id => $this->emptyRow($user)])->all();

        foreach ($period->months() as $month) {
            foreach ($this->payrolls->monthlyRows($month, $users) as $monthly) {
                $this->accumulate($rows[$monthly['user']->id], $monthly);
            }
        }

        return collect($rows)
            ->map(fn (array $row) => $row + ['status' => $this->paymentStatus($row)])
            ->values();
    }

    /**
     * فلترة الصفوف المجمّعة بحالة الاعتماد والصرف ثم ترتيبها — تتم في الذاكرة لأنها قيم محسوبة
     * عبر الشهور (لا أعمدة في جدول) وعدد الموظفين صغير.
     *
     * @param  array<string, string>  $filters
     */
    public function filterAndSort(Collection $rows, array $filters, ?string $sort, ?string $direction): Collection
    {
        $rows = $rows
            ->when(($filters['approval'] ?? null) === 'approved', fn ($rows) => $rows->where('approved_months', '>', 0))
            ->when(($filters['approval'] ?? null) === 'pending', fn ($rows) => $rows->where('approved_months', 0))
            ->when(isset($filters['payment']), fn ($rows) => $rows->filter(
                fn (array $row) => $row['status']['key'] === $filters['payment']
            ));

        $key = match ($sort) {
            'department' => fn (array $row) => $row['user']->department ?? '',
            'net' => fn (array $row) => $row['net'],
            'remaining' => fn (array $row) => $row['remaining'],
            default => fn (array $row) => $row['user']->name,
        };

        return ($direction === 'desc' ? $rows->sortByDesc($key) : $rows->sortBy($key))->values();
    }

    /** @return array<string, float|int> إجماليات كل الصفوف */
    public function totals(Collection $rows): array
    {
        $keys = ['base', 'installments', 'installation', 'marketing', 'maintenance', 'bonus', 'deductions', 'net', 'paid', 'remaining',
            'installment_count', 'installed_device_count', 'marketed_device_count', 'maintenance_count', 'absence_days',
            'approved_months', 'months'];

        return collect($keys)->mapWithKeys(fn (string $key) => [$key => round($rows->sum($key), 2)])->all()
            + ['employees' => $rows->count()];
    }

    /** @return Collection<int, array{name: string, employees: int, net: float, paid: float, remaining: float}> الأعلى تكلفة أولًا */
    public function byDepartment(Collection $rows): Collection
    {
        return $rows->groupBy(fn (array $row) => $row['user']->department ?: 'غير محدد')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'employees' => $group->count(),
                'net' => round($group->sum('net'), 2),
                'paid' => round($group->sum('paid'), 2),
                'remaining' => round($group->sum('remaining'), 2),
            ])
            ->sortByDesc('net')
            ->values();
    }

    private function emptyRow(User $user): array
    {
        return [
            'user' => $user,
            'base' => 0.0, 'installments' => 0.0, 'installation' => 0.0, 'marketing' => 0.0, 'maintenance' => 0.0,
            'bonus' => 0.0, 'deductions' => 0.0, 'net' => 0.0, 'paid' => 0.0, 'remaining' => 0.0,
            'installment_count' => 0, 'installed_device_count' => 0, 'marketed_device_count' => 0, 'maintenance_count' => 0,
            'absence_days' => 0, 'approved_months' => 0, 'months' => 0,
        ];
    }

    /** يضيف كشف شهر واحد إلى صف الموظف — نفس معادلة PayrollService::total() مفصّلة لمكوّناتها */
    private function accumulate(array &$row, array $monthly): void
    {
        $values = $monthly['values'];
        $payroll = $monthly['payroll'];
        $paid = $payroll ? $payroll->paidAmount() : 0.0;

        $row['base'] += $values['base_salary'];
        $row['installments'] += $values['installment_count'] * $values['installment_commission'];
        $row['installation'] += $values['installed_device_count'] * $values['installation_commission'];
        $row['marketing'] += $values['marketed_device_count'] * $values['marketing_commission'];
        $row['maintenance'] += $values['maintenance_commission'];
        $row['bonus'] += $values['bonus'];
        $row['deductions'] += $values['regular_deduction'] + $values['attendance_deduction'];
        $row['net'] += $monthly['total'];
        $row['paid'] += $paid;
        $row['remaining'] += $payroll ? $payroll->remainingAmount() : $monthly['total'];

        foreach (['installment_count', 'installed_device_count', 'marketed_device_count', 'maintenance_count', 'absence_days'] as $key) {
            $row[$key] += $values[$key];
        }

        $row['approved_months'] += $payroll ? 1 : 0;
        $row['months']++;
    }

    /** @return array{key: string, label: string} */
    private function paymentStatus(array $row): array
    {
        $key = match (true) {
            $row['approved_months'] === 0 => 'pending',
            // كشف معتمد بصافي صفر لا يعني أنه «صُرف» — لا شيء مستحق أصلًا
            $row['net'] <= 0.01 => 'none',
            $row['remaining'] <= 0.01 => 'paid',
            $row['paid'] > 0 => 'partial',
            default => 'unpaid',
        };

        return ['key' => $key, 'label' => self::PAYMENTS[$key] ?? ['pending' => 'غير معتمد', 'none' => 'لا مستحقات'][$key]];
    }
}
