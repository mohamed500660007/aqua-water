@php
    $plan = $plan ?? null;

    $storedItems = $plan
        ? $plan->items
            ->map(fn($item) => [
                'product_id' => (string) $item->product_id,
                'replacement_date' => $item->replacement_date->format('Y-m-d'),
            ])
            ->all()
        : [];

    $itemRows = array_values(old('items', $storedItems));
    $productCatalog = $products->map(fn($product) => [
        'id' => (string) $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'price' => (float) $product->price,
        'available' => $product->status->label(),
    ]);
@endphp

<form action="{{ $action }}" method="POST"
    x-data="maintenancePlanForm({ items: @js($itemRows), products: @js($productCatalog) })"
    x-on:submit="if (! validateAll()) $event.preventDefault()">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
            <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر حفظ الخطة — راجع الحقول التالية:</p>
            <ul class="list-disc pr-5 space-y-1">
                @foreach ($errors->all() as $message)
                    <li class="text-[12px] text-red-500">{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-4 lg:space-y-5">
        {{-- بيانات الخطة --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="shield-check" />
                </span>
                <h2 class="text-sm font-bold text-gray-800">بيانات الخطة</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.field col="sm:col-span-2" label="اسم الخطة" name="name" placeholder="مثال: باقة صيانة سنوية شاملة"
                    :value="$plan?->name" client-error="errors.name" required x-on:blur="check($el)" x-on:input="check($el)" />

                <x-ui.field label="مدة الخطة (بالأشهر)" name="duration_months" type="number" placeholder="12"
                    :value="$plan?->duration_months" client-error="errors.duration_months" min="1" max="120"
                    hint="اختيارية — تُترك فارغة للخطط المفتوحة" x-on:blur="check($el)" x-on:input="check($el)" />

                <x-ui.field label="الحالة" name="status" type="select" :options="$statuses"
                    :value="$plan?->status?->value ?? $defaultStatus" placeholder="اختر الحالة" required />

                <x-ui.field col="sm:col-span-2" label="وصف الخطة" name="description" type="textarea" rows="3"
                    placeholder="ما الذي تغطيه الخطة..." :value="$plan?->description" />
            </div>
        </section>

        {{-- بنود الخطة --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="package" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">بنود الخطة</h2>
                        <p class="text-[11px] text-gray-400">المنتج + تاريخ الاستبدال</p>
                    </div>
                </div>
                <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addItem()">إضافة بند</x-ui.btn>
            </div>

            <p class="text-[12px] font-semibold text-red-500 mb-3" x-cloak x-show="errors.items" x-text="errors.items"></p>

            <div class="space-y-3">
                <template x-bind:key="row.uid" x-for="(row, index) in items">
                    <div class="rounded-2xl border border-gray-100 p-3.5">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-7">
                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">المنتج</label>
                                <div class="relative">
                                    <select
                                        class="w-full h-11 px-3.5 pl-9 rounded-xl bg-white border text-[13px] text-gray-800 appearance-none cursor-pointer focus:ring-2 outline-none transition"
                                        x-bind:class="row.errors.product_id ? 'border-red-300 focus:ring-red-200' :
                                            'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                        x-bind:name="`items[${index}][product_id]`" x-model="row.product_id"
                                        x-on:change="validateRow(row)">
                                        <option value="">اختر المنتج...</option>
                                        <template x-bind:key="product.id" x-for="product in products">
                                            <option x-bind:value="product.id" x-text="product.name"></option>
                                        </template>
                                    </select>
                                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                        fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>
                            </div>


                            <div class="sm:col-span-4">
                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ الاستبدال</label>
                                <input
                                    class="w-full h-11 px-3.5 rounded-xl bg-white border text-[13px] text-gray-800 focus:ring-2 outline-none transition"
                                    type="date"
                                    x-bind:class="row.errors.replacement_date ? 'border-red-300 focus:ring-red-200' :
                                        'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                    x-bind:name="`items[${index}][replacement_date]`" x-model="row.replacement_date"
                                    x-on:change="validateRow(row)" />
                            </div>

                            <div class="sm:col-span-1 flex sm:items-end sm:justify-end">
                                <button
                                    class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    type="button" x-on:click="removeItem(index)">
                                    <svg class="w-[17px] h-[17px]" fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4h8v2" />
                                        <path d="M19 6l-1 14H6L5 6" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- بيانات المنتج المختار --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2.5" x-cloak x-show="product(row)">
                            <span class="text-[11px] text-gray-400">
                                الكود: <span class="font-bold text-gray-600" x-text="product(row)?.sku"></span>
                            </span>
                            <span class="text-[11px] text-gray-400">
                                السعر الحالي:
                                <span class="font-bold text-gray-600"
                                    x-text="Number(product(row)?.price || 0).toLocaleString('en') + ' ج.م'"></span>
                            </span>
                            <span class="text-[11px] text-gray-400">
                                الحالة: <span class="font-bold text-gray-600" x-text="product(row)?.available"></span>
                            </span>
                        </div>

                        <p class="text-[11px] font-semibold text-red-500 mt-2" x-cloak x-show="rowError(row)"
                            x-text="rowError(row)"></p>
                    </div>
                </template>
            </div>

            <div class="mt-3" x-cloak x-show="items.length === 0">
                <x-ui.empty-state icon="package-x" message="أضف منتجًا واحدًا على الأقل حتى تتمكن من حفظ الخطة."
                    title="لا توجد بنود بعد" />
            </div>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
            <x-ui.btn class="sm:w-40" variant="outline" :href="$plan ? route('maintenance-plans.show', $plan) : route('maintenance-plans.index')">إلغاء</x-ui.btn>
            <x-ui.btn class="sm:w-48" icon="check" type="submit">{{ $submitLabel }}</x-ui.btn>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        // نموذج خطة الصيانة: بنود ديناميكية + تحقق فوري يعكس قواعد الـ Form Request
        function maintenancePlanForm(config) {
            let uid = 0;

            const blankRow = () => ({uid: uid++, product_id: '', replacement_date: '', errors: {}});

            const toRow = item => ({
                uid: uid++,
                product_id: String(item.product_id || ''),
                replacement_date: item.replacement_date || '',
                errors: {}
            });

            return {
                errors: {},
                products: config.products,
                items: config.items.length ? config.items.map(toRow) : [blankRow()],

                rules: {
                    name(value) {
                        if (!value.trim()) return 'اسم الخطة مطلوب.';
                        return value.trim().length > 150 ? 'اسم الخطة طويل جدًا.' : '';
                    },
                    duration_months(value) {
                        if (!value) return '';
                        const months = Number(value);
                        return Number.isInteger(months) && months >= 1 && months <= 120 ? '' :
                            'مدة الخطة يجب أن تكون بين 1 و 120 شهرًا.';
                    }
                },

                check(el) {
                    const rule = this.rules[el.name];
                    if (rule) this.errors[el.name] = rule(el.value);
                },

                product(row) {
                    return this.products.find(p => p.id === row.product_id) || null;
                },

                addItem() {
                    this.items.push(blankRow());
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                validateRow(row) {
                    row.errors = {
                        product_id: row.product_id ? '' : 'اختر المنتج.',
                        replacement_date: row.replacement_date ? '' : 'حدّد تاريخ الاستبدال.'
                    };

                    const duplicated = row.product_id &&
                        this.items.filter(other => other.product_id === row.product_id).length > 1;

                    if (duplicated) row.errors.product_id = 'المنتج مكرر داخل الخطة.';

                    return !this.rowError(row);
                },

                rowError(row) {
                    return Object.values(row.errors).find(message => message) || '';
                },

                validateAll() {
                    this.$el.querySelectorAll('input[name], textarea[name]').forEach(el => this.check(el));

                    this.errors.items = this.items.length ? '' : 'يجب إضافة بند واحد على الأقل للخطة.';

                    const rowsValid = this.items.map(row => this.validateRow(row)).every(Boolean);
                    const fieldsValid = Object.values(this.errors).every(message => !message);

                    return rowsValid && fieldsValid;
                }
            };
        }
    </script>
@endpush
