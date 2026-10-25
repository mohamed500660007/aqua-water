@extends('layouts.report', [
    'route' => 'installments.report',
    'query' => [],
    'back' => route('installments.index'),
])

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $share = fn ($part, $whole) => ($whole > 0 ? round($part * 100 / $whole, 1) : 0) . '%';
    $place = fn ($customer) => collect([$customer?->governorate, $customer?->center, $customer?->village])->filter()->join(' / ') ?: '—';
    $shows = fn (string $part) => in_array($content, ['all', $part], true);
    $areaLabel = $areaLevels[$areaLevel];

    $scope = collect([
        'المحافظة' => $filters['governorate'] ?? null,
        'المركز' => $filters['center'] ?? null,
        'القرية' => $filters['village'] ?? null,
        'المحصّل' => $collectors[$filters['collected_by_id'] ?? ''] ?? null,
    ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join(' — ');

    // ترقيم الأقسام حسب ما يُطبع فعلًا
    $sectionNo = 0;
    $next = function () use (&$sectionNo) {
        return ++$sectionNo;
    };
@endphp

@section('title', 'تقرير الأقساط')
@section('subtitle', 'التحصيل والمتأخرات حسب المنطقة والمحصّل')
@section('scope', $scope ?: 'جميع المناطق وجميع المحصّلين')

@section('filters')
    <x-report.filter label="المحافظة" name="governorate" :options="array_combine($governorates, $governorates)" :selected="$filters['governorate'] ?? null" />
    <x-report.filter label="المركز" name="center" :options="array_combine($centers, $centers)" :selected="$filters['center'] ?? null" />
    <x-report.filter label="القرية" name="village" :options="array_combine($villages, $villages)" :selected="$filters['village'] ?? null" />
    <x-report.filter label="المحصّل / الفني" name="collected_by_id" :options="$collectors" :selected="$filters['collected_by_id'] ?? null" />
    <x-report.filter label="تجميع المناطق حسب" name="area_level" :options="$areaLevels" :selected="$areaLevel" :placeholder="null" />
    <x-report.filter label="محتوى التقرير" name="content" :options="$contents" :selected="$content" :placeholder="null" />
@endsection

@section('content')
    @if ($truncated)
        <p class="rpt-scope"><b>تنبيه:</b> بعض الكشوف مقتصرة على أول {{ number_format(\App\Domain\Sales\Services\InstallmentReport::MAX_ROWS) }} صف — ضيّق الفترة أو المنطقة لطباعة الباقي.</p>
    @endif

    <x-report.section class="avoid-break" :no="$next()" title="الملخص العام">
        <x-report.figures :highlight="0" :items="[
            ['المحصّل خلال الفترة', $money($summary['collected_amount']), 'ج.م', number_format($summary['collected_installments']) . ' قسط من ' . number_format($summary['collected_customers']) . ' عميل'],
            ['المستحق خلال الفترة', $money($summary['due_amount']), 'ج.م', 'حُصِّل منه ' . $share($summary['due_paid'], $summary['due_amount'])],
            ['المتأخرات', $money($summary['overdue_amount']), 'ج.م', number_format($summary['overdue_installments']) . ' قسط على ' . number_format($summary['overdue_contracts']) . ' عقد'],
        ]" />
        @if ($shows('milestones'))
            <x-report.figures :items="[
                ['بدأوا السداد (القسط الأول)', number_format($firstPayments->count()), 'عميل', 'بقيمة ' . $money($firstPayments->sum('paid_amount') + $firstPayments->sum('down_payment')) . ' مع المقدم'],
                ['أنهوا أقساطهم', number_format($finished->count()), 'عميل', 'بقيمة عقود ' . $money($finished->sum(fn ($row) => $row['contract']->total))],
                ['عقود متأخرة', number_format($summary['overdue_contracts']), 'عقد', 'حتى ' . $overdueCutoff],
            ]" />
        @endif
    </x-report.section>

    @if ($shows('collections'))
        <x-report.section class="avoid-break" :no="$next()" :title="'التحصيل حسب ' . $areaLabel . ' والمحصّل'">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>{{ $areaLabel }} / المحصّل</th>
                        <th class="c">العملاء</th>
                        <th class="c">الأقساط</th>
                        <th class="l">المبلغ المحصّل</th>
                        <th class="c">الحصة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($areas as $area)
                        <tr class="group-row">
                            <td>{{ $area['area'] }}</td>
                            <td class="num c">{{ number_format($area['customers']) }}</td>
                            <td class="num c">{{ number_format($area['installments']) }}</td>
                            <td class="num l">{{ $money($area['amount']) }}</td>
                            <td class="num c">{{ $share($area['amount'], $summary['collected_amount']) }}</td>
                        </tr>
                        @foreach ($area['collectors'] as $collector)
                            <tr>
                                <td class="pr-5">{{ $collector['name'] }}</td>
                                <td class="num c">{{ number_format($collector['customers']) }}</td>
                                <td class="num c">{{ number_format($collector['installments']) }}</td>
                                <td class="num l">{{ $money($collector['amount']) }}</td>
                                <td class="num c muted">{{ $share($collector['amount'], $area['amount']) }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td class="is-empty" colspan="5">لا توجد تحصيلات في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
                @if ($areas->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td>الإجمالي</td>
                            <td class="num c">{{ number_format($summary['collected_customers']) }}</td>
                            <td class="num c">{{ number_format($summary['collected_installments']) }}</td>
                            <td class="num l">{{ $money($summary['collected_amount']) }}</td>
                            <td class="num c">100%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>

        <x-report.section :no="$next()" title="كشف التحصيلات" :note="number_format($collections->count()) . ' عملية تحصيل — مرتبة حسب ' . $areaLabel . ' ثم المحصّل'">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th class="c">التاريخ</th>
                        <th>العميل</th>
                        <th>المنطقة</th>
                        <th>العقد</th>
                        <th>القسط</th>
                        <th>المحصّل</th>
                        <th class="l">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($collections as $installment)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td class="num c" dir="ltr">{{ $installment->paid_at?->format('Y-m-d') }}</td>
                            <td>
                                <p class="font-bold leading-tight">{{ $installment->contract?->customer?->name ?? '—' }}</p>
                                <p class="num text-[9.5px] muted text-right" dir="ltr">{{ $installment->contract?->customer?->code }}</p>
                            </td>
                            <td class="text-[10px]">{{ $place($installment->contract?->customer) }}</td>
                            <td class="num" dir="ltr">{{ $installment->contract?->code }}</td>
                            <td class="text-[10px] whitespace-nowrap">{{ $installment->label() }}</td>
                            <td class="text-[10px]">{{ $installment->collectedBy?->name ?? '—' }}</td>
                            <td class="num l font-bold">{{ $money($installment->paid_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="8">لا توجد تحصيلات في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
                @if ($collections->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="7">إجمالي الكشف</td>
                            <td class="num l">{{ $money($collections->sum('paid_amount')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    @endif

    @if ($shows('overdue'))
        <x-report.section :no="$next()" title="المتأخرون عن السداد"
            :note="number_format($overdue->count()) . ' عميل — أقساط فات ميعادها حتى ' . $overdueCutoff . ' ولم تُحصَّل (بدون المحوّلين للقانونية)'">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>العميل</th>
                        <th>المنطقة</th>
                        <th>العقد</th>
                        <th>الأقساط المتأخرة</th>
                        <th class="c">أقدم ميعاد</th>
                        <th class="c">أيام التأخير</th>
                        <th class="l">إجمالي المتأخر</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($overdue as $row)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td>
                                <p class="font-bold leading-tight">{{ $row['contract']->customer?->name ?? '—' }}</p>
                                <p class="num text-[9.5px] muted text-right" dir="ltr">{{ $row['contract']->customer?->primaryPhone?->phone ?? $row['contract']->customer?->code }}</p>
                            </td>
                            <td class="text-[10px]">{{ $place($row['contract']->customer) }}</td>
                            <td class="num" dir="ltr">{{ $row['contract']->code }}</td>
                            <td class="text-[10px]">
                                @foreach ($row['installments'] as $installment)
                                    <p class="whitespace-nowrap">{{ $installment->label() }}: <span class="num font-bold">{{ $money($installment->amount) }}</span></p>
                                @endforeach
                            </td>
                            <td class="num c" dir="ltr">{{ $row['oldest']->format('Y-m-d') }}</td>
                            <td class="num c">{{ number_format((int) $row['oldest']->diffInDays(today())) }}</td>
                            <td class="num l font-black">{{ $money($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="8">لا يوجد عملاء متأخرون</td></tr>
                    @endforelse
                </tbody>
                @if ($overdue->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="4">الإجمالي</td>
                            <td class="num">{{ number_format($overdue->sum(fn ($row) => $row['installments']->count())) }} قسط</td>
                            <td colspan="2"></td>
                            <td class="num l">{{ $money($overdue->sum('amount')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    @endif

    @if ($shows('milestones'))
        <x-report.section :no="$next()" title="عملاء دفعوا القسط الأول مع المقدم" :note="number_format($firstPayments->count()) . ' عميل حصّلوا القسط الأول خلال الفترة'">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>العميل</th>
                        <th>المنطقة</th>
                        <th>العقد</th>
                        <th class="c">تاريخ العقد</th>
                        <th class="c">تاريخ القسط</th>
                        <th>المحصّل</th>
                        <th class="l">المقدم</th>
                        <th class="l">القسط الأول</th>
                        <th class="l">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($firstPayments as $installment)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td class="font-bold">{{ $installment->contract?->customer?->name ?? '—' }}</td>
                            <td class="text-[10px]">{{ $place($installment->contract?->customer) }}</td>
                            <td class="num" dir="ltr">{{ $installment->contract?->code }}</td>
                            <td class="num c" dir="ltr">{{ $installment->contract?->contract_date?->format('Y-m-d') }}</td>
                            <td class="num c" dir="ltr">{{ $installment->paid_at?->format('Y-m-d') }}</td>
                            <td class="text-[10px]">{{ $installment->collectedBy?->name ?? '—' }}</td>
                            <td class="num l">{{ $money($installment->down_payment) }}</td>
                            <td class="num l">{{ $money($installment->paid_amount) }}</td>
                            <td class="num l font-bold">{{ $money($installment->down_payment + $installment->paid_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="10">لا يوجد عملاء دفعوا القسط الأول في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
                @if ($firstPayments->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="7">الإجمالي</td>
                            <td class="num l">{{ $money($firstPayments->sum('down_payment')) }}</td>
                            <td class="num l">{{ $money($firstPayments->sum('paid_amount')) }}</td>
                            <td class="num l">{{ $money($firstPayments->sum('down_payment') + $firstPayments->sum('paid_amount')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>

        <x-report.section :no="$next()" title="عملاء أنهوا أقساطهم" :note="number_format($finished->count()) . ' عقد اكتمل سداد أقساطه خلال الفترة'">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>العميل</th>
                        <th>المنطقة</th>
                        <th>العقد</th>
                        <th class="c">تاريخ العقد</th>
                        <th class="c">عدد الأقساط</th>
                        <th class="c">آخر سداد</th>
                        <th class="l">قيمة العقد</th>
                        <th class="l">المحصّل بالأقساط</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($finished as $row)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td class="font-bold">{{ $row['contract']->customer?->name ?? '—' }}</td>
                            <td class="text-[10px]">{{ $place($row['contract']->customer) }}</td>
                            <td class="num" dir="ltr">{{ $row['contract']->code }}</td>
                            <td class="num c" dir="ltr">{{ $row['contract']->contract_date?->format('Y-m-d') }}</td>
                            <td class="num c">{{ number_format($row['contract']->installments_count ?? $row['payments']) }}</td>
                            <td class="num c" dir="ltr">{{ $row['last_paid_at']->format('Y-m-d') }}</td>
                            <td class="num l">{{ $money($row['contract']->total) }}</td>
                            <td class="num l font-bold">{{ $money($row['paid']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="9">لا يوجد عملاء أنهوا أقساطهم في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
                @if ($finished->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="7">الإجمالي</td>
                            <td class="num l">{{ $money($finished->sum(fn ($row) => $row['contract']->total)) }}</td>
                            <td class="num l">{{ $money($finished->sum('paid')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-report.section>
    @endif
@endsection
