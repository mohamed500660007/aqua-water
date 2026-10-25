@extends('layouts.report', [
    'route' => 'reports.executive',
    'back' => route('reports.index'),
])

@use('App\Domain\Reports\Services\ExecutiveReport')

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $count = fn ($value) => number_format((int) $value);
    $percent = fn ($value) => number_format((float) $value, 1) . '%';

    // [العنوان، المفتاح، المنسّق، هل الزيادة سيئة؟]
    $rows = [
        'المبيعات' => [
            ['عقود البيع الجديدة', 'sales_contracts', $count],
            ['قيمة المبيعات (بدون الفائدة)', 'sales_value', $money],
            ['الأجهزة المباعة', 'devices_sold', $count],
            ['فوائد التقسيط', 'interest', $money],
            ['عقود الصيانة', 'maintenance_contracts', $count],
            ['قيمة عقود الصيانة', 'maintenance_contracts_value', $money],
            ['عملاء جدد', 'new_customers', $count],
        ],
        'التحصيل' => [
            ['أقساط مستحقة في الفترة', 'due', $money],
            ['المحصّل من الأقساط', 'collections', $money],
            ['نسبة تحصيل المستحق', 'collection_rate', $percent],
        ],
        'خدمة ما بعد البيع' => [
            ['شمعات تم تغييرها', 'candles_serviced', $count],
            ['عملاء اتعملهم صيانة', 'maintenance_customers', $count],
            ['الشكاوى', 'complaints', $count, true],
            ['شكاوى محلولة', 'complaints_resolved', $count],
        ],
        'المالية' => [
            ['إجمالي الدخل', 'income', $money],
            ['إجمالي المصروفات', 'expenses', $money, true],
            ['الصافي', 'net', $money],
        ],
    ];

    $trend = function (string $key, bool $increaseIsBad = false) use ($current, $before) {
        $change = ExecutiveReport::change($current[$key], $before[$key]);

        if ($change === null) {
            return ['—', 'muted'];
        }

        $good = $increaseIsBad ? $change <= 0 : $change >= 0;

        return [($change > 0 ? '▲ ' : ($change < 0 ? '▼ ' : '')) . number_format(abs($change), 1) . '%', $good ? 'font-black' : 'font-black is-bad'];
    };
@endphp

@section('title', 'الملخص التنفيذي')
@section('subtitle', 'أداء الشركة مقارنة بالفترة السابقة')
@section('scope', 'مقارنة بالفترة ' . $previous->fromDate() . ' — ' . $previous->toDate() . ' (نفس عدد الأيام)')

@section('content')
    <style>
        .is-bad { text-decoration: underline; text-decoration-style: dotted; }
    </style>

    <x-report.section class="avoid-break" no="1" title="الصورة العامة">
        <x-report.figures :highlight="2" :items="[
            ['الدخل', $money($current['income']), 'ج.م', 'السابقة ' . $money($before['income'])],
            ['المصروفات', $money($current['expenses']), 'ج.م', 'السابقة ' . $money($before['expenses'])],
            ['الصافي', $money($current['net']), 'ج.م', 'السابقة ' . $money($before['net'])],
            ['قيمة المبيعات', $money($current['sales_value']), 'ج.م', $count($current['sales_contracts']) . ' عقد'],
        ]" />
        <x-report.figures :items="[
            ['المحصّل من الأقساط', $money($current['collections']), 'ج.م', 'نسبة التحصيل ' . $percent($current['collection_rate'])],
            ['شمعات صيانة', $count($current['candles_serviced']), 'شمعة', $count($current['maintenance_customers']) . ' عميل'],
            ['الشكاوى', $count($current['complaints']), 'شكوى', $count($current['complaints_resolved']) . ' محلولة'],
            ['عملاء جدد', $count($current['new_customers']), 'عميل'],
        ]" />
    </x-report.section>

    <x-report.section class="avoid-break" no="2" title="تنبيهات تحتاج تدخّلك الآن" note="لحظية — لا تتقيد بالفترة">
        <x-report.figures :items="[
            ['عقود متأخرة السداد', $count($alerts['overdue_contracts']), 'عقد', 'متأخر عليها ' . $money($alerts['overdue_amount'])],
            ['محوّلة للشؤون القانونية', $count($alerts['legal_contracts']), 'عقد', 'متبقٍّ عليها ' . $money($alerts['legal_amount'])],
            ['شمعات فات ميعادها', $count($alerts['late_candles']), 'شمعة', 'لم تُغيَّر بعد'],
            ['شكاوى مفتوحة', $count($alerts['open_complaints']), 'شكوى'],
            ['منتجات نفدت من المخازن', $count($alerts['out_of_stock']), 'صنف'],
        ]" />
    </x-report.section>

    <x-report.section no="3" title="المؤشرات التفصيلية" note="▲ زيادة ▼ نقص — الخط المنقّط تغيّر في الاتجاه غير المرغوب">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>المؤشر</th>
                    <th class="l">الفترة الحالية</th>
                    <th class="l">الفترة السابقة</th>
                    <th class="c">التغيّر</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $section => $items)
                    <tr class="group-row"><td colspan="4">{{ $section }}</td></tr>
                    @foreach ($items as $item)
                        @php
                            [$label, $key, $format] = $item;
                            [$change, $class] = $trend($key, $item[3] ?? false);
                        @endphp
                        <tr>
                            <td class="font-bold">{{ $label }}</td>
                            <td class="num l font-black">{{ $format($current[$key]) }}</td>
                            <td class="num l muted">{{ $format($before[$key]) }}</td>
                            <td class="num c {{ $class }}">{{ $change }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </x-report.section>

    @php
        $boards = [
            ['أفضل مندوبي المبيعات', $leaders['sales_reps'], 'العقود', 'قيمة المبيعات', $money],
            ['أفضل المحصّلين', $leaders['collectors'], 'الأقساط', 'المحصّل', $money],
            ['أنشط فنيي الصيانة', $leaders['technicians'], 'الشمعات', 'العمولة', $money],
            ['المنتجات الأكثر مبيعًا', $leaders['products'], 'الكمية', 'القيمة', $money],
        ];
    @endphp

    <div class="grid grid-cols-2 gap-4">
        @foreach ($boards as $index => [$title, $board, $countLabel, $amountLabel, $format])
            <x-report.section class="avoid-break" :no="4 + $index" :title="$title">
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th class="c">#</th>
                            <th>الاسم</th>
                            <th class="c">{{ $countLabel }}</th>
                            <th class="l">{{ $amountLabel }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($board as $row)
                            <tr>
                                <td class="num c muted">{{ $loop->iteration }}</td>
                                <td class="font-bold">{{ $row['name'] }}</td>
                                <td class="num c">{{ $count($row['count']) }}</td>
                                <td class="num l font-bold">{{ $format($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td class="is-empty" colspan="4">لا توجد بيانات في هذه الفترة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-report.section>
        @endforeach
    </div>
@endsection
