@extends('layouts.app')

@section('title', 'شكوى جديدة')
@section('page-title', 'شكوى جديدة')
@section('page-subtitle', 'تسجيل شكوى عميل وما صُرف فيها من قطع غيار أو رسوم زيارة')

@php
    $stockSourceOptions = collect($stockSources)->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values();
    $productOptions = $products->map(fn($p) => ['id' => (string) $p->id, 'name' => $p->name, 'price' => (float) $p->price])->values();
@endphp

@section('content')
    <x-ui.page-header icon="message-square-warning" subtitle="تسجيل شكوى عميل" title="شكوى جديدة">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('complaints.index')">رجوع للسجل</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('complaints.store') }}" method="POST"
        x-data="complaintForm({
            contractsMeta: @js($contractsMeta),
            products: @js($productOptions),
            availability: @js($availability),
            employeeAvailability: @js($employeeAvailability),
            stockSources: @js($stockSourceOptions),
            old: @js(old('parts', [])),
        })"
        x-on:submit="if (! valid) $event.preventDefault()">
        @csrf

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسجيل الشكوى — راجع الحقول التالية:</p>
                <ul class="list-disc pr-5 space-y-1">
                    @foreach ($errors->all() as $message)
                        <li class="text-[12px] text-red-500">{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
            <div class="lg:col-span-2 space-y-4 lg:space-y-5">
                {{-- العقد والعميل --}}
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="file-text" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">العميل والجهاز</h2>
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
                        </div>
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <x-ui.field label="الفني" name="technician_id" type="select" :options="$technicians"
                            placeholder="بدون تحديد" x-model="technicianId" />
                        <x-ui.field label="المندوبة" name="sales_rep_id" type="select" :options="$salesReps"
                            placeholder="بدون تحديد" x-model="salesRepId" />
                    </div>
                </section>

                {{-- بيانات الشكوى --}}
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="message-square-warning" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">بيانات الشكوى</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.field label="تاريخ الشكوى" name="complaint_date" type="date"
                            :value="today()->toDateString()" required />

                        <x-ui.field label="رسوم الزيارة (ج.م)" name="visit_fee" type="number" min="0" step="0.01"
                            placeholder="0" hint="اختياري — تُحصَّل حتى لو لم تُصرف أي قطعة" x-model.number="visitFee" />

                        <x-ui.field col="sm:col-span-2" label="ملاحظات" name="note"
                            placeholder="وصف مختصر للشكوى أو ما تم (اختياري)" />
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-50">
                        <label class="block text-[12px] font-bold text-gray-600 mb-2">هل تم حل المشكلة؟</label>
                        <div class="flex gap-2">
                            <button
                                class="h-10 px-5 rounded-xl text-[12px] font-bold border transition-colors flex items-center gap-1.5"
                                type="button"
                                x-bind:class="resolved ? 'border-green-300 bg-green-50 text-green-700' : 'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                x-on:click="resolved = true">
                                <x-icon class="w-4 h-4" name="circle-check" /> نعم
                            </button>
                            <button
                                class="h-10 px-5 rounded-xl text-[12px] font-bold border transition-colors flex items-center gap-1.5"
                                type="button"
                                x-bind:class="! resolved ? 'border-red-300 bg-red-50 text-red-600' : 'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                x-on:click="resolved = false">
                                <x-icon class="w-4 h-4" name="circle-alert" /> لا
                            </button>
                        </div>
                        <input name="resolved" type="hidden" x-bind:value="resolved ? 1 : 0" />
                    </div>
                </section>

                {{-- قطع الغيار (اختياري) --}}
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                                <x-icon class="w-[18px] h-[18px]" name="package" />
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">قطع الغيار</h2>
                                <p class="text-[11px] text-gray-400">اختياري — تُخصم من المخزن أو من عهدة الفني/المندوبة</p>
                            </div>
                        </div>
                        <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addPart()">إضافة قطعة</x-ui.btn>
                    </div>

                    <p class="text-[12px] text-gray-400 py-3 text-center rounded-xl border border-dashed border-gray-200"
                        x-cloak x-show="parts.length === 0">
                        لم تُضف قطع غيار — ستُسجَّل الشكوى برسوم الزيارة فقط.
                    </p>

                    <div class="space-y-3">
                        <template x-bind:key="row.uid" x-for="(row, index) in parts">
                            <div class="rounded-2xl border border-gray-100 p-3.5">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-5">
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">القطعة</label>
                                        <div class="relative">
                                            <select
                                                class="w-full h-11 px-3.5 pl-9 rounded-xl bg-white border text-[13px] text-gray-800 appearance-none cursor-pointer focus:ring-2 outline-none transition border-gray-200 focus:ring-brand-green/50 focus:border-brand-green"
                                                x-bind:name="`parts[${index}][product_id]`" x-model="row.product_id"
                                                x-on:change="onProductChange(row)">
                                                <option value="">اختر القطعة...</option>
                                                <template x-for="p in products" x-bind:key="p.id">
                                                    <option x-bind:value="p.id" x-text="p.name"></option>
                                                </template>
                                            </select>
                                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                                fill="none" stroke="currentColor" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الكمية</label>
                                        <input
                                            class="w-full h-11 px-3.5 rounded-xl bg-white border text-[13px] text-gray-800 focus:ring-2 outline-none transition"
                                            min="1" placeholder="1" type="number"
                                            x-bind:class="row.product_id && !rowValid(row) ? 'border-red-300 focus:ring-red-200' :
                                                'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                            x-bind:name="`parts[${index}][quantity]`" x-model.number="row.quantity" />
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">السعر (ج.م)</label>
                                        <input
                                            class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                                            min="0" placeholder="0" step="0.01" type="number"
                                            x-bind:name="`parts[${index}][price]`" x-model.number="row.price" />
                                    </div>

                                    <div class="sm:col-span-2 flex sm:items-end sm:justify-end">
                                        <button
                                            class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                            title="حذف القطعة" type="button" x-on:click="removePart(index)">
                                            <x-icon class="w-[17px] h-[17px]" name="trash-2" />
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <span class="text-[11px] font-bold text-gray-500 ml-1">تُخصم من:</span>
                                    <template x-bind:key="source.value" x-for="source in stockSources">
                                        <button
                                            class="h-9 px-3.5 rounded-xl text-[11px] font-bold border transition-colors"
                                            type="button"
                                            x-bind:class="row.stock_source === source.value ? 'border-brand-green bg-brand-green/20 text-brand-dark' :
                                                'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                            x-on:click="row.stock_source = source.value">
                                            <span x-text="source.label"></span>
                                        </button>
                                    </template>
                                    <input type="hidden" x-bind:name="`parts[${index}][stock_source]`"
                                        x-bind:value="row.stock_source" />

                                    <div class="relative" x-show="row.stock_source === 'warehouse'">
                                        <select
                                            class="h-9 pr-3 pl-8 rounded-xl bg-white border border-gray-200 text-[11px] font-bold text-gray-700 appearance-none cursor-pointer focus:ring-2 focus:ring-brand-green/50 outline-none"
                                            x-bind:name="`parts[${index}][warehouse_id]`" x-model="row.warehouse_id">
                                            <option value="">اختر المخزن...</option>
                                            @foreach ($warehouses as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                            fill="none" stroke="currentColor" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg>
                                    </div>
                                </div>

                                <p class="text-[11px] text-gray-400 mt-2.5" x-cloak x-show="row.product_id">
                                    المتاح في المصدر المختار:
                                    <span class="font-bold" x-bind:class="available(row) > 0 ? 'text-green-700' : 'text-red-500'"
                                        x-text="Number(available(row)).toLocaleString('en') + ' قطعة'"></span>
                                    <span class="text-red-500 font-bold" x-cloak x-show="sourceMissing(row)"
                                        x-text="sourceMissingText(row)"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            {{-- الملخص --}}
            <div class="lg:col-span-1">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">ملخص الشكوى</h2>

                    <dl class="space-y-2.5 text-[12px]">
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500">قطع الغيار</dt>
                            <dd class="font-bold text-gray-800"><span x-text="fmt(partsTotal)"></span> ج.م</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500">رسوم الزيارة</dt>
                            <dd class="font-bold text-gray-800"><span x-text="fmt(visitFee)"></span> ج.م</dd>
                        </div>
                        <div class="flex items-center justify-between pt-2.5 border-t border-gray-100">
                            <dt class="font-bold text-gray-700">الإجمالي على العميل</dt>
                            <dd class="text-base font-bold text-gray-900"><span x-text="fmt(total)"></span> ج.م</dd>
                        </div>
                    </dl>

                    <p class="text-[12px] text-gray-500 leading-relaxed mt-4 mb-4">
                        بمجرد الحفظ، تُخصم قطع الغيار (إن وُجدت) فورًا من مصدرها. حذف الشكوى لاحقًا يعيدها.
                    </p>

                    <div class="flex flex-col gap-2.5">
                        <x-ui.btn class="w-full" icon="check" type="submit" x-bind:disabled="! valid">حفظ الشكوى</x-ui.btn>
                        <x-ui.btn class="w-full" variant="outline" :href="route('complaints.index')">إلغاء</x-ui.btn>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // نموذج الشكوى: قطع الغيار اختيارية ومتعددة، كل قطعة بمصدر خصم مستقل، والمتاح يُحسب محليًا
        function complaintForm(config) {
            let uid = 0;
            const blankPart = () => ({ uid: uid++, product_id: '', quantity: 1, price: '', stock_source: 'warehouse', warehouse_id: '' });
            const restored = (config.old || []).map(part => ({
                uid: uid++,
                product_id: String(part.product_id || ''),
                quantity: Number(part.quantity) || 1,
                price: part.price ?? '',
                stock_source: part.stock_source || 'warehouse',
                warehouse_id: String(part.warehouse_id || ''),
            }));

            return {
                contractsMeta: config.contractsMeta || {},
                products: config.products || [],
                availability: config.availability || {},
                employeeAvailability: config.employeeAvailability || {},
                stockSources: config.stockSources || [],

                contractId: @js(old('sales_contract_id', '')),
                technicianId: @js(old('technician_id', '')),
                salesRepId: @js(old('sales_rep_id', '')),
                visitFee: Number(@js(old('visit_fee', 0))) || 0,
                resolved: @js((bool) old('resolved', false)),
                parts: restored,

                get meta() {
                    return this.contractsMeta[this.contractId] || null;
                },

                onContractChange() {
                    const meta = this.meta;
                    if (!meta) return;
                    this.setSelect('technician_id', meta.installerId);
                    this.setSelect('sales_rep_id', meta.salesRepId);
                    this.parts.forEach(row => { if (!row.warehouse_id) row.warehouse_id = meta.warehouseId; });
                },

                // القوائم المخصَّصة (x-ui.dropdown) لا تُحدِّث النص الظاهر إلا حين يُغيَّر
                // العنصر الأصلي مباشرة مع إطلاق أحداثه.
                setSelect(name, value) {
                    const el = document.querySelector(`select[name="${name}"]`);
                    if (!el) return;
                    el.value = value || '';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                },

                addPart() {
                    const row = blankPart();
                    row.warehouse_id = this.meta?.warehouseId || '';
                    this.parts.push(row);
                },

                removePart(index) {
                    this.parts.splice(index, 1);
                },

                // يقترح سعر بيع المنتج المسجَّل، ويبقى قابلًا للتعديل
                onProductChange(row) {
                    const product = this.products.find(p => p.id === row.product_id);
                    if (product && (row.price === '' || row.price === null)) row.price = product.price;
                },

                available(row) {
                    if (!row.product_id) return 0;
                    if (row.stock_source === 'warehouse') {
                        return Number((this.availability[row.warehouse_id] || {})[row.product_id] || 0);
                    }
                    const employeeId = row.stock_source === 'installer' ? this.technicianId : this.salesRepId;
                    return Number((this.employeeAvailability[employeeId] || {})[row.product_id] || 0);
                },

                sourceMissing(row) {
                    if (row.stock_source === 'warehouse') return !row.warehouse_id;
                    if (row.stock_source === 'installer') return !this.technicianId;
                    return !this.salesRepId;
                },

                sourceMissingText(row) {
                    if (row.stock_source === 'warehouse') return '— اختر المخزن';
                    if (row.stock_source === 'installer') return '— اختر الفني أولًا';
                    return '— اختر المندوبة أولًا';
                },

                // الكميات المطلوبة من نفس المنتج والمصدر تُجمَّع قبل مقارنتها بالمتاح
                requestedLike(row) {
                    return this.parts
                        .filter(other => other.product_id === row.product_id && other.stock_source === row.stock_source &&
                            (row.stock_source !== 'warehouse' || other.warehouse_id === row.warehouse_id))
                        .reduce((sum, other) => sum + (Number(other.quantity) || 0), 0);
                },

                rowValid(row) {
                    if (!row.product_id || this.sourceMissing(row)) return false;
                    const quantity = Number(row.quantity) || 0;
                    const price = row.price === '' || row.price === null ? NaN : Number(row.price);
                    return quantity >= 1 && !isNaN(price) && price >= 0 && this.requestedLike(row) <= this.available(row);
                },

                get partsTotal() {
                    return this.parts.reduce((sum, row) => sum + (Number(row.quantity) || 0) * (Number(row.price) || 0), 0);
                },

                get total() {
                    return this.partsTotal + (Number(this.visitFee) || 0);
                },

                fmt(value) {
                    return Number(value || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                get valid() {
                    if (!this.contractId) return false;
                    if (Number(this.visitFee) < 0) return false;
                    return this.parts.every(row => this.rowValid(row));
                }
            };
        }
    </script>
@endpush
