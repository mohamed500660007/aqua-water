<?php

namespace App\Domain\Users\Services;

use App\Domain\Reports\Data\ReportPeriod;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * تقرير الموظفين المطبوع. كل الأقسام تُحسب على نفس الموظفين المفلترين (نفس فلاتر قائمة الموظفين)،
 * والفترة تحدد فقط «المعيّنين خلال الفترة».
 */
class UserReport
{
    /** حد أقصى لصفوف الكشف المطبوع */
    public const MAX_ROWS = 1000;

    /** الأعمدة المسموح التجميع عليها — تُمرَّر كأسماء أعمدة في SQL فلا تُقبل من المستخدم مباشرة */
    private const GROUPABLE = ['department', 'role', 'branch'];

    /**
     * @return array{total: int, statuses: array<string, int>, hired: int, salaries: float,
     *     missing_national_id: int, id_needs_renewal: int, average_age: float|null}
     */
    public function summary(Builder $users, ReportPeriod $period): array
    {
        $renewalLimit = CarbonImmutable::today()->addMonths(2)->toDateString();

        $totals = (clone $users)->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN hire_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as hired', $period->bounds())
            ->selectRaw('COALESCE(SUM(base_salary), 0) as salaries')
            ->selectRaw('SUM(CASE WHEN national_id IS NULL THEN 1 ELSE 0 END) as missing_national_id')
            ->selectRaw('SUM(CASE WHEN national_id_expires_at IS NOT NULL AND national_id_expires_at <= ? THEN 1 ELSE 0 END) as id_needs_renewal', [$renewalLimit])
            ->first();

        $statuses = (clone $users)->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $ages = (clone $users)->whereNotNull('birth_date')->pluck('birth_date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->age);

        return [
            'total' => (int) ($totals->total ?? 0),
            'statuses' => collect(UserStatus::cases())
                ->mapWithKeys(fn (UserStatus $status) => [$status->value => (int) $statuses->get($status->value, 0)])
                ->all(),
            'hired' => (int) ($totals->hired ?? 0),
            'salaries' => (float) ($totals->salaries ?? 0),
            'missing_national_id' => (int) ($totals->missing_national_id ?? 0),
            'id_needs_renewal' => (int) ($totals->id_needs_renewal ?? 0),
            'average_age' => $ages->isEmpty() ? null : round($ages->avg(), 1),
        ];
    }

    /** @return Collection<int, array{name: string, total: int, active: int, salaries: float}> الأكبر عددًا أولًا */
    public function groupedBy(Builder $users, string $column): Collection
    {
        abort_unless(in_array($column, self::GROUPABLE, true), 500);

        return (clone $users)->toBase()
            ->select($column)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', [UserStatus::Active->value])
            ->selectRaw('COALESCE(SUM(base_salary), 0) as salaries')
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->{$column} ?: 'غير محدد',
                'total' => (int) $row->total,
                'active' => (int) $row->active,
                'salaries' => (float) $row->salaries,
            ]);
    }

    /**
     * موظفون ببيانات ناقصة أو بطاقة تحتاج تجديدًا — لمتابعة شؤون الموظفين.
     *
     * @return Collection<int, array{user: User, issues: list<string>}>
     */
    public function alerts(Builder $users): Collection
    {
        $renewalLimit = CarbonImmutable::today()->addMonths(2);

        return (clone $users)
            ->where(fn (Builder $query) => $query
                ->whereNull('national_id')
                ->orWhereNull('phone')
                ->orWhereNull('emergency_contact_phone')
                ->orWhere('national_id_expires_at', '<=', $renewalLimit->toDateString()))
            ->orderBy('name')
            ->limit(self::MAX_ROWS)
            ->get(['id', 'name', 'role', 'department', 'phone', 'national_id', 'national_id_expires_at', 'emergency_contact_phone'])
            ->map(fn (User $user) => [
                'user' => $user,
                'issues' => array_values(array_filter([
                    $user->national_id ? null : 'بدون رقم قومي',
                    $user->national_id_expires_at?->lte($renewalLimit)
                        ? ($user->national_id_expires_at->isPast() ? 'البطاقة منتهية' : 'البطاقة تنتهي قريبًا')
                        : null,
                    $user->phone ? null : 'بدون رقم هاتف',
                    $user->emergency_contact_phone ? null : 'بدون جهة طوارئ',
                ])),
            ]);
    }

    /**
     * صفوف الكشف. يُجلب صف زائد عن الحد لمعرفة إن كان الكشف مقطوعًا.
     *
     * @return Collection<int, User>
     */
    public function rows(Builder $users, ?string $sort = null, ?string $direction = null): Collection
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $column = ['hire_date' => 'hire_date', 'base_salary' => 'base_salary', 'birth_date' => 'birth_date', 'department' => 'department'][$sort] ?? 'name';

        return (clone $users)
            ->orderBy($column, $direction)
            ->orderBy('name')
            ->limit(self::MAX_ROWS + 1)
            ->get();
    }
}
