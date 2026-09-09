@extends('layouts.app')

@section('title', 'زيارة صيانة جديدة')
@section('page-title', 'زيارة صيانة جديدة')
@section('page-subtitle', 'تغيير شمعة وخصمها تلقائيًا من مصدرها')

@php
    $stockSourceOptions = collect($stockSources)->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values();
@endphp

@section('content')
    <x-ui.page-header icon="wrench" subtitle="تغيير شمعة وخصمها تلقائيًا من مصدرها" title="زيارة صيانة جديدة">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('maintenance-visits.index')">رجوع
                للسجل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('maintenance-visits.store') }}" method="POST"
        x-data="maintenanceVisitForm({
            contractsMeta: @js($contractsMeta),
            availability: @js($availability),
            employeeAvailability: @js($employeeAvailability),
            stockSources: @js($stockSourceOptions),
        })"
        x-on:submit="if (! valid) $event.preventDefault()">
        @csrf

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسجيل الصيانة — راجع الحقول التالية:</p>
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
                            <x-icon class="w-[18px] h-[18px]" name="file-text" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">العقد والعميل</h2>
                    </div>

                    <x-ui.field label="العقد" name="sales_contract_id" type="select" :options="$contractOptions"
                        placeholder="اختر العقد بكود العميل أو اسمه" required x-model="contractId"
                        x-on:change="onContractChange()" />

                    <template x-if="meta">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 mt-4 pt-4 border-t border-gray-50">
                            <span class="text-[12px] text-gray-500">
                                العميل: <span class="font-bold text-gray-700" x-text="meta.customer"></span>
                            </span>
                            <template x-if="meta.device">
                                <span class="text-[12px] text-gray-500">
                                    نوع الجهاز: <span class="font-bold text-gray-700" x-text="meta.device"></span>
                                </span>
                            </template>
                            <template x-if="meta.installationDate">
                                <span class="text-[12px] text-gray-500" dir="ltr">
                                    تاريخ التركيب: <span class="font-bold text-gray-700" x-text="meta.installationDate"></span>
                                </span>
                            </template>
                        </div>
                    </template>
                </section>

                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="wrench" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بيانات الصيانة</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.field label="نوع الشمعة" name="product_id" type="select" :options="$products"
                            placeholder="اختر المنتج" required x-model="productId" />

                        <x-ui.field label="الكمية" name="quantity" type="number" min="1" value="1" required
                            x-model.number="quantity" />

                        <x-ui.field label="سعر البيع (ج.م)" name="price" type="number" min="0" step="0.01"
                            placeholder="0" required />

                        <x-ui.field label="حالة الضمان" name="warranty_status" type="select" :options="$warrantyStatuses"
                            placeholder="اختر الحالة" required />

                        <x-ui.field label="تاريخ تنفيذ الصيانة" name="performed_at" type="date"
                            :value="today()->toDateString()" required />

                        <x-ui.field label="ملاحظة" name="note" placeholder="اختياري" />
                    </div>
                </section>

                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="users" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">مصدر الشمعة</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <x-ui.field label="المخزن" name="warehouse_id" type="select" :options="$warehouses"
                            placeholder="بدون تحديد" x-model="warehouseId" />
                        <x-ui.field label="الفني" name="technician_id" type="select" :options="$technicians"
                            placeholder="بدون تحديد" x-model="technicianId" />
                        <x-ui.field label="المندوبة" name="sales_rep_id" type="select" :options="$salesReps"
                            placeholder="بدون تحديد" x-model="salesRepId" />
                    </div>

                    <label class="block text-[12px] font-bold text-gray-600 mb-2">تُخصم الشمعة من</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-bind:key="source.value" x-for="source in stockSources">
                            <button
                                class="h-10 px-4 rounded-xl text-[12px] font-bold border transition-colors flex items-center gap-1.5"
                                type="button"
                                x-bind:class="stockSource === source.value ? 'border-brand-green bg-brand-green/20 text-brand-dark' :
                                    'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                x-on:click="stockSource = source.value">
                                <span x-text="source.label"></span>
                            </button>
                        </template>
                    </div>
                    <input name="stock_source" type="hidden" x-bind:value="stockSource" />

                    <p class="text-[11px] text-gray-400 mt-2.5" x-cloak x-show="productId">
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
                        بمجرد الحفظ، تُخصم الكمية المحدَّدة فورًا من المصدر المختار (المخزن أو عهدة الفني أو
                        المندوبة)، وتُسجَّل الزيارة على العقد المختار.
                    </p>

                    <div class="flex flex-col gap-2.5">
                        <x-ui.btn class="w-full" icon="check" type="submit" x-bind:disabled="! valid">حفظ الصيانة</x-ui.btn>
                        <x-ui.btn class="w-full" variant="outline" :href="route('maintenance-visits.index')">إلغاء</x-ui.btn>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // نموذج زيارة الصيانة: يعرض بيانات العقد المختار، ويحسب المتاح في مصدر الخصم محليًا
        function maintenanceVisitForm(config) {
            return {
                contractsMeta: config.contractsMeta || {},
                availability: config.availability || {},
                employeeAvailability: config.employeeAvailability || {},
                stockSources: config.stockSources || [],

                contractId: '',
                productId: '',
                quantity: 1,
                stockSource: 'warehouse',
                warehouseId: '',
                technicianId: '',
                salesRepId: '',

                get meta() {
                    return this.contractsMeta[this.contractId] || null;
                },

                onContractChange() {
                    const meta = this.meta;
                    if (!meta) return;
                    this.setSelect('warehouse_id', meta.warehouseId);
                    this.setSelect('technician_id', meta.installerId);
                    this.setSelect('sales_rep_id', meta.salesRepId);
                },

                // القوائم المخصَّصة (x-ui.dropdown) لا تُحدِّث النص الظاهر إلا حين يُغيَّر
                // العنصر الأصلي مباشرة مع إطلاق أحداثه — تعيين متغيّر Alpine من مكوّن آخر
                // لا يكفي وحده لتحديث واجهتها.
                setSelect(name, value) {
                    const el = document.querySelector(`select[name="${name}"]`);
                    if (!el) return;
                    el.value = value || '';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                },

                get available() {
                    if (this.stockSource === 'warehouse') {
                        return Number((this.availability[this.warehouseId] || {})[this.productId] || 0);
                    }
                    if (this.stockSource === 'installer') {
                        return Number((this.employeeAvailability[this.technicianId] || {})[this.productId] || 0);
                    }
                    if (this.stockSource === 'sales_rep') {
                        return Number((this.employeeAvailability[this.salesRepId] || {})[this.productId] || 0);
                    }
                    return 0;
                },

                get valid() {
                    if (!this.contractId || !this.productId) return false;
                    if (this.stockSource === 'warehouse' && !this.warehouseId) return false;
                    if (this.stockSource === 'installer' && !this.technicianId) return false;
                    if (this.stockSource === 'sales_rep' && !this.salesRepId) return false;

                    const quantity = Number(this.quantity) || 0;

                    return quantity >= 1 && quantity <= this.available;
                }
            };
        }
    </script>
@endpush
