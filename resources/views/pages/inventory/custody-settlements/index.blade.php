@extends('layouts.app')

@section('title', 'تسويات العهد')
@section('page-title', 'تسويات العهد')
@section('page-subtitle', 'تصحيح أرصدة عهد الموظفين بالموجب أو بالسالب دون أي حركة مخزنية')

@php($hasFilters = collect(request()->only(['user_id', 'type', 'status']))->filter()->isNotEmpty())

@section('content')
    <x-ui.page-header icon="scale" subtitle="تصحيح أرصدة عهد الموظفين بالموجب أو بالسالب دون أي حركة مخزنية" title="تسويات العهد">
        <x-slot:actions>
            <x-ui.btn icon="user-round-check" size="sm" variant="soft" :href="route('inventory.employee-stocks.index')">مخزون
                الموظفين</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('inventory.custody-settlements.create', request()->only('user_id'))">تسوية جديدة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 border-b border-gray-100 flex flex-wrap gap-2.5 items-center"
            action="{{ route('inventory.custody-settlements.index') }}" method="GET" x-data x-on:submit.prevent="navigateForm($el)">
            <x-ui.select icon="user-round" name="user_id" :options="$employees" placeholder="كل الموظفين"
                :selected="request('user_id')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="arrow-down-up" name="type" :options="$types" placeholder="كل الأنواع"
                :selected="request('type')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="list-checks" name="status" :options="$statuses" placeholder="كل الحالات"
                :selected="request('status')" x-on:change="navigateForm($el.form)" />
            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('inventory.custody-settlements.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide" x-data>
            <table class="w-full min-w-[860px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.th>رقم التسوية</x-ui.th>
                        <x-ui.th>التاريخ</x-ui.th>
                        <x-ui.th>الموظف</x-ui.th>
                        <x-ui.th>النوع</x-ui.th>
                        <x-ui.th>السبب</x-ui.th>
                        <x-ui.th>الأصناف / القطع</x-ui.th>
                        <x-ui.th>الحالة</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($settlements as $settlement)
                        @php($panel = Js::from(route('inventory.custody-settlements.panel', $settlement)))
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <button class="text-[12.5px] font-black text-gray-800 hover:underline font-mono" dir="ltr" type="button"
                                    x-on:click="$store.drawer.show({{ $panel }})">{{ $settlement->number() }}</button>
                            </td>
                            <td class="px-4 py-3 text-[12px] font-semibold text-gray-600 whitespace-nowrap" dir="ltr">
                                {{ $settlement->settlement_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <a class="text-[13px] font-bold text-gray-800 hover:underline"
                                    href="{{ route('inventory.employee-stocks.show', $settlement->user_id) }}" wire:navigate>
                                    {{ $settlement->user?->name ?? '—' }}</a>
                            </td>
                            <td class="px-4 py-3"><x-ui.badge :status="$settlement->type->label()" :tone="$settlement->type->tone()" /></td>
                            <td class="px-4 py-3">
                                <p class="text-[12.5px] font-semibold text-gray-700 truncate max-w-[220px]" title="{{ $settlement->reason }}">
                                    {{ $settlement->reason }}</p>
                            </td>
                            <td class="px-4 py-3 text-[12.5px] text-gray-600">
                                {{ $settlement->items_count }} / <b class="text-gray-800">{{ number_format((int) $settlement->pieces) }}</b>
                            </td>
                            <td class="px-4 py-3"><x-ui.badge :status="$settlement->status->label()" :tone="$settlement->status->tone()" dot /></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="معاينة" tone="primary" x-on:click="$store.drawer.show({{ $panel }})" />
                                    @if ($settlement->isDraft())
                                        <x-ui.icon-btn icon="pencil" label="تعديل" :href="route('inventory.custody-settlements.edit', $settlement)" />
                                        @include('pages.inventory.custody-settlements.partials.post-button', ['iconOnly' => true])
                                        @include('pages.inventory.custody-settlements.partials.delete-button')
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($settlements->isEmpty())
            <x-ui.empty-state icon="scale" :title="$hasFilters ? 'لا توجد تسويات مطابقة' : 'لا توجد تسويات بعد'"
                :message="$hasFilters ? 'جرّب تعديل الفلاتر أو إعادة ضبطها.' : 'سجّل تسوية عند اكتشاف عجز أو زيادة في عهدة أحد الموظفين.'">
                <x-slot:action>
                    @unless ($hasFilters)
                        <x-ui.btn icon="plus" size="sm" :href="route('inventory.custody-settlements.create')">تسوية جديدة</x-ui.btn>
                    @endunless
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$settlements" />
    </div>
@endsection
