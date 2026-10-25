@extends('layouts.app')

@section('title', 'تسليم عهدة لموظف')
@section('page-title', 'تسليم عهدة لموظف')
@section('page-subtitle', 'نقل منتج واحد أو أكثر من المخزن الرئيسي إلى عهدة الموظف')

@php
    $defaultWarehouse = old('warehouse_id', array_key_first($warehouses) ?? '');
    $itemRows = array_values(old('items', [['product_id' => '', 'quantity' => '']]));
@endphp

@section('content')
    <x-ui.page-header icon="plus" subtitle="نقل منتج واحد أو أكثر من المخزن الرئيسي إلى عهدة الموظف" title="تسليم عهدة لموظف">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.employee-stocks.index')">رجوع
                للعهد</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('inventory.employee-stocks.store') }}" method="POST"
        x-data="employeeHandoverForm({ availability: @js($availability ?? []), warehouseId: @js((string) $defaultWarehouse), items: @js($itemRows) })"
        x-on:submit="if (! valid) $event.preventDefault()">
        @csrf

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسليم العهدة:</p>
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
                            <x-icon class="w-[18px] h-[18px]" name="user-cog" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بيانات التسليم</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.field col="sm:col-span-2" label="الموظف" name="user_id" type="select" :options="$employees"
                            :value="old('user_id')" placeholder="اختر الموظف (مندوب مبيعات أو فني تركيب)" required />

                        <x-ui.field label="المخزن" name="warehouse_id" type="select" :options="$warehouses"
                            :value="$defaultWarehouse" placeholder="اختر المخزن" required x-model="warehouseId" />

                        <x-ui.field label="التاريخ" name="moved_at" type="date"
                            :value="old('moved_at', today()->toDateString())" required />

                        <x-ui.field col="sm:col-span-2" label="ملاحظة" name="note" placeholder="اختياري"
                            :value="old('note')" />
                    </div>
                </section>

                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <x-icon class="w-[18px] h-[18px]" name="package" />
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">منتجات العهدة</h2>
                                <p class="text-[11px] text-gray-400">يمكن تسليم أكثر من منتج في نفس العملية</p>
                            </div>
                        </div>
                        <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addItem()">إضافة منتج</x-ui.btn>
                    </div>

                    <div class="space-y-3">
                        <template x-bind:key="row.uid" x-for="(row, index) in items">
                            <div class="rounded-2xl border border-gray-100 p-3.5">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-7">
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">المنتج</label>
                                        <div class="relative">
                                            <select
                                                class="w-full h-11 px-3.5 pl-9 rounded-xl bg-white border text-[13px] text-gray-800 appearance-none cursor-pointer focus:ring-2 outline-none transition"
                                                x-bind:class="duplicate(row) ? 'border-red-300 focus:ring-red-200' :
                                                    'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                                x-bind:name="`items[${index}][product_id]`" x-model="row.product_id">
                                                <option value="">اختر المنتج...</option>
                                                @foreach ($products as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                                fill="none" stroke="currentColor" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                        <p class="text-[11px] font-semibold text-red-500 mt-1.5" x-cloak x-show="duplicate(row)">
                                            هذا المنتج مُضاف بالفعل في بند آخر.</p>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الكمية</label>
                                        <input
                                            class="w-full h-11 px-3.5 rounded-xl bg-white border text-[13px] text-gray-800 focus:ring-2 outline-none transition"
                                            min="1" placeholder="0" type="number"
                                            x-bind:class="row.quantity && !rowValid(row) ? 'border-red-300 focus:ring-red-200' :
                                                'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                            x-bind:name="`items[${index}][quantity]`" x-model.number="row.quantity" />
                                    </div>

                                    <div class="sm:col-span-2 flex sm:items-end sm:justify-end">
                                        <button
                                            class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                                            type="button" x-bind:disabled="items.length === 1" x-on:click="removeItem(index)">
                                            <svg class="w-[17px] h-[17px]" fill="none" stroke="currentColor"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="M19 6l-1 14H6L5 6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <p class="text-[11px] text-gray-400 mt-2.5" x-cloak x-show="row.product_id">
                                    المتاح في المخزن المختار:
                                    <span class="font-bold" x-bind:class="available(row) > 0 ? 'text-green-700' : 'text-red-500'"
                                        x-text="Number(available(row)).toLocaleString('en') + ' قطعة'"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <div class="lg:col-span-1">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">ملخص العهدة</h2>

                    <div class="p-4 rounded-2xl bg-gray-50 mb-4">
                        <p class="text-[11px] text-gray-400 mb-1">عدد المنتجات</p>
                        <p class="text-2xl font-bold text-gray-800" x-text="items.length"></p>
                    </div>

                    <div class="p-4 rounded-2xl border"
                        x-bind:class="valid ? 'border-brand-green bg-brand-green/10' : 'border-red-200 bg-red-50'">
                        <p class="text-[11px] text-gray-500 mb-1">إجمالي القطع المطلوب تسليمها</p>
                        <p class="text-3xl font-bold" x-bind:class="valid ? 'text-brand-dark' : 'text-red-500'"
                            x-text="Number(totalQuantity).toLocaleString('en')"></p>
                        <p class="text-[11px] font-semibold text-red-500 mt-2" x-cloak x-show="! valid">
                            أكمل بيانات كل بند بكمية متاحة ومنتج غير مكرر.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2.5 mt-5">
                        <x-ui.btn class="w-full" icon="check" type="submit" x-bind:disabled="! valid">تسليم العهدة</x-ui.btn>
                        <x-ui.btn class="w-full" variant="outline" :href="route('inventory.employee-stocks.index')">إلغاء</x-ui.btn>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // نموذج تسليم العهدة: يسمح بأكثر من منتج في نفس العملية، ويحسب المتاح والإجمالي محليًا
        function employeeHandoverForm(config) {
            return {
                availability: config.availability || {},
                warehouseId: config.warehouseId || '',
                items: (config.items || []).map((row, index) => ({ uid: index + 1, ...row })),

                addItem() {
                    const uid = this.items.length ? Math.max(...this.items.map(r => r.uid)) + 1 : 1;
                    this.items.push({ uid, product_id: '', quantity: '' });
                },

                removeItem(index) {
                    if (this.items.length === 1) return;
                    this.items.splice(index, 1);
                },

                available(row) {
                    const warehouse = this.availability[this.warehouseId] || {};
                    return Number(warehouse[row.product_id] || 0);
                },

                duplicate(row) {
                    if (!row.product_id) return false;
                    return this.items.filter(r => r.product_id === row.product_id).length > 1;
                },

                rowValid(row) {
                    if (!row.product_id || this.duplicate(row)) return false;
                    const quantity = Number(row.quantity) || 0;

                    return quantity >= 1 && quantity <= this.available(row);
                },

                get totalQuantity() {
                    return this.items.reduce((sum, row) => sum + (Number(row.quantity) || 0), 0);
                },

                get valid() {
                    if (!this.warehouseId || !this.items.length) return false;

                    return this.items.every(row => this.rowValid(row));
                }
            };
        }
    </script>
@endpush
