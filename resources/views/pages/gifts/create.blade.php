@extends('layouts.app')

@section('title', 'هدية جديدة')
@section('page-title', 'هدية جديدة')
@section('page-subtitle', 'قطعة غيار تُهدى لعميل وتُخصم من مصدرها')

@php
    $stockSourceOptions = collect($stockSources)->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values();
@endphp

@section('content')
    <x-ui.page-header icon="gift" subtitle="قطعة غيار تُهدى لعميل" title="هدية جديدة">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('gifts.index')">رجوع للسجل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('gifts.store') }}" method="POST"
        x-data="giftForm({
            availability: @js($availability),
            employeeAvailability: @js($employeeAvailability),
            stockSources: @js($stockSourceOptions),
        })"
        x-on:submit="if (! valid) $event.preventDefault()">
        @csrf

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسجيل الهدية — راجع الحقول التالية:</p>
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
                            <x-icon class="w-[18px] h-[18px]" name="gift" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بيانات الهدية</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.field label="اسم العميل" name="customer_id" type="select" :options="$customers"
                            placeholder="اختر العميل" required x-model="customerId" />

                        <x-ui.field label="التاريخ" name="gift_date" type="date" :value="today()->toDateString()" required />

                        <x-ui.field label="اسم الفني" name="technician_id" type="select" :options="$technicians"
                            placeholder="بدون تحديد" x-model="technicianId" />

                        <x-ui.field label="قطعة الغيار" name="product_id" type="select" :options="$products"
                            placeholder="اختر القطعة" required x-model="productId" />

                        <x-ui.field label="الكمية" name="quantity" type="number" min="1" value="1" required
                            x-model.number="quantity" />

                        <x-ui.field label="ملاحظة" name="note" placeholder="اختياري" />
                    </div>
                </section>

                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="package" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">تُخصم القطعة من</h2>
                    </div>

                    <div class="flex flex-wrap gap-2 mb-4">
                        <template x-bind:key="source.value" x-for="source in stockSources">
                            <button
                                class="h-10 px-4 rounded-xl text-[12px] font-bold border transition-colors"
                                type="button"
                                x-bind:class="stockSource === source.value ? 'border-brand-green bg-brand-green/20 text-brand-dark' :
                                    'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                x-on:click="stockSource = source.value">
                                <span x-text="source.label"></span>
                            </button>
                        </template>
                    </div>
                    <input name="stock_source" type="hidden" x-bind:value="stockSource" />

                    <div x-show="stockSource === 'warehouse'">
                        <x-ui.field label="المخزن" name="warehouse_id" type="select" :options="$warehouses"
                            placeholder="اختر المخزن" x-model="warehouseId" />
                    </div>
                    <p class="text-[12px] text-gray-500" x-cloak x-show="stockSource === 'installer'">
                        تُخصم من عهدة الفني المختار أعلاه.
                        <span class="text-red-500 font-bold" x-show="! technicianId">— اختر الفني أولًا.</span>
                    </p>

                    <p class="text-[11px] text-gray-400 mt-3" x-cloak x-show="productId">
                        المتاح في المصدر المختار:
                        <span class="font-bold" x-bind:class="available > 0 ? 'text-green-700' : 'text-red-500'"
                            x-text="Number(available).toLocaleString('en') + ' قطعة'"></span>
                    </p>
                </section>
            </div>

            <div class="lg:col-span-1">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">قبل الحفظ</h2>
                    <p class="text-[12px] text-gray-500 leading-relaxed mb-4">
                        الهدية مجانية للعميل، لكن القطعة تُخصم فورًا من المخزن أو من عهدة الفني حتى يبقى المخزون صحيحًا.
                        حذف الهدية لاحقًا يعيدها.
                    </p>

                    <div class="flex flex-col gap-2.5">
                        <x-ui.btn class="w-full" icon="check" type="submit" x-bind:disabled="! valid">حفظ الهدية</x-ui.btn>
                        <x-ui.btn class="w-full" variant="outline" :href="route('gifts.index')">إلغاء</x-ui.btn>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function giftForm(config) {
            return {
                availability: config.availability || {},
                employeeAvailability: config.employeeAvailability || {},
                stockSources: config.stockSources || [],

                customerId: @js(old('customer_id', '')),
                technicianId: @js(old('technician_id', '')),
                productId: @js(old('product_id', '')),
                quantity: Number(@js(old('quantity', 1))) || 1,
                stockSource: @js(old('stock_source', 'warehouse')),
                warehouseId: @js(old('warehouse_id', '')),

                get available() {
                    if (!this.productId) return 0;
                    if (this.stockSource === 'warehouse') {
                        return Number((this.availability[this.warehouseId] || {})[this.productId] || 0);
                    }
                    return Number((this.employeeAvailability[this.technicianId] || {})[this.productId] || 0);
                },

                get valid() {
                    if (!this.customerId || !this.productId) return false;
                    if (this.stockSource === 'warehouse' && !this.warehouseId) return false;
                    if (this.stockSource === 'installer' && !this.technicianId) return false;
                    const quantity = Number(this.quantity) || 0;
                    return quantity >= 1 && quantity <= this.available;
                }
            };
        }
    </script>
@endpush
