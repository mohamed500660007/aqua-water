@extends('layouts.app')

@section('title', 'خطة الصيانة')
@section('page-title', 'خطة الصيانة')
@section('page-subtitle', 'سبع شمعات بمواعيد ثابتة لكل الفلاتر، تُحسب من تاريخ التركيب')

@php
    use App\Domain\Maintenance\Models\MaintenancePlan;
@endphp

@section('content')
    <x-ui.page-header icon="shield-check" subtitle="سبع شمعات بمواعيد ثابتة لكل الفلاتر، تُحسب من تاريخ التركيب" title="خطة الصيانة" />

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <header class="flex items-center gap-2.5 px-5 py-4 border-b border-gray-100">
            <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                <x-icon class="w-[18px] h-[18px]" name="droplets" />
            </span>
            <div>
                <h2 class="text-sm font-bold text-gray-800">شمعات الخطة</h2>
                <p class="text-[11px] text-gray-400">الخطة ثابتة لكل العملاء — كل عقد ضمن الخطة تتحسب مواعيده من تاريخ تركيب الجهاز.</p>
            </div>
        </header>

        <ol class="divide-y divide-gray-50">
            @foreach ($intervals as $position => $months)
                <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 shrink-0 rounded-xl bg-brand-dark text-brand-green flex items-center justify-center text-[15px] font-black">
                            {{ $position }}</span>
                        <p class="text-[14px] font-extrabold text-gray-800">{{ MaintenancePlan::candleLabel($position) }}</p>
                    </div>

                    <x-ui.badge :tone="$months === 12 ? 'purple' : ($months === 1 ? 'green' : 'blue')">
                        {{ MaintenancePlan::intervalLabel($months) }}</x-ui.badge>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
