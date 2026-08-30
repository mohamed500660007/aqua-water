@extends('layouts.app')

@section('title', 'خطط الصيانة')
@section('page-title', 'خطط الصيانة')
@section('page-subtitle', 'خطط الصيانة الدورية وبنودها')

@php
    $hasFilters = collect(request()->only(['q', 'status']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="shield-check" subtitle="خطط الصيانة الدورية" title="خطط الصيانة">
        <x-slot:actions>
            <x-ui.btn icon="plus" size="sm" :href="route('maintenance-plans.create')">خطة جديدة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 lg:gap-4 mb-4 lg:mb-6">
        <x-ui.kpi hint="في النظام" icon="shield-check" label="إجمالي الخطط" tone="blue" unit="خطة"
            :value="number_format($stats['total'])" />
        <x-ui.kpi hint="جاهزة للربط بالعقود" icon="circle-check" label="خطط مفعّلة" tone="green" unit="خطة"
            :value="number_format($stats['active'])" />
        <x-ui.kpi hint="غير متاحة للاختيار" icon="circle-pause" label="خطط معطّلة" tone="gray" unit="خطة"
            :value="number_format($stats['inactive'])" />
        <x-ui.kpi hint="منتجات الاستبدال" icon="package" label="إجمالي البنود" tone="purple" unit="بند"
            :value="number_format($stats['items'])" />
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('maintenance-plans.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث باسم الخطة أو وصفها..." value="{{ request('q') }}" x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="circle-dot" name="status" :options="$statuses" placeholder="كل الحالات"
                :selected="request('status')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('maintenance-plans.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[760px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.sort-th column="name">الخطة</x-ui.sort-th>
                        <x-ui.th>البنود</x-ui.th>
                        <x-ui.th>المدة</x-ui.th>
                        <x-ui.sort-th column="status">الحالة</x-ui.sort-th>
                        <x-ui.sort-th column="created_at">تاريخ الإنشاء</x-ui.sort-th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($plans as $plan)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="min-w-0 block" href="{{ route('maintenance-plans.show', $plan) }}" wire:navigate>
                                    <p class="text-[13px] font-bold text-gray-800 truncate">{{ $plan->name }}</p>
                                    <p class="text-[11px] text-gray-400 truncate max-w-[280px]">
                                        {{ $plan->description ?: '—' }}</p>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-800">{{ $plan->items_count }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">
                                {{ $plan->duration_months ? $plan->duration_months . ' شهر' : '—' }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$plan->status->label()" dot /></td>
                            <td class="px-4 py-3 text-[12px] text-gray-500" dir="ltr">
                                {{ $plan->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="عرض" :href="route('maintenance-plans.show', $plan)" />
                                    <x-ui.icon-btn icon="pencil" label="تعديل" :href="route('maintenance-plans.edit', $plan)" />
                                    <form action="{{ route('maintenance-plans.destroy', $plan) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف خطة الصيانة',
                                                message: {{ Js::from('سيتم حذف خطة «' . $plan->name . '» وبنودها. العقود المرتبطة تحتفظ بنسختها الخاصة من البنود.') }},
                                                label: 'حذف',
                                                onOk: () => $root.requestSubmit()
                                            })" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($plans->isEmpty())
            <x-ui.empty-state icon="shield-off" :title="$hasFilters ? 'لا توجد خطط مطابقة' : 'لا توجد خطط صيانة بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'أنشئ خطة صيانة لتتمكن من ربطها بعقود البيع.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('maintenance-plans.index')">إعادة
                            ضبط الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="plus" size="sm" :href="route('maintenance-plans.create')">إضافة خطة</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$plans" />
    </div>
@endsection
