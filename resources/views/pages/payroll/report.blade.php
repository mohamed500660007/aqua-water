@extends('layouts.report', [
    'route' => 'payroll.report',
    'query' => [],
    'back' => route('payroll.index', $backQuery),
])

@use('App\Domain\Payroll\Services\PayrollReport')

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $share = fn ($part, $whole) => ($whole > 0 ? round($part * 100 / $whole, 1) : 0) . '%';
    $commissions = $totals['installments'] + $totals['installation'] + $totals['marketing'] + $totals['maintenance'];
    $gross = $totals['base'] + $commissions + $totals['bonus'];
    $monthsCount = count($period->months());

    $scope = collect([
        'بحث' => $search,
        'الوظيفة' => $filters['role'] ?? null,
        'القسم' => $filters['department'] ?? null,
        'الفرع' => $filters['branch'] ?? null,
        'الاعتماد' => $approvals[$filters['approval'] ?? ''] ?? null,
        'الصرف' => $payments[$filters['payment'] ?? ''] ?? null,
    ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join(' — ');
@endphp

@section('title', 'تقرير المرتبات والعمولات')
@section('subtitle', $monthsCount === 1 ? 'شهر ' . $period->from->format('Y-m') : $monthsCount . ' شهور')
@section('scope', $scope ?: 'جميع الموظفين — الكشف المعتمد لكل شهر، أو المحسوب تلقائيًا إن لم يُعتمد بعد')

@section('filters')
    <label class="col-span-2 flex flex-col gap-1">
        <span class="text-[11px] font-bold text-gray-500">بحث عن موظف</span>
        <input class="h-9 rounded border border-gray-300 px-2 text-xs" name="q" type="search" value="{{ $search }}" placeholder="الاسم أو البريد أو الهاتف..." />
    </label>
    <x-report.filter label="الوظيفة" name="role" :options="$roles" :selected="$filters['role'] ?? null" />
    <x-report.filter label="القسم" name="department" :options="$departments" :selected="$filters['department'] ?? null" />
    <x-report.filter label="الفرع" name="branch" :options="$branches" :selected="$filters['branch'] ?? null" />
    <x-report.filter label="اعتماد الكشف" name="approval" :options="$approvals" :selected="$filters['approval'] ?? null" />
    <x-report.filter label="حالة الصرف" name="payment" :options="$payments" :selected="$filters['payment'] ?? null" />
    <x-report.filter label="محتوى التقرير" name="content" :options="$contents" :selected="$content" :placeholder="null" />
    <x-report.filter label="ترتيب الكشف حسب" name="sort" :options="$sorts" :selected="request('sort', 'name')" :placeholder="null" />
    <x-report.filter label="اتجاه الترتيب" name="dir" :options="['asc' => 'تصاعدي', 'desc' => 'تنازلي']" :selected="request('dir', 'asc')" :placeholder="null" />
@endsection

@section('content')
    @if ($content !== 'list')
        <x-report.section class="avoid-break" no="1" title="الملخص العام">
            <x-report.figures :highlight="3" :items="[
                ['عدد الموظفين', number_format($totals['employees']), 'موظف', $monthsCount > 1 ? 'على مدار ' . $monthsCount . ' شهور' : null],
                ['إجمالي المستحقات', $money($gross), 'ج.م', 'الثابت + العمولات + المكافآت'],
                ['إجمالي الخصومات', $money($totals['deductions']), 'ج.م', number_format($totals['absence_days']) . ' يوم غياب'],
                ['صافي المرتبات', $money($totals['net']), 'ج.م'],
            ]" />
            <x-report.figures :items="[
                ['كشوف معتمدة', number_format($totals['approved_months']) . ' / ' . number_format($totals['months']), 'كشف', 'المعتمد من إجمالي كشوف الفترة'],
                ['تم صرفه', $money($totals['paid']), 'ج.م', 'نسبة الصرف ' . $share($totals['paid'], $totals['net'])],
                ['متبقي للصرف', $money($totals['remaining']), 'ج.م'],
                ['إجمالي العمولات', $money($commissions), 'ج.م', $share($commissions, $gross) . ' من المستحقات'],
            ]" />
        </x-report.section>

        <x-report.section class="avoid-break" no="2" title="تفصيل المستحقات والخصومات">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>البند</th>
                        <th class="c">العدد</th>
                        <th class="l">المبلغ (ج.م)</th>
                        <th class="c">الحصة من المستحقات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (PayrollReport::COMPONENTS as [$key, $label, $countKey])
                        <tr>
                            <td class="font-bold">{{ $label }}</td>
                            <td class="num c">{{ $countKey ? number_format($totals[$countKey]) : '—' }}</td>
                            <td class="num l">{{ $money($totals[$key]) }}</td>
                            <td class="num c">{{ $share($totals[$key], $gross) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="font-bold">الخصومات (الغياب والبصمة + أخرى)</td>
                        <td class="num c">{{ number_format($totals['absence_days']) }} يوم</td>
                        <td class="num l">− {{ $money($totals['deductions']) }}</td>
                        <td class="num c">—</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">صافي المرتبات</td>
                        <td class="num l">{{ $money($totals['net']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </x-report.section>

        <x-report.section class="avoid-break" no="3" title="المرتبات حسب القسم">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th class="c">الموظفون</th>
                        <th class="l">صافي المرتبات</th>
                        <th class="l">تم صرفه</th>
                        <th class="l">المتبقي</th>
                        <th class="c">الحصة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byDepartment as $group)
                        <tr>
                            <td class="font-bold">{{ $group['name'] }}</td>
                            <td class="num c">{{ number_format($group['employees']) }}</td>
                            <td class="num l font-bold">{{ $money($group['net']) }}</td>
                            <td class="num l">{{ $money($group['paid']) }}</td>
                            <td class="num l">{{ $money($group['remaining']) }}</td>
                            <td class="num c">{{ $share($group['net'], $totals['net']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="6">لا يوجد موظفون</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>
    @endif

    @if ($content !== 'summary')
        <x-report.section :no="$content === 'list' ? 1 : 4" title="كشف المرتبات" :note="number_format($rows->count()) . ' موظف'">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>الموظف / الوظيفة</th>
                        <th class="l">الثابت</th>
                        <th class="l">العمولات</th>
                        <th class="l">المكافآت</th>
                        <th class="l">الخصومات</th>
                        <th class="l">الصافي</th>
                        <th class="l">تم صرفه</th>
                        <th class="l">المتبقي</th>
                        <th class="c">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td>
                                <p class="font-bold leading-tight">{{ $row['user']->name }}</p>
                                <p class="text-[9.5px] muted">{{ collect([$row['user']->role, $row['user']->department])->filter()->join(' — ') }}</p>
                            </td>
                            <td class="num l">{{ $money($row['base']) }}</td>
                            <td class="num l">{{ $money($row['installments'] + $row['installation'] + $row['marketing'] + $row['maintenance']) }}</td>
                            <td class="num l">{{ $row['bonus'] > 0 ? $money($row['bonus']) : '—' }}</td>
                            <td class="num l">{{ $row['deductions'] > 0 ? '− ' . $money($row['deductions']) : '—' }}</td>
                            <td class="num l font-black">{{ $money($row['net']) }}</td>
                            <td class="num l">{{ $money($row['paid']) }}</td>
                            <td class="num l {{ $row['remaining'] > 0 ? 'font-bold' : 'muted' }}">{{ $money($row['remaining']) }}</td>
                            <td class="c whitespace-nowrap">
                                @if ($row['status']['key'] === 'paid')
                                    {{ $row['status']['label'] }}
                                @else
                                    <span @class(['tag', 'is-strong' => $row['status']['key'] === 'unpaid'])>{{ $row['status']['label'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="10">لا يوجد موظفون مطابقون لنطاق التقرير</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="2">الإجمالي</td>
                            <td class="num l">{{ $money($totals['base']) }}</td>
                            <td class="num l">{{ $money($commissions) }}</td>
                            <td class="num l">{{ $money($totals['bonus']) }}</td>
                            <td class="num l">{{ $money($totals['deductions']) }}</td>
                            <td class="num l">{{ $money($totals['net']) }}</td>
                            <td class="num l">{{ $money($totals['paid']) }}</td>
                            <td class="num l">{{ $money($totals['remaining']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
            <p class="text-[9.5px] muted mt-2">* «غير معتمد»: لم يُحفظ كشف الشهر بعد، والأرقام محسوبة تلقائيًا من حركة الشهر (تحصيل، تركيب، تسويق).</p>
        </x-report.section>
    @endif
@endsection
