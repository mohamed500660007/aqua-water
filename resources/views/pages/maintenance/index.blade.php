@extends('layouts.app')

@section('title', 'ملف الصيانة')
@section('page-title', 'ملف الصيانة')
@section('page-subtitle', 'شمع عملاء خطة الصيانة — قطاعي أو جملة')

@php
    $hasFilters = filled(request('q')) || filled(request('technician_id'));
@endphp

@section('content')
    <x-ui.page-header icon="calendar-clock" subtitle="شمع عملاء خطة الصيانة — قطاعي أو جملة" title="ملف الصيانة">
        <x-slot:actions>
            <x-ui.btn icon="shield-check" size="sm" variant="soft" :href="route('maintenance.plan')">خطة الصيانة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-hide p-3 border-b border-gray-100">
            @foreach ($statuses as $value => $label)
                <a class="shrink-0 px-3.5 py-2 rounded-xl text-[12px] font-bold transition-colors {{ $status === $value ? 'bg-brand-green/25 text-brand-dark' : 'text-gray-500 hover:bg-gray-50' }}"
                    href="{{ route('maintenance.file', array_filter([...request()->except(['status', 'page']), 'status' => $value === 'pending' ? null : $value])) }}" wire:navigate>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('maintenance.file') }}" method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <input name="status" type="hidden" value="{{ request('status') }}" />

            <x-ui.search-input name="q" placeholder="ابحث باسم العميل أو كوده أو كود العقد..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="wrench" name="technician_id" :options="$technicians" placeholder="كل الفنيين"
                :selected="request('technician_id')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('maintenance.file', request()->only('status')) }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[860px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.th>كود العميل</x-ui.th>
                        <x-ui.th>اسم العميل</x-ui.th>
                        <x-ui.th>النوع</x-ui.th>
                        <x-ui.th>تاريخ الصيانة</x-ui.th>
                        <x-ui.th>الشمعة</x-ui.th>
                        <x-ui.th>تم</x-ui.th>
                        <x-ui.th>الفني</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" x-data="{ editing: null }">
                    @foreach ($candles as $candle)
                        @php($customer = $candle->contract->customer)
                        @php($date = $candle->serviced_at ?? $candle->replacement_date)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3 text-[12px] font-bold text-gray-700" dir="ltr">{{ $customer?->code ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-bold text-gray-800">{{ $customer?->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400" dir="ltr">{{ $candle->contract->code }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if ($candle->service_type)
                                    <x-ui.badge :tone="$candle->service_type->tone()">{{ $candle->service_type->label() }}</x-ui.badge>
                                @else
                                    <span class="text-[12px] font-semibold text-gray-600">صيانة</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12px] font-semibold whitespace-nowrap {{ ! $candle->isDone() && $date->isPast() && ! $date->isToday() ? 'text-red-500' : 'text-gray-600' }}" dir="ltr">
                                {{ $date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-[12.5px] font-bold text-gray-800">{{ $candle->product_name }}</td>
                            <td class="px-4 py-3">
                                @if ($candle->isDone())
                                    <x-ui.badge tone="green" dot>تم</x-ui.badge>
                                @else
                                    <x-ui.badge tone="gray" dot>لا</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12.5px] font-semibold text-gray-700">{{ $candle->technician?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @unless ($candle->isDone())
                                    <div class="flex items-center justify-end gap-1">
                                        <x-ui.icon-btn icon="pencil" label="تعديل الفني والتاريخ"
                                            x-on:click="editing = editing === {{ $candle->id }} ? null : {{ $candle->id }}" />
                                        <a class="shrink-0 h-9 px-3.5 rounded-xl text-[12px] font-bold bg-brand-green/25 text-green-700 hover:bg-brand-green/40 transition-colors flex items-center gap-1.5"
                                            href="{{ route('maintenance.create', $candle) }}" wire:navigate>
                                            <x-icon class="w-3.5 h-3.5" name="plus" />
                                            إضافة
                                        </a>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                        @unless ($candle->isDone())
                            <tr x-cloak x-show="editing === {{ $candle->id }}">
                                <td class="px-4 pb-4 pt-0" colspan="8">
                                    <form action="{{ route('maintenance.update', $candle) }}" method="POST"
                                        class="rounded-2xl bg-gray-50 border border-gray-100 p-3.5 grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 sm:items-end">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الفني</label>
                                            <select class="w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none"
                                                name="technician_id" required>
                                                <option value="">اختار الفني</option>
                                                @foreach ($technicians as $id => $name)
                                                    <option value="{{ $id }}" @selected(($candle->technician_id ?? $candle->contract->installer_id) == $id)>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ الصيانة</label>
                                            <input class="w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none"
                                                name="date" required type="date" value="{{ $candle->replacement_date->format('Y-m-d') }}" />
                                        </div>
                                        <div class="flex gap-2">
                                            <button class="h-10 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:bg-gray-100" type="button" x-on:click="editing = null">إلغاء</button>
                                            <button class="h-10 px-4 rounded-xl text-[12px] font-bold bg-brand-green text-brand-dark hover:brightness-95" type="submit">حفظ</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endunless
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($candles->isEmpty())
            <x-ui.empty-state icon="calendar-check" :title="$hasFilters ? 'مفيش نتائج مطابقة' : ($status === 'done' ? 'لسه مفيش صيانة اتسجلت' : 'مفيش شمع مستني تغيير')"
                :message="$hasFilters ? 'جرّب تعدّل البحث أو تعيد ضبط الفلاتر.' : 'شمع العقود اللي ضمن خطة الصيانة بيظهر هنا لحد ما يتغير.'" />
        @endif

        <x-ui.paginator :paginator="$candles" />
    </div>
@endsection
