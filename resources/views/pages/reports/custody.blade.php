@extends('layouts.report', [
    'route' => 'reports.custody',
    'back' => route('reports.index'),
])

@use('App\Domain\Inventory\Services\CustodyReport')

@php
    $count = fn ($value) => number_format((int) $value);
    $columns = CustodyReport::COLUMNS;
    $totals = collect(array_keys($columns))->mapWithKeys(fn (string $key) => [$key => $employees->sum($key)]);
@endphp

@section('title', 'تقرير العهد والتسويات')
@section('subtitle', 'ما استلمه كل موظف وأين ذهب')
@section('scope', ($userId ? 'الموظف: ' . ($people[$userId] ?? '—') : 'كل المناديب والفنيين') . ' — الحركات خلال الفترة، والرصيد الحالي لحظي')

@section('filters')
    <x-report.filter label="الموظف" name="user_id" :options="$people" :selected="$userId" />
@endsection

@section('content')
    <x-report.section class="avoid-break" no="1" title="الملخص العام">
        <x-report.figures :highlight="5" :items="[
            ['استلموا من المخزن', $count($totals['received']), 'قطعة'],
            ['صُرف في عقود', $count($totals['sales']), 'قطعة'],
            ['صيانة', $count($totals['maintenance']), 'قطعة'],
            ['شكاوى وهدايا', $count($totals['complaints'] + $totals['gifts']), 'قطعة'],
            ['عجز / زيادة تسويات', '−' . $count($totals['shortage']) . ' / +' . $count($totals['surplus']), 'قطعة'],
            ['الرصيد الحالي معهم', $count($employees->sum('balance')), 'قطعة'],
        ]" />
    </x-report.section>

    <x-report.section no="2" title="حركة عهدة كل موظف">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    @foreach ($columns as $label)
                        <th class="c">{{ $label }}</th>
                    @endforeach
                    <th class="c">الرصيد الحالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $row)
                    <tr>
                        <td>
                            <p class="font-bold leading-tight">{{ $row['user']->name }}</p>
                            <p class="text-[9.5px] muted">{{ $row['user']->role }}</p>
                        </td>
                        @foreach (array_keys($columns) as $key)
                            <td @class(['num c', 'muted' => ! $row[$key], 'font-black' => in_array($key, ['shortage', 'surplus'], true) && $row[$key]])>
                                {{ $row[$key] ? ($key === 'shortage' ? '−' : ($key === 'surplus' ? '+' : '')) . $count($row[$key]) : '—' }}
                            </td>
                        @endforeach
                        <td class="num c font-black">{{ $count($row['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="{{ count($columns) + 2 }}">لا توجد عهد أو حركات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if ($employees->isNotEmpty())
                <tfoot>
                    <tr>
                        <td>الإجمالي</td>
                        @foreach (array_keys($columns) as $key)
                            <td class="num c">{{ $count($totals[$key]) }}</td>
                        @endforeach
                        <td class="num c">{{ $count($employees->sum('balance')) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
        <p class="text-[9.5px] muted mt-1.5">«صرف في عقود» و«الشكاوى» و«الهدايا» صافية بعد خصم المرتجع إلى العهدة.</p>
    </x-report.section>

    <x-report.section no="3" title="تفصيل الأصناف لكل موظف">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>الصنف</th>
                    @foreach ($columns as $label)
                        <th class="c">{{ $label }}</th>
                    @endforeach
                    <th class="c">الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $group)
                    <tr class="group-row">
                        <td colspan="{{ count($columns) + 2 }}">{{ $group['user']->name }} — {{ $count($group['products']->count()) }} صنف</td>
                    </tr>
                    @foreach ($group['products'] as $row)
                        <tr>
                            <td class="font-bold">{{ $row['product']?->name ?? 'منتج محذوف' }}</td>
                            @foreach (array_keys($columns) as $key)
                                <td @class(['num c', 'muted' => ! $row[$key]])>{{ $row[$key] ? $count($row[$key]) : '—' }}</td>
                            @endforeach
                            <td class="num c font-black">{{ $count($row['balance']) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td class="is-empty" colspan="{{ count($columns) + 2 }}">لا توجد أصناف</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-report.section>

    <x-report.section no="4" title="التسويات المُرحّلة" :note="$count($settlements->count()) . ' تسوية خلال الفترة'">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>رقم التسوية</th>
                    <th class="c">التاريخ</th>
                    <th>الموظف</th>
                    <th>النوع</th>
                    <th>السبب</th>
                    <th>الأصناف (الفرق)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($settlements as $settlement)
                    <tr>
                        <td class="num font-bold" dir="ltr">{{ $settlement->number() }}</td>
                        <td class="num c" dir="ltr">{{ $settlement->settlement_date->format('Y-m-d') }}</td>
                        <td class="font-bold">{{ $settlement->user?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap"><span class="tag">{{ $settlement->type->label() }}</span></td>
                        <td class="text-[10px]">{{ $settlement->reason }}</td>
                        <td class="text-[10px]">
                            {{ $settlement->items->map(fn ($item) => ($item->product?->name ?? 'منتج محذوف') . ' (' . $item->signedDifference() . ')')->join('، ') }}
                        </td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="6">لا توجد تسويات مُرحّلة في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-report.section>
@endsection
