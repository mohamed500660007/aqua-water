@extends('layouts.app')

@section('title', $contract->code)
@section('page-title', $contract->code)
@section('page-subtitle', 'تفاصيل عقد البيع')

@section('content')
    <x-ui.page-header icon="file-text" :subtitle="$contract->customer?->name" :title="'عقد ' . $contract->code">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('contracts.index')">رجوع للقائمة</x-ui.btn>
            <x-ui.btn icon="pencil" size="sm" :href="route('contracts.edit', $contract)">تعديل العقد</x-ui.btn>

            <form action="{{ route('contracts.destroy', $contract) }}" method="POST" x-data>
                @csrf
                @method('DELETE')
                <x-ui.btn icon="trash-2" size="sm" variant="danger"
                    x-on:click="$store.confirm.ask({
                        title: 'حذف العقد',
                        message: {{ Js::from('سيتم حذف العقد ' . $contract->code . ' وإعادة كمياته إلى المخزن.') }},
                        label: 'حذف',
                        onOk: () => $root.requestSubmit()
                    })">حذف</x-ui.btn>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
        <div class="lg:col-span-2 space-y-4 lg:space-y-5">
            {{-- بيانات العقد --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <x-ui.avatar :name="$contract->customer?->name ?? ''" size="w-12 h-12" />
                        <div class="min-w-0">
                            <a class="text-sm font-bold text-gray-800 truncate block"
                                href="{{ route('customers.show', $contract->customer_id) }}" wire:navigate>
                                {{ $contract->customer?->name }}</a>
                            <p class="text-[12px] text-gray-400" dir="ltr">{{ $contract->customer?->code }}</p>
                        </div>
                    </div>
                    <x-ui.badge :tone="$contract->type->tone()" dot>{{ $contract->type->label() }}</x-ui.badge>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                    <x-ui.detail-row label="تاريخ العقد" :value="$contract->contract_date->format('Y-m-d')" />
                    <x-ui.detail-row label="تاريخ التركيب" :value="$contract->installation_date?->format('Y-m-d') ?? '—'" />
                    <x-ui.detail-row label="تاريخ الصيانة" :value="$contract->maintenance_date?->format('Y-m-d') ?? '—'" />
                    <x-ui.detail-row label="تاريخ التحصيل" :value="$contract->collection_date?->format('Y-m-d') ?? '—'" />
                    <x-ui.detail-row label="المخزن" :value="$contract->warehouse?->name ?? '—'" />
                    <x-ui.detail-row label="مندوب المبيعات" :value="$contract->salesRep?->name ?? 'غير مُعيَّن'" />
                    <x-ui.detail-row label="فني التركيب" :value="$contract->installer?->name ?? 'غير مُعيَّن'" />
                    <x-ui.detail-row label="تُصرف البضاعة من" :value="$contract->stock_source->label()" />
                    <x-ui.detail-row label="خطة الصيانة" :value="$contract->maintenancePlan?->name ?? 'بدون خطة'" />
                    <x-ui.detail-row label="تاريخ الإنشاء" :value="$contract->created_at?->format('Y-m-d')" />
                </div>

                @if ($contract->notes)
                    <div class="mt-4 pt-4 border-t border-gray-50">
                        <p class="text-[12px] text-gray-400 mb-1.5">ملاحظات</p>
                        <p class="text-[13px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $contract->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- بنود العقد --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-3 p-5 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="package" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بنود العقد</h2>
                    </div>
                    <span class="text-[11px] font-bold text-gray-400">{{ $contract->items->count() }} بند</span>
                </div>

                <div class="overflow-x-auto scrollbar-hide">
                    <table class="w-full min-w-[560px]">
                        <thead class="bg-gray-50/70 border-b border-gray-100">
                            <tr>
                                <x-ui.th>المنتج</x-ui.th>
                                <x-ui.th>سعر الوحدة</x-ui.th>
                                <x-ui.th>الكمية</x-ui.th>
                                <x-ui.th align="left">الإجمالي</x-ui.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($contract->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-[13px] font-bold text-gray-800">{{ $item->product_name }}</td>
                                    <td class="px-4 py-3 text-[12px] text-gray-600">
                                        {{ number_format((float) $item->unit_price) }} ج.م</td>
                                    <td class="px-4 py-3 text-[13px] font-bold text-gray-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-left text-[13px] font-bold text-gray-800">
                                        {{ number_format((float) $item->total) }}
                                        <span class="text-[10px] text-gray-400">ج.م</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- جدول الأقساط --}}
            @if ($contract->installments->isNotEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 p-5 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <x-icon class="w-[18px] h-[18px]" name="calendar" />
                            </span>
                            <h2 class="text-sm font-bold text-gray-800">جدول الأقساط</h2>
                        </div>
                        <span class="text-[11px] font-bold text-gray-400">
                            {{ $contract->installments->count() }} قسط كل
                            {{ $contract->installment_period_months }} شهر</span>
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach ($contract->installments as $installment)
                            <div class="flex items-center gap-3 px-5 py-3">
                                <span
                                    class="w-8 h-8 shrink-0 rounded-xl bg-gray-50 text-gray-500 flex items-center justify-center text-[12px] font-bold">
                                    {{ $installment->number }}</span>
                                <span class="flex-1 text-[12px] text-gray-600" dir="ltr">
                                    {{ $installment->due_date->format('Y-m-d') }}</span>
                                <span class="text-[13px] font-bold text-gray-800">
                                    {{ number_format((float) $installment->amount, 2) }}
                                    <span class="text-[10px] text-gray-400">ج.م</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- نسخة بنود خطة الصيانة --}}
            @if ($contract->maintenanceItems->isNotEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 p-5 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                                <x-icon class="w-[18px] h-[18px]" name="shield-check" />
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">بنود خطة الصيانة</h2>
                                <p class="text-[11px] text-gray-400">نسخة محفوظة وقت التعاقد</p>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach ($contract->maintenanceItems as $item)
                            <div class="flex items-center gap-3 px-5 py-3">
                                <span class="flex-1 text-[13px] font-bold text-gray-800">{{ $item->product_name }}</span>
                                <span class="text-[11px] text-gray-400">كل {{ $item->interval_months }} شهر</span>
                                <span class="text-[12px] text-gray-600" dir="ltr">
                                    {{ $item->replacement_date->format('Y-m-d') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- الملخص المالي --}}
        <div class="lg:col-span-1 space-y-4 lg:space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-bold text-gray-800 mb-4">الملخص المالي</h2>

                <x-ui.detail-row label="السعر الأصلي" :value="number_format((float) $contract->subtotal) . ' ج.م'" />
                <x-ui.detail-row label="الخصم" :value="number_format((float) $contract->discount) . ' ج.م'" />
                <x-ui.detail-row label="السعر بعد الخصم" :value="number_format((float) $contract->total) . ' ج.م'" />
                <x-ui.detail-row label="المدفوع" :value="number_format((float) $contract->paid) . ' ج.م'" />

                <div class="flex items-center justify-between gap-3 pt-3 mt-2 border-t border-gray-100">
                    <span class="text-[12px] text-gray-400">المتبقي</span>
                    <span class="text-lg font-bold {{ (float) $contract->remaining > 0 ? 'text-amber-600' : 'text-green-700' }}">
                        {{ number_format((float) $contract->remaining) }}
                        <span class="text-[10px] text-gray-400">ج.م</span>
                    </span>
                </div>
            </div>

            @if ($contract->isInstallment())
                <div class="bg-brand-green/20 border border-brand-green rounded-2xl p-5 text-center">
                    <p class="text-[11px] font-bold text-gray-600 mb-1">
                        {{ $contract->installment_period_months === 1 ? 'المبلغ المستحق شهريًا' : 'المبلغ المستحق كل ' . $contract->installment_period_months . ' أشهر' }}
                    </p>
                    <p class="text-3xl font-bold text-brand-dark leading-none">
                        {{ number_format((float) $contract->installment_amount, 2) }}
                        <span class="text-xs font-semibold text-gray-500">ج.م</span>
                    </p>
                    <p class="text-[11px] text-gray-500 mt-2">
                        {{ $contract->installments_count }} قسط — أول استحقاق
                        <span dir="ltr">{{ $contract->first_due_date?->format('Y-m-d') }}</span>
                    </p>
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-bold text-gray-800 mb-3">أثر العقد على المخزون</h2>

                <p class="text-[12px] text-gray-500 leading-relaxed">
                    صُرفت بنود العقد من «{{ $contract->warehouse?->name }}» عبر حركات مخزون مرتبطة بالعقد،
                    وأي تعديل على الكميات يُسجَّل كحركة جديدة.
                </p>
            </div>
        </div>
    </div>
@endsection
