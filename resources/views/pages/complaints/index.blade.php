@extends('layouts.app')

@section('title', 'الشكاوى')
@section('page-title', 'الشكاوى')
@section('page-subtitle', 'شكاوى العملاء وما صُرف فيها من قطع غيار ورسوم زيارات')

@php
    $hasFilters = collect(request()->only(['q', 'customer_id', 'resolved']))->filter(fn($v) => $v !== null && $v !== '')->isNotEmpty();
    $resolvedOptions = ['1' => 'تم الحل', '0' => 'لم يتم الحل'];
@endphp

@section('content')
    <x-ui.page-header icon="message-square-warning" subtitle="شكاوى العملاء" title="الشكاوى">
        <x-slot:actions>
            <x-ui.btn icon="plus" size="sm" :href="route('complaints.create')">شكوى جديدة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-3 gap-2.5 mb-4 lg:mb-6">
        <x-ui.kpi compact icon="message-square-warning" label="إجمالي الشكاوى" tone="blue" unit="شكوى"
            :value="number_format($stats['total'])" />
        <x-ui.kpi compact icon="circle-alert" label="لم تُحل بعد" tone="red" unit="شكوى"
            :value="number_format($stats['open'])" />
        <x-ui.kpi compact icon="circle-check" label="تم حلها" tone="green" unit="شكوى"
            :value="number_format($stats['resolved'])" />
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('complaints.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }"
            x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث بكود العقد أو اسم العميل..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="circle-check" name="resolved" :options="$resolvedOptions" placeholder="كل الحالات"
                :selected="request('resolved')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="user-round" name="customer_id" :options="$customers" placeholder="كل العملاء"
                :selected="request('customer_id')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('complaints.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[980px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.th>العميل</x-ui.th>
                        <x-ui.th>الجهاز</x-ui.th>
                        <x-ui.th>الفني / المندوبة</x-ui.th>
                        <x-ui.th>قطع الغيار</x-ui.th>
                        <x-ui.sort-th column="complaint_date">التاريخ</x-ui.sort-th>
                        <x-ui.th>الإجمالي</x-ui.th>
                        <x-ui.th>الحالة</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($complaints as $complaint)
                        @php
                            $partsTotal = $complaint->partsTotal();
                            $device = $complaint->contract->items->pluck('product_name')->join('، ');
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="text-[13px] font-bold text-gray-800 truncate block"
                                    href="{{ route('customers.show', $complaint->contract->customer_id) }}" wire:navigate>
                                    {{ $complaint->contract->customer?->name ?? '—' }}</a>
                                <a class="text-[11px] text-gray-400" dir="ltr"
                                    href="{{ route('contracts.show', $complaint->sales_contract_id) }}" wire:navigate>
                                    {{ $complaint->contract->code }}</a>
                            </td>
                            <td class="px-4 py-3 text-[12px] font-bold text-gray-700 max-w-[180px] truncate"
                                title="{{ $device }}">{{ $device ?: '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">
                                <div>{{ $complaint->technician?->name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $complaint->salesRep?->name ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($complaint->parts->isEmpty())
                                    <span class="text-[11px] font-bold text-gray-400">بدون قطع — رسوم زيارة</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($complaint->parts as $part)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-gray-50 border border-gray-100 text-[11px] font-bold text-gray-700">
                                                {{ $part->product?->name ?? '—' }}
                                                <span class="text-gray-400 font-normal">× {{ $part->quantity }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">
                                {{ $complaint->complaint_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-[13px] font-bold text-gray-800">
                                    {{ number_format($complaint->total(), 2) }}</span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                                @if ($partsTotal > 0 && (float) $complaint->visit_fee > 0)
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        قطع {{ number_format($partsTotal, 2) }} + زيارة
                                        {{ number_format((float) $complaint->visit_fee, 2) }}</p>
                                @elseif ((float) $complaint->visit_fee > 0)
                                    <p class="text-[10px] text-gray-400 mt-0.5">رسوم زيارة</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <form action="{{ route('complaints.resolve', $complaint) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input name="resolved" type="hidden" value="{{ $complaint->resolved ? 0 : 1 }}" />
                                    <button
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-colors active:scale-95 {{ $complaint->resolved ? 'bg-green-50 text-green-700 border-green-100 hover:bg-green-100' : 'bg-red-50 text-red-600 border-red-100 hover:bg-red-100' }}"
                                        title="{{ $complaint->resolved ? 'اضغط لإعادتها إلى غير محلولة' : 'اضغط لتعليمها كمحلولة' }}"
                                        type="submit">
                                        <x-icon class="w-3.5 h-3.5" :name="$complaint->resolved ? 'circle-check' : 'circle-alert'" />
                                        {{ $complaint->resolved ? 'تم الحل' : 'لم يتم الحل' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف الشكوى',
                                                message: {{ Js::from($complaint->parts->isEmpty() ? 'سيتم حذف الشكوى نهائيًا.' : 'سيتم حذف الشكوى وإعادة '.$complaint->parts->sum('quantity').' قطعة إلى مصدرها.') }},
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

        @if ($complaints->isEmpty())
            <x-ui.empty-state icon="message-square-warning"
                :title="$hasFilters ? 'لا توجد شكاوى مطابقة' : 'لا توجد شكاوى بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'سجّل أول شكوى لعميل قائم.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('complaints.index')">
                            إعادة ضبط الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="plus" size="sm" :href="route('complaints.create')">شكوى جديدة</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$complaints" />
    </div>
@endsection
