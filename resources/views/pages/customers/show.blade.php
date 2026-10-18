@extends('layouts.app')

@section('title', $customer->name)
@section('page-title', $customer->name)
@section('page-subtitle', 'تفاصيل العميل')

@section('content')
    <x-ui.page-header icon="user-round" :subtitle="'كود العميل: ' . $customer->code" :title="$customer->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('customers.index')">رجوع للقائمة</x-ui.btn>
            <x-ui.btn icon="pencil" size="sm" :href="route('customers.edit', $customer)">تعديل البيانات</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
        {{-- بطاقة العميل --}}
        <div class="lg:col-span-1 space-y-4 lg:space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-4 mb-5">
                    <x-ui.avatar :name="$customer->name" size="w-14 h-14 text-base" />
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-800 truncate">{{ $customer->name }}</p>
                        <p class="text-[12px] text-gray-400" dir="ltr">{{ $customer->code }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 mb-4">
                    <span class="text-[12px] text-gray-400">الحالة</span>
                    <x-ui.badge :tone="$customer->status->tone()" dot>{{ $customer->status->label() }}</x-ui.badge>
                </div>

                <div class="flex items-center justify-between gap-3 mb-4">
                    <span class="text-[12px] text-gray-400">نوع العميل</span>
                    <x-ui.badge :tone="$customer->origin->tone()">{{ $customer->origin->label() }}</x-ui.badge>
                </div>

                <x-ui.detail-row label="الرقم القومي" :value="$customer->national_id ?? '—'" />
                <x-ui.detail-row label="تاريخ التسجيل" :value="$customer->registered_at?->format('Y-m-d') ?? '—'" />
                <x-ui.detail-row label="أُضيف في" :value="$customer->created_at?->format('Y-m-d')" />
                <x-ui.detail-row label="آخر تعديل" :value="$customer->updated_at?->format('Y-m-d')" />
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="map-pin" />
                    </span>
                    <h2 class="text-sm font-bold text-gray-800">العنوان</h2>
                </div>

                <x-ui.detail-row label="المحافظة" :value="$customer->governorate ?? '—'" />
                <x-ui.detail-row label="المركز" :value="$customer->center ?? '—'" />
                <x-ui.detail-row label="القرية" :value="$customer->village ?? '—'" />
                <x-ui.detail-row label="العنوان التفصيلي" :value="$customer->detailed_address ?? '—'" />
            </div>
        </div>

        {{-- الهواتف والملاحظات --}}
        <div class="lg:col-span-2 space-y-4 lg:space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="phone" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">أرقام الهاتف</h2>
                    </div>
                    <span class="text-[11px] font-bold text-gray-400">{{ $customer->phones->count() }} رقم</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse ($customer->phones as $phone)
                        <div class="flex items-center justify-between gap-3 p-3.5 rounded-2xl border border-gray-100">
                            <a class="flex items-center gap-3 min-w-0" href="tel:{{ $phone->phone }}">
                                <span
                                    class="w-9 h-9 shrink-0 rounded-xl bg-gray-50 text-gray-500 flex items-center justify-center">
                                    <x-icon class="w-[17px] h-[17px]" name="phone" />
                                </span>
                                <span class="text-[13px] font-bold text-gray-800" dir="ltr">{{ $phone->phone }}</span>
                            </a>
                            @if ($phone->is_primary)
                                <x-ui.badge tone="green">أساسي</x-ui.badge>
                            @endif
                        </div>
                    @empty
                        <p class="text-[12px] text-gray-400">لا توجد أرقام مسجلة.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="notebook-pen" />
                    </span>
                    <h2 class="text-sm font-bold text-gray-800">ملاحظات</h2>
                </div>

                <p class="text-[13px] text-gray-600 leading-relaxed whitespace-pre-line">
                    {{ $customer->notes ?: 'لا توجد ملاحظات مسجلة على هذا العميل.' }}</p>
            </div>
        </div>
    </div>
@endsection
