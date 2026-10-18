@extends('layouts.app')

@section('title', 'طلبية وارد جديدة')
@section('page-title', 'طلبية وارد جديدة')
@section('page-subtitle', 'تسجيل بضاعة دخلت المخزن بإجمالي مبلغ الطلبية')

@php
    $defaultWarehouse = old('warehouse_id', array_key_first($warehouses) ?? '');
    $itemRows = array_values(old('items', [['product_id' => '', 'quantity' => '']]));
@endphp

@section('content')
    <x-ui.page-header icon="truck" subtitle="تسجيل بضاعة دخلت المخزن بإجمالي مبلغ الطلبية" title="طلبية وارد جديدة">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.purchase-orders.index')">رجوع
                للمخزن</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('inventory.purchase-orders.store') }}" method="POST"
        x-data="purchaseOrderForm({ items: @js($itemRows) })" x-on:submit="if (! valid) $event.preventDefault()">
        @csrf

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسجيل الطلبية:</p>
                <ul class="list-disc pr-5 space-y-1">
                    @foreach ($errors->all() as $message)
                        <li class="text-[12px] text-red-500">{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
            <div class="lg:col-span-2 space-y-4 lg:space-y-5">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="truck" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بيانات الطلبية</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.field label="المخزن" name="warehouse_id" type="select" :options="$warehouses"
                            :value="$defaultWarehouse" placeholder="اختر المخزن" required />

                        <x-ui.field label="تاريخ الطلبية" name="order_date" type="date"
                            :value="old('order_date', today()->toDateString())" required />

                        <x-ui.field label="المورد" name="supplier" placeholder="اختياري" :value="old('supplier')" />

                        <x-ui.field label="إجمالي مبلغ الطلبية (ج.م)" name="total_amount" type="number" min="0"
                            step="0.01" placeholder="0" :value="old('total_amount')" required
                            hint="المبلغ المدفوع للطلبية كلها — لا يُسجَّل سعر كل قطعة" />

                        <x-ui.field col="sm:col-span-2" label="ملاحظة" name="note" placeholder="اختياري"
                            :value="old('note')" />
                    </div>
                </section>

                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <x-icon class="w-[18px] h-[18px]" name="package" />
                            </span>
                            <h2 class="text-sm font-bold text-gray-800">محتوى الطلبية</h2>
                        </div>
                        <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addItem()">إضافة منتج</x-ui.btn>
                    </div>

                    <div class="space-y-3">
                        <template x-bind:key="row.uid" x-for="(row, index) in items">
                            <div class="rounded-2xl border border-gray-100 p-3.5 grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-7">
                                    <label class="block text-[11px] font-bold text-gray-500 mb-1.5">المنتج</label>
                                    <select
                                        class="w-full h-11 px-3.5 rounded-xl bg-white border text-[13px] text-gray-800 focus:ring-2 outline-none transition"
                                        x-bind:class="duplicate(row) ? 'border-red-300 focus:ring-red-200' :
                                            'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                        x-bind:name="`items[${index}][product_id]`" x-model="row.product_id">
                                        <option value="">اختر المنتج...</option>
                                        @foreach ($products as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] font-semibold text-red-500 mt-1.5" x-cloak x-show="duplicate(row)">
                                        هذا المنتج مُضاف بالفعل في بند آخر.</p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الكمية</label>
                                    <input
                                        class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                                        min="1" placeholder="0" type="number" x-bind:name="`items[${index}][quantity]`"
                                        x-model.number="row.quantity" />
                                </div>

                                <div class="sm:col-span-2 flex sm:items-end sm:justify-end">
                                    <x-ui.icon-btn icon="trash-2" label="حذف البند" tone="danger"
                                        x-bind:disabled="items.length === 1" x-on:click="removeItem(index)" />
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <div class="lg:col-span-1">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">ملخص الطلبية</h2>

                    <div class="p-4 rounded-2xl bg-gray-50 mb-4">
                        <p class="text-[11px] text-gray-400 mb-1">إجمالي القطع</p>
                        <p class="text-2xl font-bold text-gray-800" x-text="Number(totalQuantity).toLocaleString('en')"></p>
                    </div>

                    <p class="text-[11px] font-semibold text-red-500" x-cloak x-show="! valid">
                        أكمل كل بند بمنتج غير مكرر وكمية لا تقل عن 1.
                    </p>

                    <div class="flex flex-col gap-2.5 mt-5">
                        <x-ui.btn class="w-full" icon="check" type="submit" x-bind:disabled="! valid">حفظ الطلبية</x-ui.btn>
                        <x-ui.btn class="w-full" variant="outline" :href="route('inventory.purchase-orders.index')">إلغاء</x-ui.btn>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function purchaseOrderForm(config) {
            return {
                items: (config.items || []).map((row, index) => ({ uid: index + 1, ...row })),

                addItem() {
                    const uid = this.items.length ? Math.max(...this.items.map(r => r.uid)) + 1 : 1;
                    this.items.push({ uid, product_id: '', quantity: '' });
                },

                removeItem(index) {
                    if (this.items.length === 1) return;
                    this.items.splice(index, 1);
                },

                duplicate(row) {
                    return !!row.product_id && this.items.filter(r => r.product_id === row.product_id).length > 1;
                },

                get totalQuantity() {
                    return this.items.reduce((sum, row) => sum + (Number(row.quantity) || 0), 0);
                },

                get valid() {
                    return this.items.length > 0
                        && this.items.every(row => row.product_id && !this.duplicate(row) && Number(row.quantity) >= 1);
                }
            };
        }
    </script>
@endpush
