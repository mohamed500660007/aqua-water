@extends('layouts.report', [
    'route' => 'users.report',
    'query' => [],
    'back' => route('users.index', $listQuery),
])

@use('App\Domain\Users\Enums\Gender')
@use('App\Domain\Users\Enums\UserStatus')

@php
    $total = $summary['total'];
    $money = fn ($amount) => number_format((float) $amount, 2);
    $percent = fn ($count) => ($total ? round($count * 100 / $total, 1) : 0) . '%';

    $scope = collect([
        'بحث' => $search,
        'الحالة' => isset($filters['status']) ? UserStatus::from($filters['status'])->label() : null,
        'الوظيفة' => $filters['role'] ?? null,
        'القسم' => $filters['department'] ?? null,
        'الفرع' => $filters['branch'] ?? null,
        'النوع' => isset($filters['gender']) ? Gender::from($filters['gender'])->label() : null,
    ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join(' — ');
@endphp

@section('title', 'تقرير الموظفين')
@section('scope', $scope ?: 'جميع الموظفين — «المعيّنون خلال الفترة» حسب تاريخ التعيين')

@section('filters')
    <label class="col-span-2 flex flex-col gap-1">
        <span class="text-[11px] font-bold text-gray-500">بحث (الاسم، البريد، الهاتف، الرقم القومي)</span>
        <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="q" type="search" value="{{ $search }}" placeholder="اكتب للبحث..." />
    </label>
    <x-report.filter label="الحالة" name="status" :options="$statuses" :selected="$filters['status'] ?? null" />
    <x-report.filter label="الوظيفة" name="role" :options="$roles" :selected="$filters['role'] ?? null" />
    <x-report.filter label="القسم" name="department" :options="$departments" :selected="$filters['department'] ?? null" />
    <x-report.filter label="الفرع" name="branch" :options="$branches" :selected="$filters['branch'] ?? null" />
    <x-report.filter label="النوع" name="gender" :options="$genders" :selected="$filters['gender'] ?? null" />
    <x-report.filter label="محتوى التقرير" name="content" :options="$contents" :selected="$content" :placeholder="null" />
    <x-report.filter label="ترتيب الكشف حسب" name="sort" :options="$sorts" :selected="request('sort', 'name')" :placeholder="null" />
    <x-report.filter label="اتجاه الترتيب" name="dir" :options="['asc' => 'تصاعدي', 'desc' => 'تنازلي']" :selected="request('dir', 'asc')" :placeholder="null" />
@endsection

@section('content')
    @if ($content !== 'list')
        <x-report.section class="avoid-break" no="1" title="الملخص العام">
            <x-report.figures :highlight="3" :items="[
                ['إجمالي الموظفين', number_format($total), 'موظف'],
                ['المعيّنون خلال الفترة', number_format($summary['hired']), 'موظف'],
                ['متوسط العمر', $summary['average_age'] !== null ? $summary['average_age'] : '—', $summary['average_age'] !== null ? 'سنة' : null],
                ['إجمالي المرتبات الثابتة', $money($summary['salaries']), 'ج.م', 'شهريًا قبل العمولات'],
            ]" />
            <x-report.figures :items="collect(UserStatus::cases())
                ->map(fn ($status) => [$status->label(), number_format($summary['statuses'][$status->value]), 'موظف', $percent($summary['statuses'][$status->value]) . ' من الإجمالي'])
                ->push(['بيانات تحتاج استكمالًا', number_format($summary['missing_national_id'] + $summary['id_needs_renewal']), 'موظف', 'بدون رقم قومي أو بطاقة تحتاج تجديدًا'])
                ->all()" />
        </x-report.section>

        <x-report.section class="avoid-break" no="2" title="التوزيع حسب القسم">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th class="c">الموظفون</th>
                        <th class="c">النشطون</th>
                        <th class="c">النسبة</th>
                        <th class="l">المرتبات الثابتة (ج.م)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byDepartment as $group)
                        <tr>
                            <td class="font-bold">{{ $group['name'] }}</td>
                            <td class="num c">{{ number_format($group['total']) }}</td>
                            <td class="num c">{{ number_format($group['active']) }}</td>
                            <td class="num c">{{ $percent($group['total']) }}</td>
                            <td class="num l font-bold">{{ $money($group['salaries']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="5">لا يوجد موظفون</td></tr>
                    @endforelse
                </tbody>
                @if ($byDepartment->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td>الإجمالي</td>
                            <td class="num c">{{ number_format($total) }}</td>
                            <td class="num c">{{ number_format($summary['statuses']['active']) }}</td>
                            <td class="num c">100%</td>
                            <td class="num l">{{ $money($summary['salaries']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>

        <div class="grid grid-cols-2 gap-4">
            @foreach ([['التوزيع حسب الوظيفة', 'الوظيفة', $byRole], ['التوزيع حسب الفرع', 'الفرع', $byBranch]] as [$title, $label, $groups])
                <x-report.section class="avoid-break" :no="$loop->iteration + 2" :title="$title">
                    <table class="rpt-table">
                        <thead>
                            <tr>
                                <th>{{ $label }}</th>
                                <th class="c">العدد</th>
                                <th class="c">النشطون</th>
                                <th class="l">المرتبات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groups as $group)
                                <tr>
                                    <td class="font-bold">{{ $group['name'] }}</td>
                                    <td class="num c">{{ number_format($group['total']) }}</td>
                                    <td class="num c">{{ number_format($group['active']) }}</td>
                                    <td class="num l">{{ $money($group['salaries']) }}</td>
                                </tr>
                            @empty
                                <tr><td class="is-empty" colspan="4">لا يوجد موظفون</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-report.section>
            @endforeach
        </div>

        <x-report.section class="avoid-break" no="5" title="بيانات تحتاج متابعة" :note="$alerts->count() . ' موظف'">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c w-8">م</th>
                        <th>الموظف</th>
                        <th>الوظيفة / القسم</th>
                        <th>الملاحظة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td class="font-bold">{{ $alert['user']->name }}</td>
                            <td class="text-[10px]">{{ collect([$alert['user']->role, $alert['user']->department])->filter()->join(' — ') ?: '—' }}</td>
                            <td>
                                @foreach ($alert['issues'] as $issue)
                                    <span @class(['tag', 'is-strong' => str_contains($issue, 'منتهية')])>{{ $issue }}</span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="4">بيانات كل الموظفين مكتملة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>
    @endif

    @if ($content !== 'summary')
        <x-report.section :no="$content === 'list' ? 1 : 6" title="كشف الموظفين"
            :note="number_format($rows->count()) . ' موظف' . ($truncated ? ' — الكشف مقتصر على أول ' . number_format($rows->count()) : '')">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>الاسم / الهاتف</th>
                        <th>الرقم القومي</th>
                        <th>الوظيفة / القسم</th>
                        <th>الفرع</th>
                        <th class="c">تاريخ التعيين</th>
                        <th class="c">السن</th>
                        <th class="l">المرتب الثابت</th>
                        <th class="c">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $user)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td>
                                <p class="font-bold leading-tight">{{ $user->name }}</p>
                                <p class="num text-[9.5px] muted text-right" dir="ltr">{{ $user->phone }}</p>
                            </td>
                            <td class="num" dir="ltr">{{ $user->national_id ?? '—' }}</td>
                            <td class="text-[10px]">
                                <p class="font-bold">{{ $user->role ?? '—' }}</p>
                                <p class="muted">{{ $user->department }}</p>
                            </td>
                            <td class="text-[10px]">{{ $user->branch ?? '—' }}</td>
                            <td class="num c" dir="ltr">{{ $user->hire_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="num c">{{ $user->age() ?? '—' }}</td>
                            <td class="num l font-bold">{{ $money($user->base_salary) }}</td>
                            <td class="c">
                                @if ($user->status === UserStatus::Active)
                                    {{ $user->status->label() }}
                                @else
                                    <span @class(['tag', 'is-strong' => $user->status === UserStatus::Suspended])>{{ $user->status->label() }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="9">لا يوجد موظفون مطابقون لنطاق التقرير</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="7">إجمالي المرتبات الثابتة</td>
                            <td class="num l">{{ $money($rows->sum('base_salary')) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    @endif
@endsection
