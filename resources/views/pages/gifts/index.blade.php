@extends('layouts.app')

@section('title', 'الهدايا')
@section('page-title', 'الهدايا')
@section('page-subtitle', 'قطع الغيار المُهداة للعملاء ومن سلّمها')

@php
    $hasFilters = collect(request()->only(['q', 'customer_id', 'technician_id', 'product_id']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="gift" subtitle="قطع الغيار المُهداة للعملاء" title="الهدايا">
        <x-slot:actions>
            <x-ui.btn icon="plus" size="sm" :href="route('gifts.create')">هدية جديدة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('gifts.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }"
            x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث باسم العميل أو كوده..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="user-round" name="customer_id" :options="$customers" placeholder="كل العملاء"
                :selected="request('customer_id')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="wrench" name="technician_id" :options="$technicians" placeholder="كل الفنيين"
                :selected="request('technician_id')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="package" name="product_id" :options="$products" placeholder="كل القطع"
                :selected="request('product_id')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('gifts.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[760px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.th>اسم العميل</x-ui.th>
                        <x-ui.sort-th column="gift_date">التاريخ</x-ui.sort-th>
                        <x-ui.th>اسم الفني</x-ui.th>
                        <x-ui.th>قطعة الغيار</x-ui.th>
                        <x-ui.th>خُصمت من</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($gifts as $gift)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="text-[13px] font-bold text-gray-800 truncate block"
                                    href="{{ route('customers.show', $gift->customer_id) }}" wire:navigate>
                                    {{ $gift->customer?->name ?? '—' }}</a>
                                <span class="text-[11px] text-gray-400" dir="ltr">{{ $gift->customer?->code }}</span>
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">{{ $gift->gift_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">{{ $gift->technician?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-700">
                                {{ $gift->product?->name ?? '—' }}
                                <span class="text-[11px] text-gray-400 font-normal">× {{ $gift->quantity }}</span>
                                @if ($gift->note)
                                    <p class="text-[11px] text-gray-400 font-normal mt-0.5">{{ $gift->note }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">
                                {{ $gift->stock_source === \App\Domain\Sales\Enums\StockSource::Warehouse ? $gift->warehouse?->name ?? 'المخزن' : 'عهدة الفني' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <form action="{{ route('gifts.destroy', $gift) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف الهدية',
                                                message: {{ Js::from('سيُعاد '.$gift->quantity.' قطعة من «'.($gift->product?->name).'» إلى مصدرها.') }},
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

        @if ($gifts->isEmpty())
            <x-ui.empty-state icon="gift" :title="$hasFilters ? 'لا توجد هدايا مطابقة' : 'لا توجد هدايا بعد'"
                :message="$hasFilters ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.' : 'سجّل أول قطعة غيار مُهداة لعميل.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('gifts.index')">إعادة ضبط الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="plus" size="sm" :href="route('gifts.create')">هدية جديدة</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$gifts" />
    </div>
@endsection
