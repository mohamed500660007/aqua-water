@extends('layouts.document')

@php
    $user = $payroll->user;
    $monthLabel = $payroll->month->format('Y-m');
    $line = fn (int $count, float $rate) => round($count * $rate, 2);

    $earnings = [
        ['المرتب الأساسي', '', (float) $payroll->base_salary],
        ['عمولة التحصيل', $payroll->installment_count . ' قسط بإجمالي ' . number_format((float) $payroll->installment_total, 2) . ' ج.م × ' . number_format((float) $payroll->installment_commission, 2), $line($payroll->installment_count, (float) $payroll->installment_commission)],
        ['عمولة التركيب', $payroll->installed_device_count . ' جهاز × ' . number_format((float) $payroll->installation_commission, 2), $line($payroll->installed_device_count, (float) $payroll->installation_commission)],
        ['عمولة البيع (التسويق)', $payroll->marketed_device_count . ' جهاز بقيمة ' . number_format((float) $payroll->device_sales_total, 2) . ' ج.م × ' . number_format((float) $payroll->marketing_commission, 2), $line($payroll->marketed_device_count, (float) $payroll->marketing_commission)],
        ['عمولة الصيانة', $payroll->maintenance_count . ' عميل × ' . number_format((float) $payroll->maintenance_commission, 2), $line($payroll->maintenance_count, (float) $payroll->maintenance_commission)],
        ['مكافأة', '', (float) $payroll->bonus],
    ];

    $deductions = [
        ['خصم الغياب والبصمة', $payroll->absence_days . ' يوم غياب', (float) $payroll->attendance_deduction],
        ['خصومات أخرى', $payroll->deduction_reason ?? '', (float) $payroll->regular_deduction],
    ];
@endphp

@section('title', 'قسيمة مرتب ' . $user->name . ' — ' . $monthLabel)
@section('back', route('payroll.index', ['month' => $monthLabel]))

@section('content')
    <header class="flex items-start justify-between border-b-2 border-gray-800 pb-4 mb-6">
        <div>
            <p class="text-2xl font-extrabold">أكوا ووتر</p>
            <p class="text-[12px] text-gray-500">لأنظمة تنقية المياه</p>
        </div>
        <div class="text-left">
            <p class="text-xl font-extrabold">قسيمة مرتب</p>
            <p class="text-[13px] text-gray-600">عن شهر <span dir="ltr">{{ $monthLabel }}</span></p>
        </div>
    </header>

    <section class="grid grid-cols-3 gap-4 mb-6 text-[13px]">
        <div><p class="text-gray-500">اسم الموظف</p><p class="font-bold">{{ $user->name }}</p></div>
        <div><p class="text-gray-500">الوظيفة</p><p class="font-bold">{{ $user->role ?? '—' }}</p></div>
        <div><p class="text-gray-500">القسم</p><p class="font-bold">{{ $user->department ?? '—' }}</p></div>
    </section>

    <table class="w-full text-[13px] mb-5 border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-right">المستحقات</th>
                <th class="p-2 text-right">التفاصيل</th>
                <th class="p-2 text-left">المبلغ (ج.م)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($earnings as [$label, $details, $amount])
                <tr class="border-t border-gray-200">
                    <td class="p-2 font-semibold">{{ $label }}</td>
                    <td class="p-2 text-gray-600">{{ $details }}</td>
                    <td class="p-2 text-left font-bold">{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="w-full text-[13px] mb-6 border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-right">الخصومات</th>
                <th class="p-2 text-right">التفاصيل</th>
                <th class="p-2 text-left">المبلغ (ج.م)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deductions as [$label, $details, $amount])
                <tr class="border-t border-gray-200">
                    <td class="p-2 font-semibold">{{ $label }}</td>
                    <td class="p-2 text-gray-600">{{ $details }}</td>
                    <td class="p-2 text-left font-bold text-red-600">{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="rounded-xl border-2 border-gray-800 p-4 flex items-center justify-between mb-4">
        <span class="text-lg font-extrabold">صافي المستحق</span>
        <span class="text-2xl font-extrabold">{{ number_format((float) $payroll->total, 2) }} ج.م</span>
    </div>

    @if ($payroll->payments->isNotEmpty())
        <div class="text-[12px] text-gray-600 mb-6">
            <p class="font-bold mb-1">الدفعات المصروفة:</p>
            @foreach ($payroll->payments as $payment)
                <p><span dir="ltr">{{ $payment->paid_at->format('Y-m-d') }}</span> — {{ number_format((float) $payment->amount, 2) }} ج.م</p>
            @endforeach
            <p class="font-bold mt-1">المتبقي: {{ number_format($payroll->remainingAmount(), 2) }} ج.م</p>
        </div>
    @endif

    @if ($payroll->note)
        <p class="text-[12px] text-gray-600 mb-6">ملاحظات: {{ $payroll->note }}</p>
    @endif

    <footer class="grid grid-cols-2 gap-10 mt-16 text-[13px]">
        <div class="text-center">
            <p class="font-bold mb-12">توقيع الموظف بالاستلام</p>
            <p class="border-t border-dotted border-gray-500 pt-1 text-gray-500">الاسم / التوقيع / التاريخ</p>
        </div>
        <div class="text-center">
            <p class="font-bold mb-12">المحاسب</p>
            <p class="border-t border-dotted border-gray-500 pt-1 text-gray-500">الاسم / التوقيع</p>
        </div>
    </footer>
@endsection
