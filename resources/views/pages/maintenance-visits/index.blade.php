@extends('layouts.app')

@section('title', 'زيارات الصيانة')
@section('page-title', 'زيارات الصيانة')
@section('page-subtitle', 'سجل تغيير الشمعات وقطع الصيانة')

@php
    $hasFilters = collect(request()->only(['q', 'customer_id', 'warranty_status']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="wrench" subtitle="سجل تغيير الشمعات وقطع الصيانة" title="زيارات الصيانة">
        <x-slot:actions>
            <x-ui.btn icon="plus" size="sm" :href="route('maintenance-visits.create')">زيارة صيانة جديدة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('maintenance-visits.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }"
            x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث بكود العقد أو اسم العميل..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="shield-check" name="warranty_status" :options="$warrantyStatuses" placeholder="كل الحالات"
                :selected="request('warranty_status')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="user-round" name="customer_id" :options="$customers" placeholder="كل العملاء"
                :selected="request('customer_id')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('maintenance-visits.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[860px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.th>العميل</x-ui.th>
                        <x-ui.th>العقد</x-ui.th>
                        <x-ui.th>القطعة</x-ui.th>
                        <x-ui.th>الفني</x-ui.th>
                        <x-ui.sort-th column="performed_at">تاريخ التنفيذ</x-ui.sort-th>
                        <x-ui.sort-th column="price">السعر</x-ui.sort-th>
                        <x-ui.th>الضمان</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($visits as $visit)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="text-[13px] font-bold text-gray-800 truncate block"
                                    href="{{ route('customers.show', $visit->contract->customer_id) }}" wire:navigate>
                                    {{ $visit->contract->customer?->name ?? '—' }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <a class="text-[12px] font-bold text-gray-700" dir="ltr"
                                    href="{{ route('contracts.show', $visit->sales_contract_id) }}" wire:navigate>
                                    {{ $visit->contract->code }}</a>
                            </td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-700">{{ $visit->product?->name ?? '—' }}
                                <span class="text-[11px] text-gray-400 font-normal">× {{ $visit->quantity }}</span>
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">{{ $visit->technician?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">
                                {{ $visit->performed_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-[13px] font-bold text-gray-800">
                                    {{ number_format((float) $visit->price, 2) }}</span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :tone="$visit->warranty_status->tone()" dot>
                                    {{ $visit->warranty_status->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <form action="{{ route('maintenance-visits.destroy', $visit) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف زيارة الصيانة',
                                                message: 'سيُعاد ' + {{ Js::from($visit->quantity) }} + ' قطعة من «' + {{ Js::from($visit->product?->name) }} + '» إلى مصدرها.',
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

        @if ($visits->isEmpty())
            <x-ui.empty-state icon="wrench" :title="$hasFilters ? 'لا توجد زيارات مطابقة' : 'لا توجد زيارات صيانة بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'سجّل أول زيارة صيانة لعقد قائم.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('maintenance-visits.index')">
                            إعادة ضبط الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="plus" size="sm" :href="route('maintenance-visits.create')">زيارة صيانة
                            جديدة</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$visits" />
    </div>
@endsection
