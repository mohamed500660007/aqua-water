@extends('layouts.report', [
    'route' => 'reports.finance',
    'back' => route('reports.index'),
])

@use('App\Domain\Reports\Services\FinanceReport')

@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $share = fn ($part, $whole) => ($whole > 0 ? round($part * 100 / $whole, 1) : 0) . '%';
    $monthName = fn (string $month) => \Illuminate\Support\Carbon::parse($month . '-01')->locale('ar')->translatedFormat('F Y');
@endphp

@section('title', 'التقرير المالي')
@section('subtitle', 'الدخل والمصروفات والصافي')
@section('scope', 'الأرقام نقدية بتاريخ الدفع الفعلي — الأقساط بتاريخ تحصيلها، والمرتبات بتاريخ صرفها، والطلبيات بتاريخ ورودها')

@section('content')
    <x-report.section class="avoid-break" no="1" title="الملخص المالي">
        <x-report.figures :highlight="2" :items="[
            ['إجمالي الدخل', $money($totals['income_total']), 'ج.م'],
            ['إجمالي المصروفات', $money($totals['expenses_total']), 'ج.م'],
            ['الصافي', $money($totals['net']), 'ج.م', $totals['net'] >= 0 ? 'فائض' : 'عجز'],
        ]" />
        <x-report.figures :items="[
            ['مستحق على العملاء', $money($outstanding['receivables']), 'ج.م', 'لحظي — بدون القانونية'],
            ['لدى الشؤون القانونية', $money($outstanding['legal_receivables']), 'ج.م'],
            ['مرتبات لم تُصرف بعد', $money($outstanding['payroll_due']), 'ج.م', 'لكشوف شهور الفترة'],
            ['تكلفة الهدايا', $money($outstanding['gifts_cost']), 'ج.م', 'غير نقدية — بسعر التكلفة'],
        ]" />
    </x-report.section>

    <div class="grid grid-cols-2 gap-4">
        <x-report.section class="avoid-break" no="2" title="الدخل">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>البند</th>
                        <th class="l">المبلغ</th>
                        <th class="c">الحصة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (FinanceReport::INCOME as $key => $label)
                        <tr>
                            <td class="font-bold">{{ $label }}</td>
                            <td class="num l">{{ $money($totals['income'][$key]) }}</td>
                            <td class="num c muted">{{ $share($totals['income'][$key], $totals['income_total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>إجمالي الدخل</td>
                        <td class="num l">{{ $money($totals['income_total']) }}</td>
                        <td class="num c">100%</td>
                    </tr>
                </tfoot>
            </table>
        </x-report.section>

        <x-report.section class="avoid-break" no="3" title="المصروفات">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>البند</th>
                        <th class="l">المبلغ</th>
                        <th class="c">الحصة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (FinanceReport::EXPENSES as $key => $label)
                        <tr>
                            <td class="font-bold">{{ $label }}</td>
                            <td class="num l">{{ $money($totals['expenses'][$key]) }}</td>
                            <td class="num c muted">{{ $share($totals['expenses'][$key], $totals['expenses_total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>إجمالي المصروفات</td>
                        <td class="num l">{{ $money($totals['expenses_total']) }}</td>
                        <td class="num c">100%</td>
                    </tr>
                </tfoot>
            </table>
        </x-report.section>
    </div>

    <x-report.section no="4" title="التدفق الشهري" note="الدخل والمصروفات والصافي لكل شهر تلمسه الفترة">
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>الشهر</th>
                    <th class="l">الدخل</th>
                    <th class="l">المصروفات</th>
                    <th class="l">الصافي</th>
                    <th class="l">الصافي التراكمي</th>
                </tr>
            </thead>
            <tbody>
                @php($running = 0)
                @foreach ($monthly as $month)
                    @php($running += $month['net'])
                    <tr>
                        <td class="font-bold">{{ $monthName($month['month']) }}</td>
                        <td class="num l">{{ $money($month['income']) }}</td>
                        <td class="num l">{{ $money($month['expenses']) }}</td>
                        <td class="num l font-black">{{ $month['net'] < 0 ? '(' . $money(abs($month['net'])) . ')' : $money($month['net']) }}</td>
                        <td class="num l muted">{{ $running < 0 ? '(' . $money(abs($running)) . ')' : $money($running) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>الإجمالي</td>
                    <td class="num l">{{ $money($monthly->sum('income')) }}</td>
                    <td class="num l">{{ $money($monthly->sum('expenses')) }}</td>
                    <td class="num l">{{ $monthly->sum('net') < 0 ? '(' . $money(abs($monthly->sum('net'))) . ')' : $money($monthly->sum('net')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <p class="text-[9.5px] muted mt-1.5">الأرقام بين قوسين ( ) = عجز (المصروفات أكبر من الدخل).</p>
    </x-report.section>
@endsection
