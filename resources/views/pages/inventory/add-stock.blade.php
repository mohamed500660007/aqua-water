@extends('layouts.app')

@section('title', 'إضافة إلى المخزن')
@section('page-title', 'إضافة إلى المخزن')
@section('page-subtitle', 'وارد بضاعة جديدة أو تسوية جرد فعلي')

@php
    $defaultWarehouse = old('warehouse_id', array_key_first($warehouses) ?? '');
    $defaultProduct = old('product_id', $selectedProductId ? (string) $selectedProductId : '');
@endphp

@section('content')
    <x-ui.page-header icon="package-plus" subtitle="وارد بضاعة جديدة أو تسوية جرد فعلي" title="إضافة إلى المخزن">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('products.index')">رجوع للمنتجات</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('inventory.stock.store') }}" method="POST"
        x-data="addStockForm({ availability: @js($availability ?? []) })">
        @csrf

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسجيل الحركة:</p>
                <ul class="list-disc pr-5 space-y-1">
                    @foreach ($errors->all() as $message)
                        <li class="text-[12px] text-red-500">{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
            <div class="lg:col-span-2">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="package-plus" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بيانات الحركة</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.field label="المنتج" name="product_id" type="select" :options="$products"
                            :value="$defaultProduct" placeholder="اختر المنتج" required x-model="productId" />

                        <x-ui.field label="المخزن" name="warehouse_id" type="select" :options="$warehouses"
                            :value="$defaultWarehouse" placeholder="اختر المخزن" required x-model="warehouseId" />

                        <x-ui.field label="نوع الحركة" name="type" type="select" :options="$types"
                            :value="old('type', 'in')" placeholder="اختر النوع" required x-model="type" />

                        <x-ui.field label="الكمية" name="quantity" type="number" min="0" placeholder="0"
                            :value="old('quantity')" required x-model.number="quantity" />

                        <x-ui.field label="التاريخ" name="moved_at" type="date"
                            :value="old('moved_at', today()->toDateString())" required />

                        <x-ui.field col="sm:col-span-2" label="ملاحظة" name="note" placeholder="اختياري"
                            :value="old('note')" />
                    </div>
                </section>
            </div>

            <div class="lg:col-span-1">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">أثر الحركة على الرصيد</h2>

                    <div class="p-4 rounded-2xl bg-gray-50 mb-4">
                        <p class="text-[11px] text-gray-400 mb-1">الرصيد الحالي في المخزن</p>
                        <p class="text-2xl font-bold text-gray-800" x-text="Number(available).toLocaleString('en')"></p>
                    </div>

                    <div class="p-4 rounded-2xl border"
                        x-bind:class="valid ? 'border-brand-green bg-brand-green/10' : 'border-red-200 bg-red-50'">
                        <p class="text-[11px] text-gray-500 mb-1">الرصيد بعد الحركة</p>
                        <p class="text-3xl font-bold" x-bind:class="valid ? 'text-brand-dark' : 'text-red-500'"
                            x-text="Number(after).toLocaleString('en')"></p>
                        <p class="text-[11px] font-semibold text-red-500 mt-2" x-cloak x-show="! valid">
                            الكمية المطلوب صرفها أكبر من المتاح في المخزن.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2.5 mt-5">
                        <x-ui.btn class="w-full" icon="check" type="submit" x-bind:disabled="! valid">حفظ الحركة</x-ui.btn>
                        <x-ui.btn class="w-full" variant="outline" :href="route('products.index')">إلغاء</x-ui.btn>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // نموذج إضافة/تسوية المخزون: يحسب الرصيد المتوقع بعد الحركة محليًا حسب نوعها
        function addStockForm(config) {
            return {
                availability: config.availability || {},
                productId: @js((string) $defaultProduct),
                warehouseId: @js((string) $defaultWarehouse),
                type: @js(old('type', 'in')),
                quantity: Number(@js(old('quantity', 0))) || 0,

                get available() {
                    const warehouse = this.availability[this.warehouseId] || {};
                    return Number(warehouse[this.productId] || 0);
                },

                get after() {
                    const quantity = Number(this.quantity) || 0;

                    if (this.type === 'out') return this.available - quantity;
                    if (this.type === 'adjustment') return quantity;

                    return this.available + quantity;
                },

                get valid() {
                    if (!this.productId || !this.warehouseId) return false;
                    if (this.type === 'adjustment') return (Number(this.quantity) || 0) >= 0;

                    return (Number(this.quantity) || 0) >= 1 && this.after >= 0;
                }
            };
        }
    </script>
@endpush
