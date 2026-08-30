@extends('layouts.app')

@section('title', 'عقود البيع')
@section('page-title', 'عقود البيع')
@section('page-subtitle', 'عقود البيع النقدية والتقسيط')

@php
    $hasFilters = collect(request()->only(['q', 'type', 'customer_id', 'from', 'to']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="file-text" subtitle="العقود النقدية والتقسيط" title="عقود البيع">
        <x-slot:actions>
            <x-ui.btn icon="plus" size="sm" :href="route('contracts.create')">عقد جديد</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 lg:gap-4 mb-4 lg:mb-6">
        <x-ui.kpi hint="كل العقود" icon="file-text" label="عدد العقود" tone="blue" unit="عقد"
            :value="number_format($stats['contracts'])" />
        <x-ui.kpi hint="بعد الخصم" icon="banknote" label="إجمالي التعاقدات" tone="green" unit="ج.م"
            :value="number_format($stats['total'])" />
        <x-ui.kpi hint="لم يُحصَّل بعد" icon="wallet" label="المتبقي للتحصيل" tone="amber" unit="ج.م"
            :value="number_format($stats['remaining'])" />
        <x-ui.kpi hint="بجدول أقساط" icon="calendar" label="عقود التقسيط" tone="purple" unit="عقد"
            :value="number_format($stats['installments'])" />
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('contracts.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث بكود العقد أو اسم العميل..." value="{{ request('q') }}"
                x-model="q" />

            <x-ui.select icon="credit-card" name="type" :options="$types" placeholder="كل الأنواع"
                :selected="request('type')" x-on:change="$el.form.submit()" />
            <x-ui.select icon="user-round" name="customer_id" :options="$customers" placeholder="كل العملاء"
                :selected="request('customer_id')" x-on:change="$el.form.submit()" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('contracts.index') }}">
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[900px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.sort-th column="code">كود العقد</x-ui.sort-th>
                        <x-ui.th>العميل</x-ui.th>
                        <x-ui.sort-th column="contract_date">تاريخ العقد</x-ui.sort-th>
                        <x-ui.th>النوع</x-ui.th>
                        <x-ui.th>البنود</x-ui.th>
                        <x-ui.sort-th column="total">الإجمالي</x-ui.sort-th>
                        <x-ui.th>المتبقي</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($contracts as $contract)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="text-[12px] font-bold text-gray-800" dir="ltr"
                                    href="{{ route('contracts.show', $contract) }}">{{ $contract->code }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-bold text-gray-800 truncate">
                                    {{ $contract->customer?->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400" dir="ltr">{{ $contract->customer?->code }}</p>
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">
                                {{ $contract->contract_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :tone="$contract->type->tone()" dot>{{ $contract->type->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-700">{{ $contract->items_count }}</td>
                            <td class="px-4 py-3">
                                <span class="text-[13px] font-bold text-gray-800">
                                    {{ number_format((float) $contract->total) }}</span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-[13px] font-bold {{ (float) $contract->remaining > 0 ? 'text-amber-600' : 'text-green-700' }}">
                                    {{ number_format((float) $contract->remaining) }}</span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="عرض" :href="route('contracts.show', $contract)" />
                                    <x-ui.icon-btn icon="pencil" label="تعديل" :href="route('contracts.edit', $contract)" />
                                    <form action="{{ route('contracts.destroy', $contract) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف العقد',
                                                message: {{ Js::from('سيتم حذف العقد ' . $contract->code . ' وإعادة كمياته إلى المخزن.') }},
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

        @if ($contracts->isEmpty())
            <x-ui.empty-state icon="file-x" :title="$hasFilters ? 'لا توجد عقود مطابقة' : 'لا توجد عقود بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'أنشئ أول عقد بيع لعميل من عملاء النظام.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('contracts.index')">إعادة ضبط
                            الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="plus" size="sm" :href="route('contracts.create')">إنشاء عقد</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$contracts" />
    </div>
@endsection
