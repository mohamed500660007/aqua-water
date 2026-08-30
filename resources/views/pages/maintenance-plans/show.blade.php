@extends('layouts.app')

@section('title', $plan->name)
@section('page-title', $plan->name)
@section('page-subtitle', 'تفاصيل خطة الصيانة')

@section('content')
    <x-ui.page-header icon="shield-check" subtitle="تفاصيل خطة الصيانة" :title="$plan->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('maintenance-plans.index')">رجوع للقائمة</x-ui.btn>
            <x-ui.btn icon="pencil" size="sm" :href="route('maintenance-plans.edit', $plan)">تعديل الخطة</x-ui.btn>

            <form action="{{ route('maintenance-plans.destroy', $plan) }}" method="POST" x-data>
                @csrf
                @method('DELETE')
                <x-ui.btn icon="trash-2" size="sm" variant="danger"
                    x-on:click="$store.confirm.ask({
                        title: 'حذف خطة الصيانة',
                        message: {{ Js::from('سيتم حذف خطة «' . $plan->name . '» وبنودها. العقود المرتبطة تحتفظ بنسختها الخاصة من البنود.') }},
                        label: 'حذف',
                        onOk: () => $root.requestSubmit()
                    })">حذف</x-ui.btn>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <span class="text-[12px] text-gray-400">الحالة</span>
                    <x-ui.badge :status="$plan->status->label()" dot />
                </div>

                <x-ui.detail-row label="عدد البنود" :value="$plan->items->count() . ' بند'" />
                <x-ui.detail-row label="مدة الخطة" :value="$plan->duration_months ? $plan->duration_months . ' شهر' : '—'" />
                <x-ui.detail-row label="تاريخ الإنشاء" :value="$plan->created_at?->format('Y-m-d')" />
                <x-ui.detail-row label="آخر تعديل" :value="$plan->updated_at?->format('Y-m-d')" />

                <div class="mt-4 pt-4 border-t border-gray-50">
                    <p class="text-[12px] text-gray-400 mb-1.5">الوصف</p>
                    <p class="text-[13px] text-gray-600 leading-relaxed whitespace-pre-line">
                        {{ $plan->description ?: 'لا يوجد وصف لهذه الخطة.' }}</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-3 p-5 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="package" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بنود الخطة</h2>
                    </div>
                    <span class="text-[11px] font-bold text-gray-400">{{ $plan->items->count() }} بند</span>
                </div>

                <div class="overflow-x-auto scrollbar-hide">
                    <table class="w-full min-w-[560px]">
                        <thead class="bg-gray-50/70 border-b border-gray-100">
                            <tr>
                                <x-ui.th>المنتج</x-ui.th>
                                <x-ui.th>الدورية</x-ui.th>
                                <x-ui.th>تاريخ الاستبدال</x-ui.th>
                                <x-ui.th align="left">السعر الحالي</x-ui.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($plan->items as $item)
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="text-[13px] font-bold text-gray-800">
                                            {{ $item->product?->name ?? 'منتج محذوف' }}</p>
                                        <p class="text-[11px] text-gray-400" dir="ltr">{{ $item->product?->sku }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-[12px] font-semibold text-gray-700">
                                        كل {{ $item->interval_months }} شهر</td>
                                    <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">
                                        {{ $item->replacement_date->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-left">
                                        <span class="text-[13px] font-bold text-gray-800">
                                            {{ number_format((float) ($item->product?->price ?? 0)) }}</span>
                                        <span class="text-[10px] text-gray-400">ج.م</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($plan->items->isEmpty())
                    <x-ui.empty-state icon="package-x" message="عدّل الخطة وأضف منتجات الاستبدال."
                        title="لا توجد بنود في هذه الخطة" />
                @endif
            </div>
        </div>
    </div>
@endsection
