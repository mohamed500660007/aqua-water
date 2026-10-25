@extends('layouts.report', [
    'route' => 'reports.after-sales',
    'back' => route('reports.index'),
])

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $count = fn ($value) => number_format((int) $value);
@endphp

@section('title', 'تقرير الشكاوى والهدايا')
@section('subtitle', 'خدمة ما بعد البيع')
@section('scope', $userId ? 'الفني: ' . ($people[$userId] ?? '—') : 'كل الفنيين')

@section('filters')
    <x-report.filter label="الفني" name="user_id" :options="$people" :selected="$userId" />
@endsection

@section('content')
    @if ($truncated)
        <p class="rpt-scope"><b>تنبيه:</b> بعض الكشوف مقتصرة على أول {{ $count(\App\Domain\Reports\Services\AfterSalesReport::MAX_ROWS) }} صف — ضيّق الفترة أو اختر فنيًا.</p>
    @endif

    <x-report.section class="avoid-break" no="1" title="الملخص العام">
        <x-report.figures :highlight="3" :items="[
            ['الشكاوى', $count($summary['complaints']), 'شكوى', $count($summary['resolved']) . ' محلولة — ' . $count($summary['open']) . ' مفتوحة'],
            ['قطع غيار متغيّرة', $count($summary['parts_pieces']), 'قطعة', 'بقيمة ' . $money($summary['parts_amount'])],
            ['رسوم الزيارات', $money($summary['visit_fees']), 'ج.م'],
            ['إيراد الشكاوى', $money($summary['revenue']), 'ج.م'],
        ]" />
        <x-report.figures :items="[
            ['الهدايا', $count($summary['gifts']), 'هدية', 'لـ ' . $count($summary['gift_customers']) . ' عميل'],
            ['قطع الهدايا', $count($summary['gift_pieces']), 'قطعة'],
            ['تكلفة الهدايا', $money($summary['gift_cost']), 'ج.م', 'بسعر التكلفة'],
        ]" />
    </x-report.section>

    <div class="grid grid-cols-2 gap-4">
        <x-report.section class="avoid-break" no="2" title="أداء الفنيين">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>الفني</th>
                        <th class="c">شكاوى</th>
                        <th class="c">محلولة</th>
                        <th class="l">الإيراد</th>
                        <th class="c">هدايا</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($technicians as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="num c">{{ $count($row['complaints']) }}</td>
                            <td class="num c">{{ $count($row['resolved']) }}</td>
                            <td class="num l font-bold">{{ $money($row['revenue']) }}</td>
                            <td class="num c">{{ $count($row['gifts']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="5">لا توجد بيانات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>

        <x-report.section class="avoid-break" no="3" title="القطع الأكثر استخدامًا">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>القطعة</th>
                        <th class="c">في شكاوى</th>
                        <th class="l">قيمتها</th>
                        <th class="c">هدايا</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parts as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="num c">{{ $count($row['complaint_pieces']) }}</td>
                            <td class="num l">{{ $money($row['complaint_amount']) }}</td>
                            <td class="num c">{{ $count($row['gift_pieces']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="is-empty" colspan="4">لا توجد قطع</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-report.section>
    </div>

    <x-report.section no="4" title="كشف الشكاوى" :note="$count($complaints->count()) . ' شكوى'">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th class="c">التاريخ</th>
                    <th>العميل</th>
                    <th>العقد</th>
                    <th>الفني</th>
                    <th>القطع المتغيّرة</th>
                    <th class="l">الزيارة</th>
                    <th class="l">القطع</th>
                    <th class="l">الإجمالي</th>
                    <th class="c">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($complaints as $complaint)
                    <tr>
                        <td class="num c" dir="ltr">{{ $complaint->complaint_date?->format('Y-m-d') }}</td>
                        <td class="font-bold">{{ $complaint->contract?->customer?->name ?? '—' }}</td>
                        <td class="num" dir="ltr">{{ $complaint->contract?->code }}</td>
                        <td class="text-[10px]">{{ $complaint->technician?->name ?? '—' }}</td>
                        <td class="text-[10px]">
                            {{ $complaint->parts->map(fn ($part) => ($part->product?->name ?? 'قطعة محذوفة') . ' × ' . $part->quantity . ' (' . $money($part->price) . ')')->join('، ') ?: '—' }}
                        </td>
                        <td class="num l">{{ $money($complaint->visit_fee) }}</td>
                        <td class="num l">{{ $money($complaint->partsTotal()) }}</td>
                        <td class="num l font-bold">{{ $money($complaint->total()) }}</td>
                        <td class="c">{!! $complaint->resolved ? '<span class="tag">محلولة</span>' : '<span class="tag is-strong">مفتوحة</span>' !!}</td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="9">لا توجد شكاوى في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if ($complaints->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="5">الإجمالي</td>
                        <td class="num l">{{ $money($complaints->sum('visit_fee')) }}</td>
                        <td class="num l">{{ $money($complaints->sum(fn ($complaint) => $complaint->partsTotal())) }}</td>
                        <td class="num l">{{ $money($complaints->sum(fn ($complaint) => $complaint->total())) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </x-report.section>

    <x-report.section no="5" title="كشف الهدايا" :note="$count($gifts->count()) . ' هدية'">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th class="c">التاريخ</th>
                    <th>العميل</th>
                    <th>القطعة</th>
                    <th class="c">الكمية</th>
                    <th>الفني</th>
                    <th class="l">التكلفة</th>
                    <th>ملاحظة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gifts as $gift)
                    <tr>
                        <td class="num c" dir="ltr">{{ $gift->gift_date?->format('Y-m-d') }}</td>
                        <td class="font-bold">{{ $gift->customer?->name ?? '—' }}</td>
                        <td>{{ $gift->product?->name ?? '—' }}</td>
                        <td class="num c">{{ $count($gift->quantity) }}</td>
                        <td class="text-[10px]">{{ $gift->technician?->name ?? '—' }}</td>
                        <td class="num l">{{ $money($gift->quantity * (float) $gift->product?->cost) }}</td>
                        <td class="text-[10px] muted">{{ $gift->note }}</td>
                    </tr>
                @empty
                    <tr><td class="is-empty" colspan="7">لا توجد هدايا في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-report.section>
@endsection
