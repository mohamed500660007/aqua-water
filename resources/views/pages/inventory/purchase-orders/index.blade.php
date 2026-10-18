@extends('layouts.app')

@section('title', 'المخزن')
@section('page-title', 'المخزن')
@section('page-subtitle', 'طلبيات البضاعة الواردة إلى المخازن')

@php
    $hasFilters = collect(request()->only(['q', 'warehouse_id', 'from', 'to']))->filter()->isNotEmpty();
    $visibleItems = 3;
@endphp

@section('content')
    <x-ui.page-header icon="store" subtitle="طلبيات البضاعة الواردة إلى المخازن" title="المخزن">
        <x-slot:actions>
            {{-- تُفتح في تبويب مستقل لأنها صفحة طباعة بلا هيكل التطبيق، وتحمل نفس فلاتر الصفحة --}}
            <x-ui.btn icon="printer" size="sm" variant="dark" document :href="route('inventory.purchase-orders.report', request()->only(['q', 'warehouse_id', 'from', 'to']))">تقرير المخزن</x-ui.btn>
            <x-ui.btn icon="package-plus" size="sm" variant="soft" :href="route('inventory.stock.create')">تسوية / إضافة رصيد</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('inventory.purchase-orders.create')">طلبية جديدة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- البحث والفلاتر --}}
        <form class="p-4 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_200px_160px_160px_auto] gap-2.5 lg:items-end"
            action="{{ route('inventory.purchase-orders.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }"
            x-on:submit.prevent="navigateForm($el)">
            <div class="sm:col-span-2 lg:col-span-1">
                <x-ui.search-input name="q" placeholder="ابحث برقم الطلبية أو المورد أو اسم المنتج..." value="{{ request('q') }}"
                    x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />
            </div>
            <x-ui.select icon="warehouse" name="warehouse_id" :options="$warehouses" placeholder="كل المخازن"
                :selected="request('warehouse_id')" x-on:change="navigateForm($el.form)" />
            <label class="flex flex-col gap-1">
                <span class="text-[11px] font-bold text-gray-400">من تاريخ</span>
                <input class="h-11 px-3 rounded-xl border border-gray-200 bg-white text-[13px] text-gray-700 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none"
                    name="from" type="date" value="{{ request('from') }}" x-on:change="navigateForm($el.form)" />
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-[11px] font-bold text-gray-400">إلى تاريخ</span>
                <input class="h-11 px-3 rounded-xl border border-gray-200 bg-white text-[13px] text-gray-700 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none"
                    name="to" type="date" value="{{ request('to') }}" x-on:change="navigateForm($el.form)" />
            </label>
            <div class="flex gap-2">
                <x-ui.btn class="flex-1 lg:flex-none" icon="search" type="submit">بحث</x-ui.btn>
                @if ($hasFilters)
                    <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                        href="{{ route('inventory.purchase-orders.index') }}" wire:navigate>
                        <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                        إعادة ضبط
                    </a>
                @endif
            </div>
        </form>

        {{-- ملخص كل الطلبيات المطابقة للفلاتر — سطر واحد بدل الكروت --}}
        <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-1 text-[12px] text-gray-500">
            <span class="flex items-center gap-1.5"><x-icon class="w-3.5 h-3.5" name="clipboard-list" />
                الطلبيات: <b class="text-gray-800">{{ number_format($summary['orders']) }}</b></span>
            <span class="flex items-center gap-1.5"><x-icon class="w-3.5 h-3.5" name="boxes" />
                إجمالي القطع: <b class="text-gray-800">{{ number_format($summary['pieces']) }}</b></span>
            <span class="flex items-center gap-1.5"><x-icon class="w-3.5 h-3.5" name="banknote" />
                إجمالي المبالغ: <b class="text-gray-800">{{ number_format($summary['amount'], 2) }}</b> ج.م</span>
            @if ($hasFilters)
                <span class="text-[11px] text-amber-600 lg:mr-auto">حسب الفلاتر الحالية</span>
            @endif
        </div>

        <div class="overflow-x-auto scrollbar-hide" x-data>
            <table class="w-full min-w-[920px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.th>رقم الطلبية</x-ui.th>
                        <x-ui.th>التاريخ</x-ui.th>
                        <x-ui.th>المخزن</x-ui.th>
                        <x-ui.th>المورد</x-ui.th>
                        <x-ui.th>الأصناف</x-ui.th>
                        <x-ui.th>القطع</x-ui.th>
                        <x-ui.th>المبلغ</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($orders as $order)
                        @php($panel = Js::from(route('inventory.purchase-orders.panel', $order)))
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <button class="text-[12.5px] font-black text-gray-800 hover:underline font-mono" dir="ltr" type="button"
                                    x-on:click="$store.drawer.show({{ $panel }})">{{ $order->number() }}</button>
                            </td>
                            <td class="px-4 py-3 text-[12px] font-semibold text-gray-600 whitespace-nowrap" dir="ltr">{{ $order->order_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-800">{{ $order->warehouse?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <p class="text-[12.5px] font-semibold text-gray-700">{{ $order->supplier ?? '—' }}</p>
                                @if ($order->note)
                                    <p class="text-[11px] text-gray-400 truncate max-w-[180px]" title="{{ $order->note }}">{{ $order->note }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-[300px]">
                                    @foreach ($order->items->take($visibleItems) as $item)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">
                                            {{ $item->product?->name ?? 'منتج محذوف' }}
                                            <b class="text-gray-800">× {{ number_format($item->quantity) }}</b>
                                        </span>
                                    @endforeach
                                    @if ($order->items->count() > $visibleItems)
                                        <button class="rounded-lg border border-gray-200 px-2 py-0.5 text-[11px] font-bold text-gray-500 hover:bg-gray-50" type="button"
                                            x-on:click="$store.drawer.show({{ $panel }})">+{{ $order->items->count() - $visibleItems }} أصناف</button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-800">{{ number_format((int) $order->pieces) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-[13px] font-black text-gray-800">{{ number_format((float) $order->total_amount, 2) }}</span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="معاينة" tone="primary" x-on:click="$store.drawer.show({{ $panel }})" />
                                    <form action="{{ route('inventory.purchase-orders.destroy', $order) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف الطلبية',
                                                message: {{ Js::from('ستُخصم كميات الطلبية ' . $order->number() . ' من ' . ($order->warehouse?->name ?? 'المخزن') . '.') }},
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

        @if ($orders->isEmpty())
            <x-ui.empty-state icon="truck" :title="$hasFilters ? 'لا توجد طلبيات مطابقة' : 'لا توجد طلبيات بعد'"
                :message="$hasFilters ? 'جرّب تعديل البحث أو الفترة أو إعادة ضبط الفلاتر.' : 'سجّل أول طلبية وارد لتظهر في تقارير المخزن والتقرير المالي.'">
                <x-slot:action>
                    @unless ($hasFilters)
                        <x-ui.btn icon="plus" size="sm" :href="route('inventory.purchase-orders.create')">طلبية جديدة</x-ui.btn>
                    @endunless
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$orders" />
    </div>
@endsection
