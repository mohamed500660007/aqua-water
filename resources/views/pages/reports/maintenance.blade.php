@extends('layouts.report', [
    'route' => 'reports.maintenance',
    'back' => route('reports.index'),
])

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $count = fn ($value) => number_format((int) $value);
    $place = fn ($customer) => collect([$customer?->governorate, $customer?->center, $customer?->village])->filter()->join(' / ') ?: '—';
@endphp

@section('title', 'تقرير الصيانة')
@section('subtitle', 'ما تم، والمتأخر، والمستحق')
@section('scope', ($userId ? 'الفني: ' . ($people[$userId] ?? '—') : 'كل الفنيين') . ' — المتأخر لحظي (فات ميعاده حتى اليوم ولم يُغيَّر)')

@section('filters')
    <x-report.filter label="الفني" name="user_id" :options="$people" :selected="$userId" />
@endsection

@section('content')
    @if ($truncated)
        <p class="rpt-scope"><b>تنبيه:</b> كشف ما تم مقتصر على أول {{ $count($done->count()) }} شمعة — ضيّق الفترة أو اختر فنيًا لطباعة الباقي.</p>
    @endif

    <x-report.section class="avoid-break" no="1" title="الملخص العام">
        <x-report.figures :highlight="0" :items="[
            ['عملاء اتعملهم صيانة', $count($summary['contracts']), 'عميل', $count($summary['candles']) . ' شمعة'],
            ['جملة / قطاعي', $count($summary['package']) . ' / ' . $count($summary['per_item']), 'شمعة'],
            ['بالضمان', $count($summary['warranty']), 'شمعة', 'بدون مقابل من العميل'],
            ['إيراد الصيانة', $money($summary['revenue']), 'ج.م', 'عمولة الفنيين ' . $money($summary['commission'])],
        ]" />
        <x-report.figures :items="[
            ['شمعات متأخرة لم تُغيَّر', $count($summary['late']), 'شمعة', 'لـ ' . $count($summary['late_contracts']) . ' عميل'],
            ['مستحقة خلال الفترة', $count($summary['due']), 'شمعة', 'لـ ' . $count($summary['due_contracts']) . ' عميل'],
            ['كل الشمعات المتبقية', $count($summary['pending']), 'شمعة', 'في ملف الصيانة'],
        ]" />
    </x-report.section>

    <div class="grid grid-cols-2 gap-4">
        <x-report.section class="avoid-break" no="2" title="أداء الفنيين">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>الفني</th>
                        <th class="c">العملاء</th>
                        <th class="c">جملة</th>
                        <th class="c">قطاعي</th>
                        <th class="c">ضمان</th>
                        <th class="l">الإيراد</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($technicians as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="num c">{{ $count($row['contracts']) }}</td>
                            <td class="num c">{{ $count($row['package']) }}</td>
                            <td class="num c">{{ $count($row['per_item']) }}</td>
                            <td class="num c muted">{{ $count($row['warranty']) }}</td>
                            <td class="num l font-bold">{{ $money($row['revenue']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="6">لا توجد صيانة في هذه الفترة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>

        <x-report.section class="avoid-break" no="3" title="الشمعات المتغيّرة">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>الشمعة</th>
                        <th class="c">جملة</th>
                        <th class="c">قطاعي</th>
                        <th class="c">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($candles as $row)
                        <tr>
                            <td class="font-bold">{{ $row->product_name }}</td>
                            <td class="num c">{{ $count($row->package) }}</td>
                            <td class="num c">{{ $count($row->per_item) }}</td>
                            <td class="num c font-black">{{ $count($row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="4">لا توجد شمعات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>
    </div>

    <x-report.section no="4" title="كشف الصيانة التي تمت" :note="$count($done->count()) . ' شمعة'">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th class="c">التاريخ</th>
                    <th>العميل</th>
                    <th>العقد</th>
                    <th>الشمعة / القطعة</th>
                    <th>النوع</th>
                    <th>الفني</th>
                    <th class="l">السعر</th>
                    <th class="l">الخصم</th>
                    <th class="l">المستحق</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($done as $candle)
                    <tr>
                        <td class="num c" dir="ltr">{{ $candle->serviced_at?->format('Y-m-d') }}</td>
                        <td>
                            <p class="font-bold leading-tight">{{ $candle->contract?->customer?->name ?? '—' }}</p>
                            <p class="text-[9.5px] muted">{{ $place($candle->contract?->customer) }}</p>
                        </td>
                        <td class="num" dir="ltr">{{ $candle->contract?->code }}</td>
                        <td class="text-[10px]">{{ $candle->product_name }}<br><span class="muted">{{ $candle->product?->name }}</span></td>
                        <td class="text-[10px] whitespace-nowrap">
                            {{ $candle->service_type?->label() }}
                            @if ($candle->under_warranty) <span class="tag">ضمان</span> @endif
                        </td>
                        <td class="text-[10px]">{{ $candle->technician?->name ?? '—' }}</td>
                        <td class="num l">{{ $money($candle->price) }}</td>
                        <td class="num l muted">{{ $money($candle->discount) }}</td>
                        <td class="num l font-bold">{{ $money($candle->total()) }}</td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="9">لا توجد صيانة تمت في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if ($done->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="6">الإجمالي</td>
                        <td class="num l">{{ $money($done->sum('price')) }}</td>
                        <td class="num l">{{ $money($done->sum('discount')) }}</td>
                        <td class="num l">{{ $money($done->sum(fn ($candle) => $candle->total())) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </x-report.section>

    @foreach ([
        [5, 'عملاء متأخرون عن الصيانة', $late, 'شمعات فات ميعادها حتى اليوم ولم تُغيَّر — الأقدم أولًا', 'لا يوجد عملاء متأخرون'],
        [6, 'عملاء مستحق لهم صيانة خلال الفترة', $due, 'شمعات ميعادها داخل الفترة ولم تُغيَّر بعد', 'لا توجد صيانة مستحقة في هذه الفترة'],
    ] as [$no, $title, $rows, $note, $empty])
        <x-report.section :no="$no" :title="$title" :note="$count($rows->count()) . ' عميل — ' . $note">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th class="c">م</th>
                        <th>العميل</th>
                        <th>المنطقة</th>
                        <th>العقد</th>
                        <th>الشمعات</th>
                        <th class="c">أقرب ميعاد</th>
                        <th>الفني</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="num c muted">{{ $loop->iteration }}</td>
                            <td>
                                <p class="font-bold leading-tight">{{ $row['contract']->customer?->name ?? '—' }}</p>
                                <p class="num text-[9.5px] muted text-right" dir="ltr">{{ $row['contract']->customer?->primaryPhone?->phone }}</p>
                            </td>
                            <td class="text-[10px]">{{ $place($row['contract']->customer) }}</td>
                            <td class="num" dir="ltr">{{ $row['contract']->code }}</td>
                            <td class="text-[10px]">{{ $count($row['candles']) }} — {{ $row['labels'] }}</td>
                            <td class="num c" dir="ltr">{{ $row['oldest']?->format('Y-m-d') }}</td>
                            <td class="text-[10px]">{{ $row['contract']->installer?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="7">{{ $empty }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>
    @endforeach
@endsection
