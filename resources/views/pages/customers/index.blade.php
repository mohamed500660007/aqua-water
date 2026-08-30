@extends('layouts.app')

@section('title', 'العملاء')
@section('page-title', 'العملاء')
@section('page-subtitle', 'إدارة قاعدة عملاء أكوا ووتر')

@php
    $hasFilters = collect(request()->only(['q', 'status', 'governorate', 'center', 'village']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="users" subtitle="إدارة قاعدة العملاء" title="العملاء">
        <x-slot:actions>
            <x-ui.btn icon="user-plus" size="sm" :href="route('customers.create')">عميل جديد</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 xl:grid-cols-3 gap-3 lg:gap-4 mb-4 lg:mb-6">
        <x-ui.kpi hint="مسجلون في النظام" icon="users" label="إجمالي العملاء" tone="blue" unit="عميل"
            :value="number_format($stats['total'])" />
        <x-ui.kpi hint="حالة نشطة" icon="user-check" label="عملاء نشطون" tone="green" unit="عميل"
            :value="number_format($stats['active'])" />
        <x-ui.kpi hint="بحاجة إلى متابعة" icon="user-x" label="غير نشطين" tone="gray" unit="عميل"
            :value="number_format($stats['inactive'])" />
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- البحث والفلاتر تتم على الخادم مع الحفاظ على الترتيب الحالي --}}
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('customers.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث بالكود أو الاسم أو الرقم القومي أو رقم الهاتف..."
                value="{{ request('q') }}" x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="circle-dot" name="status" :options="$statuses" placeholder="كل الحالات"
                :selected="request('status')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="map-pin" name="governorate" :options="$governorates" placeholder="كل المحافظات"
                :selected="request('governorate')" x-on:change="navigateForm($el.form)" />
            @if ($centers)
                <x-ui.select icon="building-2" name="center" :options="$centers" placeholder="كل المراكز"
                    :selected="request('center')" x-on:change="navigateForm($el.form)" />
            @endif
            @if ($villages)
                <x-ui.select icon="house" name="village" :options="$villages" placeholder="كل القرى"
                    :selected="request('village')" x-on:change="navigateForm($el.form)" />
            @endif

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('customers.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[880px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.sort-th column="name">العميل</x-ui.sort-th>
                        <x-ui.sort-th column="code">الكود</x-ui.sort-th>
                        <x-ui.th>الهاتف</x-ui.th>
                        <x-ui.th>الموقع</x-ui.th>
                        <x-ui.sort-th column="registered_at">تاريخ التسجيل</x-ui.sort-th>
                        <x-ui.sort-th column="status">الحالة</x-ui.sort-th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($customers as $customer)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="flex items-center gap-3 min-w-0" href="{{ route('customers.show', $customer) }}" wire:navigate>
                                    <x-ui.avatar :name="$customer->name" size="w-10 h-10" />
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-gray-800 truncate">{{ $customer->name }}</p>
                                        <p class="text-[11px] text-gray-400" dir="ltr">
                                            {{ $customer->national_id ?? '—' }}</p>
                                    </div>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-[12px] font-bold text-gray-700" dir="ltr">{{ $customer->code }}</td>
                            <td class="px-4 py-3">
                                <p class="text-[12px] font-semibold text-gray-700" dir="ltr">
                                    {{ $customer->primaryPhone?->phone ?? '—' }}</p>
                                @if ($customer->phones_count > 1)
                                    <p class="text-[11px] text-gray-400">+{{ $customer->phones_count - 1 }} رقم إضافي</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[12px] font-semibold text-gray-700">{{ $customer->governorate ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400 truncate max-w-[180px]">
                                    {{ collect([$customer->center, $customer->village])->filter()->join(' — ') ?: '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-[12px] font-semibold text-gray-700" dir="ltr">
                                {{ $customer->registered_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$customer->status->label()" dot /></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="عرض" :href="route('customers.show', $customer)" />
                                    <x-ui.icon-btn icon="pencil" label="تعديل" :href="route('customers.edit', $customer)" />
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف العميل',
                                                message: {{ Js::from('سيتم حذف «' . $customer->name . '» مع أرقام هاتفه من قائمة العملاء.') }},
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

        @if ($customers->isEmpty())
            <x-ui.empty-state icon="user-x" :title="$hasFilters ? 'لا يوجد عملاء مطابقون' : 'لا يوجد عملاء بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'ابدأ بإضافة أول عميل إلى قاعدة البيانات.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('customers.index')">إعادة ضبط
                            الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="user-plus" size="sm" :href="route('customers.create')">إضافة عميل</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$customers" />
    </div>
@endsection
