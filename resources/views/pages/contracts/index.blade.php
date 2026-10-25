@extends('layouts.app')

@section('title', 'عقود البيع')
@section('page-title', 'عقود البيع')
@section('page-subtitle', 'عقود البيع النقدية والتقسيط')

@php
    $hasFilters = collect(request()->only(['q', 'kind', 'type', 'customer_id', 'legal', 'from', 'to']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="file-text" subtitle="العقود النقدية والتقسيط" title="عقود البيع">
        <x-slot:actions>
            {{-- تُفتح في تبويب مستقل لأنها صفحة طباعة بلا هيكل التطبيق، وتحمل نفس فلاتر القائمة --}}
            <x-ui.btn icon="printer" size="sm" variant="dark" document :href="route('contracts.report', request()->only(['q', 'kind', 'type', 'customer_id', 'legal', 'from', 'to', 'sort', 'dir']))">تقرير العقود</x-ui.btn>
            <x-ui.btn icon="file-spreadsheet" size="sm" variant="soft" :href="route('contracts.import.create')">استيراد من إكسيل</x-ui.btn>
            <x-ui.btn icon="shield-plus" size="sm" variant="soft" :href="route('contracts.create', ['kind' => 'maintenance'])">عقد صيانة جديد</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('contracts.create')">عقد جديد</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('contracts.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث بكود العقد أو اسم العميل..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="layers" name="kind" :options="$kinds" placeholder="بيع وصيانة"
                :selected="request('kind')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="scale" name="legal" :options="['1' => 'محوّل للشؤون القانونية']" placeholder="كل الحالات"
                :selected="request('legal')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="credit-card" name="type" :options="$types" placeholder="كل الأنواع"
                :selected="request('type')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="user-round" name="customer_id" :options="$customers" placeholder="كل العملاء"
                :selected="request('customer_id')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('contracts.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        {{-- معاينة العقد تفتح لوحة جانبية بدل الانتقال لصفحة أخرى --}}
        <div class="overflow-x-auto scrollbar-hide" x-data>
            <table class="w-full min-w-[900px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.sort-th column="code">الكود</x-ui.sort-th>
                        <x-ui.th>العميل</x-ui.th>
                        <x-ui.sort-th column="contract_date">تاريخ العقد</x-ui.sort-th>
                        <x-ui.th>المركز</x-ui.th>
                        <x-ui.th>نوع الجهاز</x-ui.th>
                        <x-ui.th>القسط</x-ui.th>
                        <x-ui.th>الفني</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($contracts as $contract)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <button class="text-[12px] font-bold text-gray-800 hover:underline" dir="ltr" type="button"
                                    x-on:click="$store.drawer.show({{ Js::from(route('contracts.panel', $contract)) }})">{{ $contract->code }}</button>
                                @if ($contract->isReferredToLegal())
                                    <div class="mt-1"><x-ui.badge tone="red" dot>قانونية</x-ui.badge></div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-800">{{ $contract->customer?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">
                                {{ $contract->contract_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-[12px] font-semibold text-gray-700">{{ $contract->customer?->center ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <p class="text-[12px] font-semibold text-gray-700 max-w-[220px] line-clamp-2">
                                    {{ $contract->items->pluck('product_name')->unique()->join('، ') ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if ($contract->isInstallment() && $contract->installment_amount)
                                    <span class="text-[13px] font-bold text-gray-800">
                                        {{ number_format((float) $contract->installment_amount, 2) }}</span>
                                    <span class="text-[10px] text-gray-400">ج.م</span>
                                @else
                                    <x-ui.badge :tone="$contract->type->tone()" dot>{{ $contract->type->label() }}</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12px] font-semibold text-gray-700">{{ $contract->installer?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="عرض" tone="primary"
                                        x-on:click="$store.drawer.show({{ Js::from(route('contracts.panel', $contract)) }})" />
                                    <x-ui.icon-btn icon="pencil" label="تعديل" :href="route('contracts.edit', $contract)" />
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
