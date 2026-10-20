@php
    $contract = $contract ?? null;

    $storedItems = $contract
        ? $contract->items->map(fn($item) => ['product_id' => (string) $item->product_id, 'quantity' => (string) $item->quantity])->all()
        : [];

    $itemRows = array_values(old('items', $storedItems));

    $productCatalog = $products->map(fn($product) => [
        'id' => (string) $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'price' => (float) $product->price,
    ]);

    // شمعات الخطة الثابتة للمعاينة؛ المواعيد الفعلية تُحسب من تاريخ التركيب عند الحفظ
    $planCandles = collect(\App\Domain\Maintenance\Models\MaintenancePlan::CANDLE_INTERVALS)
        ->map(fn($months, $position) => [
            'label' => \App\Domain\Maintenance\Models\MaintenancePlan::candleLabel($position),
            'interval' => \App\Domain\Maintenance\Models\MaintenancePlan::intervalLabel($months),
        ])
        ->values();

    // الكميات المحجوزة لهذا العقد تُضاف إلى المتاح أثناء التعديل
    $reserved = $contract
        ? $contract->items->groupBy('product_id')->map(fn($items) => (int) $items->sum('quantity'))
        : collect();

    $defaults = [
        'warehouseId' => (string) old('warehouse_id', $contract?->warehouse_id ?? array_key_first($warehouses) ?? ''),
        'salesRepId' => (string) old('sales_rep_id', $contract?->sales_rep_id ?? ''),
        'installerId' => (string) old('installer_id', $contract?->installer_id ?? ''),
        'stockSource' => old('stock_source', $contract?->stock_source?->value ?? 'warehouse'),
        'type' => old('type', $contract?->type?->value ?? 'cash'),
        'discount' => (float) old('discount', $contract?->discount ?? 0),
        'paid' => (float) old('paid', $contract?->paid ?? 0),
        'installmentsCount' => (int) old('installments_count', $contract?->installments_count ?? 6),
        'periodMonths' => (int) old('installment_period_months', $contract?->installment_period_months ?? 1),
        // العقد الجديد يدخل الخطة افتراضيًا؛ بعد خطأ تحقق تُحترم آخر قيمة اختارها المستخدم
        'withPlan' => session()->hasOldInput()
            ? filled(old('maintenance_plan_id'))
            : (bool) ($contract ? $contract->maintenance_plan_id : $plan?->id),
        'kind' => old('kind', $contract?->kind?->value ?? ($defaultKind ?? 'sale')),
        'price' => (float) old('price', $contract?->isMaintenance() ? $contract->subtotal : 0),
    ];

    $stockSourceOptions = collect($stockSources)->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values();
@endphp

<form action="{{ $action }}" method="POST"
    x-data="salesContractForm({
        items: @js($itemRows),
        products: @js($productCatalog),
        availability: @js($availability),
        employeeAvailability: @js($employeeAvailability),
        warehouseNames: @js($warehouses),
        employeeNames: @js($salesReps + $installers),
        reserved: @js($reserved),
        planCandles: @js($planCandles),
        stockSources: @js($stockSourceOptions),
        defaults: @js($defaults),
    })"
    x-on:submit="if (! validateAll()) $event.preventDefault()">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
            <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر حفظ العقد — راجع الحقول التالية:</p>
            <ul class="list-disc pr-5 space-y-1">
                @foreach ($errors->all() as $message)
                    <li class="text-[12px] text-red-500">{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
        <div class="lg:col-span-2 space-y-4 lg:space-y-5">
            {{-- بيانات العقد --}}
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="file-text" />
                    </span>
                    <h2 class="text-sm font-bold text-gray-800">بيانات العقد</h2>
                </div>

                <div class="mb-4">
                    <label class="block text-[12px] font-bold text-gray-600 mb-2">نوع التعاقد</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($kinds as $value => $label)
                            <button class="h-10 px-4 rounded-xl text-[12px] font-bold border transition-colors"
                                type="button"
                                x-bind:class="kind === @js($value) ? 'border-brand-green bg-brand-green/20 text-brand-dark' :
                                    'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                x-on:click="kind = @js($value)">{{ $label }}</button>
                        @endforeach
                    </div>
                    <input name="kind" type="hidden" x-bind:value="kind" />
                    <p class="text-[11px] text-gray-400 mt-1.5" x-cloak x-show="kind === 'maintenance'">
                        عقد صيانة فقط بسعر ثابت (لعميل داخلي أو خارجي) — لا يصرف أجهزة من المخزن، ويُحصَّل نقدًا أو بالتقسيط
                        مثل عقد البيع.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.field label="تاريخ العقد" name="contract_date" type="date"
                        :value="old('contract_date', $contract?->contract_date?->format('Y-m-d') ?? today()->toDateString())" required
                        x-model="contractDate" />

                    <x-ui.field label="نوع العقد" name="type" type="select" :options="$types" :value="$defaults['type']"
                        placeholder="اختر النوع" required x-model="type" />

                    <x-ui.field label="تاريخ التركيب" name="installation_date" type="date"
                        :value="old('installation_date', $contract?->installation_date?->format('Y-m-d'))"
                        hint="مطلوب لعقود البيع فقط" />

                    <x-ui.field label="تاريخ الصيانة" name="maintenance_date" type="date"
                        :value="old('maintenance_date', $contract?->maintenance_date?->format('Y-m-d'))" required />

                    <x-ui.field label="تاريخ التحصيل" name="collection_date" type="date"
                        :value="old('collection_date', $contract?->collection_date?->format('Y-m-d'))" />

                    @if ($plan)
                        <label class="flex items-center gap-3 self-end h-11 px-3.5 rounded-xl border border-gray-200 cursor-pointer select-none">
                            <input class="w-4 h-4 rounded" style="accent-color: #84CC16" name="maintenance_plan_id" type="checkbox"
                                value="{{ $plan->id }}" x-model="withPlan" />
                            <span class="text-[13px] font-bold text-gray-700">ضمن خطة الصيانة</span>
                            <span class="text-[11px] text-gray-400">(7 شمعات)</span>
                        </label>
                    @endif

                    <x-ui.field col="sm:col-span-2" label="ملاحظات" name="notes" type="textarea" rows="2"
                        placeholder="أي تفاصيل إضافية عن العقد..." :value="$contract?->notes" />
                </div>
            </section>

            {{-- تعيين موظفين --}}
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="users" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">تعيين موظفين</h2>
                        <p class="text-[11px] text-gray-400">العميل، الفريق المسؤول، والمخزن الذي يُصرف منه المنتج</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-ui.field label="العميل" name="customer_id" type="select" :options="$customers"
                        :value="$contract?->customer_id" placeholder="اختر العميل" required />

                    <x-ui.field label="مندوب المبيعات" name="sales_rep_id" type="select" :options="$salesReps"
                        :value="$contract?->sales_rep_id" placeholder="بدون تعيين" x-model="salesRepId" />

                    <x-ui.field label="فني التركيب" name="installer_id" type="select" :options="$installers"
                        :value="$contract?->installer_id" placeholder="بدون تعيين" x-model="installerId" />

                    <x-ui.field label="المخزن" name="warehouse_id" type="select" :options="$warehouses"
                        :value="$defaults['warehouseId']" placeholder="اختر المخزن" required x-model="warehouseId" />
                </div>

                <div class="mt-4 pt-4 border-t border-gray-50" x-show="kind === 'sale'">
                    <label class="block text-[12px] font-bold text-gray-600 mb-2">تُصرف البضاعة من</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-bind:key="source.value" x-for="source in stockSources">
                            <button
                                class="h-10 px-4 rounded-xl text-[12px] font-bold border transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5"
                                type="button"
                                x-bind:class="stockSource === source.value ? 'border-brand-green bg-brand-green/20 text-brand-dark' :
                                    'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                x-bind:disabled="source.value === 'sales_rep' ? !salesRepId : (source.value === 'installer' ? !installerId : false)"
                                x-on:click="stockSource = source.value">
                                <span x-text="source.label"></span>
                                <template x-if="source.value === 'sales_rep' && salesRepId">
                                    <span class="text-[10px] font-normal opacity-70"
                                        x-text="'(' + employeeTotal(salesRepId).toLocaleString('en') + ' قطعة إجمالًا)'"></span>
                                </template>
                                <template x-if="source.value === 'installer' && installerId">
                                    <span class="text-[10px] font-normal opacity-70"
                                        x-text="'(' + employeeTotal(installerId).toLocaleString('en') + ' قطعة إجمالًا)'"></span>
                                </template>
                            </button>
                        </template>
                    </div>
                    <input name="stock_source" type="hidden" x-bind:value="stockSource" />
                    <p class="text-[11px] font-semibold text-red-500 mt-1.5" x-cloak x-show="errors.stockSource"
                        x-text="errors.stockSource"></p>

                    <p class="text-[11px] text-amber-600 mt-1.5" x-cloak
                        x-show="stockSource === 'sales_rep' && salesRepId && employeeTotal(salesRepId) === 0">
                        هذا المندوب لا يحمل أي عهدة حاليًا — سلّمه عهدة من صفحة «تسليم عهدة لموظف» أولًا، أو اختر
                        مصدرًا آخر.
                    </p>
                    <p class="text-[11px] text-amber-600 mt-1.5" x-cloak
                        x-show="stockSource === 'installer' && installerId && employeeTotal(installerId) === 0">
                        هذا الفني لا يحمل أي عهدة حاليًا — سلّمه عهدة من صفحة «تسليم عهدة لموظف» أولًا، أو اختر
                        مصدرًا آخر.
                    </p>
                </div>
            </section>

            {{-- بنود العقد --}}
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5" x-show="kind === 'sale'">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="package" />
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">بنود العقد</h2>
                            <p class="text-[11px] text-gray-400">السعر والكمية المتاحة تظهر فور اختيار المنتج</p>
                        </div>
                    </div>
                    <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addItem()">إضافة بند</x-ui.btn>
                </div>

                <p class="text-[12px] font-semibold text-red-500 mb-3" x-cloak x-show="errors.items"
                    x-text="errors.items"></p>

                <div class="space-y-3">
                    <template x-bind:key="row.uid" x-for="(row, index) in items">
                        <div class="rounded-2xl border border-gray-100 p-3.5">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-6">
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
                                        x-bind:class="row.errors.quantity ? 'border-red-300 focus:ring-red-200' :
                                            'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                        x-bind:name="`items[${index}][quantity]`" x-model.number="row.quantity"
                                        x-on:input="validateRow(row)" />
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-gray-500 mb-1.5">إجمالي البند</label>
                                    <div
                                        class="h-11 px-3.5 rounded-xl bg-gray-50 border border-gray-100 flex items-center text-[13px] font-bold text-gray-800">
                                        <span x-text="Number(lineTotal(row)).toLocaleString('en')"></span>
                                        <span class="text-[10px] text-gray-400 mr-1">ج.م</span>
                                    </div>
                                </div>

                                <div class="sm:col-span-1 flex sm:items-end sm:justify-end">
                                    <button
                                        class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                        type="button" x-on:click="removeItem(index)">
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

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2.5" x-cloak
                                x-show="product(row)">
                                <span class="text-[11px] text-gray-400">
                                    السعر الحالي:
                                    <span class="font-bold text-gray-600"
                                        x-text="Number(product(row)?.price || 0).toLocaleString('en') + ' ج.م'"></span>
                                </span>
                                <span class="text-[11px] text-gray-400">
                                    المتاح في <span class="font-bold text-gray-600" x-text="sourceLabel"></span>:
                                    <span class="font-bold"
                                        x-bind:class="available(row) > 0 ? 'text-green-700' : 'text-red-500'"
                                        x-text="Number(available(row)).toLocaleString('en') + ' قطعة'"></span>
                                </span>
                            </div>

                            <p class="text-[11px] text-amber-600 mt-1" x-cloak
                                x-show="product(row) && available(row) <= 0 && !rowError(row)">
                                لا يوجد رصيد لهذا المنتج في <span x-text="sourceLabel"></span> حاليًا.
                            </p>

                            <p class="text-[11px] font-semibold text-red-500 mt-2" x-cloak x-show="rowError(row)"
                                x-text="rowError(row)"></p>
                        </div>
                    </template>
                </div>

                <div class="mt-3" x-cloak x-show="items.length === 0">
                    <x-ui.empty-state icon="package-x" message="أضف منتجًا واحدًا على الأقل حتى تتمكن من حفظ العقد."
                        title="لا توجد بنود بعد" />
                </div>
            </section>

            {{-- بنود خطة الصيانة المختارة --}}
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5" x-cloak
                x-show="withPlan && planCandles.length">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="shield-check" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">شمعات خطة الصيانة</h2>
                        <p class="text-[11px] text-gray-400">المواعيد تُحسب من تاريخ التركيب، وتُحفظ نسخة ثابتة منها مع العقد</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <template x-bind:key="index" x-for="(item, index) in planCandles">
                        <div class="flex items-center justify-between gap-3 p-3 rounded-2xl border border-gray-100">
                            <span class="text-[13px] font-bold text-gray-800" x-text="item.label"></span>
                            <span class="shrink-0 text-[11.5px] font-bold text-gray-600" x-text="item.interval"></span>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        {{-- الملخص المالي --}}
        <div class="lg:col-span-1">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                <h2 class="text-sm font-bold text-gray-800 mb-4">الملخص المالي</h2>

                <div class="py-3 border-b border-gray-50" x-cloak x-show="kind === 'maintenance'">
                    <label class="block text-[12px] font-bold text-gray-600 mb-1.5">سعر عقد الصيانة (ج.م)</label>
                    <input
                        class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                        min="0" name="price" placeholder="0" step="0.01" type="number" x-model.number="price" />
                    <p class="text-[11px] font-semibold text-red-500 mt-1.5" x-cloak x-show="errors.price"
                        x-text="errors.price"></p>
                </div>

                <div class="flex items-center justify-between py-2.5 border-b border-gray-50" x-show="kind === 'sale'">
                    <span class="text-[12px] text-gray-400">السعر الأصلي</span>
                    <span class="text-[13px] font-bold text-gray-800">
                        <span x-text="Number(subtotal).toLocaleString('en')"></span>
                        <span class="text-[10px] text-gray-400">ج.م</span>
                    </span>
                </div>

                <div class="py-3 border-b border-gray-50">
                    <label class="block text-[12px] font-bold text-gray-600 mb-1.5">الخصم (ج.م)</label>
                    <input
                        class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                        min="0" name="discount" placeholder="0" step="0.01" type="number"
                        x-model.number="discount" />
                    <p class="text-[11px] font-semibold text-red-500 mt-1.5" x-cloak x-show="errors.discount"
                        x-text="errors.discount"></p>
                </div>

                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                    <span class="text-[12px] text-gray-400">السعر بعد الخصم</span>
                    <span class="text-[13px] font-bold text-gray-800">
                        <span x-text="Number(total).toLocaleString('en')"></span>
                        <span class="text-[10px] text-gray-400">ج.م</span>
                    </span>
                </div>

                <div class="py-3 border-b border-gray-50">
                    <label class="block text-[12px] font-bold text-gray-600 mb-1.5">المدفوع مقدمًا (ج.م)</label>
                    <input
                        class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                        min="0" name="paid" placeholder="0" step="0.01" type="number" x-model.number="paid" />
                    <p class="text-[11px] font-semibold text-red-500 mt-1.5" x-cloak x-show="errors.paid"
                        x-text="errors.paid"></p>
                </div>

                <div class="flex items-center justify-between py-3">
                    <span class="text-[12px] text-gray-400">المتبقي</span>
                    <span class="text-base font-bold text-gray-800">
                        <span x-text="Number(remaining).toLocaleString('en')"></span>
                        <span class="text-[10px] text-gray-400">ج.م</span>
                    </span>
                </div>

                {{-- بيانات التقسيط --}}
                <div class="space-y-3 pt-3 border-t border-gray-100" x-cloak x-show="type === 'installment'">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-600 mb-1.5">عدد الأقساط</label>
                        <input
                            class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 outline-none transition"
                            max="120" min="1" name="installments_count" type="number"
                            x-model.number="installmentsCount" />
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-gray-600 mb-1.5">دورية السداد (بالأشهر)</label>
                        <input
                            class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 outline-none transition"
                            max="12" min="1" name="installment_period_months" type="number"
                            x-model.number="periodMonths" />
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-gray-600 mb-1.5">تاريخ أول استحقاق</label>
                        <input
                            class="w-full h-11 px-3.5 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 outline-none transition"
                            name="first_due_date" type="date" x-model="firstDueDate" />
                    </div>

                    {{-- المبلغ المستحق دوريًا --}}
                    <div class="rounded-2xl bg-brand-green/20 border border-brand-green p-4 text-center">
                        <p class="text-[11px] font-bold text-gray-600 mb-1" x-text="installmentLabel"></p>
                        <p class="text-3xl font-bold text-brand-dark leading-none">
                            <span x-text="Number(installmentAmount).toLocaleString('en')"></span>
                            <span class="text-xs font-semibold text-gray-500">ج.م</span>
                        </p>
                        <p class="text-[11px] text-gray-500 mt-1.5">
                            <span x-text="installmentsCount"></span> قسط —
                            آخر استحقاق <span dir="ltr" x-text="lastDueDate"></span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2.5 mt-5">
                    <x-ui.btn class="w-full" icon="check" type="submit">{{ $submitLabel }}</x-ui.btn>
                    <x-ui.btn class="w-full" variant="outline" :href="$contract ? route('contracts.show', $contract) : route('contracts.index')">إلغاء</x-ui.btn>
                </div>
            </section>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        // نموذج عقد البيع: بنود ديناميكية + ملخص مالي فوري + جدول تقسيط محسوب
        function salesContractForm(config) {
            let uid = 0;

            const blankRow = () => ({uid: uid++, product_id: '', quantity: 1, errors: {}});

            const toRow = item => ({
                uid: uid++,
                product_id: String(item.product_id || ''),
                quantity: Number(item.quantity) || 1,
                errors: {}
            });

            const round = value => Math.round((Number(value) || 0) * 100) / 100;

            return {
                errors: {},
                products: config.products,
                availability: config.availability || {},
                employeeAvailability: config.employeeAvailability || {},
                warehouseNames: config.warehouseNames || {},
                employeeNames: config.employeeNames || {},
                reserved: config.reserved || {},
                planCandles: config.planCandles || [],
                stockSources: config.stockSources || [],

                items: config.items.length ? config.items.map(toRow) : [blankRow()],
                warehouseId: config.defaults.warehouseId,
                salesRepId: config.defaults.salesRepId,
                installerId: config.defaults.installerId,
                stockSource: config.defaults.stockSource,
                type: config.defaults.type,
                contractDate: @js(old('contract_date', $contract?->contract_date?->format('Y-m-d') ?? today()->toDateString())),
                discount: config.defaults.discount,
                paid: config.defaults.paid,
                installmentsCount: config.defaults.installmentsCount,
                periodMonths: config.defaults.periodMonths,
                firstDueDate: @js(old('first_due_date', $contract?->first_due_date?->format('Y-m-d') ?? today()->addMonth()->toDateString())),
                withPlan: config.defaults.withPlan,
                kind: config.defaults.kind,
                price: config.defaults.price,

                product(row) {
                    return this.products.find(p => p.id === row.product_id) || null;
                },

                // اسم المصدر المختار حاليًا — يُستخدم لتوضيح "المتاح" بدل رقم مجرّد بلا سياق
                get sourceLabel() {
                    if (this.stockSource === 'sales_rep') {
                        return this.salesRepId ? (this.employeeNames[this.salesRepId] || 'المندوب') : 'مندوب المبيعات (لم يُحدَّد بعد)';
                    }

                    if (this.stockSource === 'installer') {
                        return this.installerId ? (this.employeeNames[this.installerId] || 'الفني') : 'فني التركيب (لم يُحدَّد بعد)';
                    }

                    return this.warehouseId ? (this.warehouseNames[this.warehouseId] || 'المخزن') : 'المخزن (لم يُحدَّد بعد)';
                },

                // إجمالي ما يحمله موظف من كل المنتجات — يُعرض كتلميح جانب زر اختيار المصدر
                employeeTotal(userId) {
                    if (!userId) return 0;

                    const items = this.employeeAvailability[userId] || {};

                    return Object.values(items).reduce((sum, qty) => sum + (Number(qty) || 0), 0);
                },

                available(row) {
                    const reserved = this.reserved[row.product_id] || 0;
                    let base = 0;

                    if (this.stockSource === 'sales_rep' && this.salesRepId) {
                        base = Number((this.employeeAvailability[this.salesRepId] || {})[row.product_id] || 0);
                    } else if (this.stockSource === 'installer' && this.installerId) {
                        base = Number((this.employeeAvailability[this.installerId] || {})[row.product_id] || 0);
                    } else {
                        base = Number((this.availability[this.warehouseId] || {})[row.product_id] || 0);
                    }

                    return base + Number(reserved);
                },

                lineTotal(row) {
                    return round((this.product(row)?.price || 0) * (Number(row.quantity) || 0));
                },

                get subtotal() {
                    if (this.kind === 'maintenance') return round(this.price);

                    return round(this.items.reduce((sum, row) => sum + this.lineTotal(row), 0));
                },

                get total() {
                    return round(Math.max(0, this.subtotal - (Number(this.discount) || 0)));
                },

                get remaining() {
                    return round(Math.max(0, this.total - (Number(this.paid) || 0)));
                },

                get installmentAmount() {
                    const count = Number(this.installmentsCount) || 0;
                    return count > 0 ? round(this.remaining / count) : 0;
                },

                get installmentLabel() {
                    const period = Number(this.periodMonths) || 1;
                    return period === 1 ? 'المبلغ المستحق شهريًا' : `المبلغ المستحق كل ${period} أشهر`;
                },

                get lastDueDate() {
                    const count = Number(this.installmentsCount) || 0;
                    if (!this.firstDueDate || count < 1) return '—';

                    const date = new Date(this.firstDueDate);
                    date.setMonth(date.getMonth() + (count - 1) * (Number(this.periodMonths) || 1));

                    return date.toISOString().slice(0, 10);
                },

                addItem() {
                    this.items.push(blankRow());
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                validateRow(row) {
                    const quantity = Number(row.quantity);

                    row.errors = {
                        product_id: row.product_id ? '' : 'اختر المنتج.',
                        quantity: Number.isInteger(quantity) && quantity >= 1 ? '' : 'الكمية يجب ألا تقل عن 1.'
                    };

                    if (row.product_id && this.items.filter(other => other.product_id === row.product_id).length > 1) {
                        row.errors.product_id = 'المنتج مكرر داخل العقد.';
                    }

                    if (!row.errors.quantity && quantity > this.available(row)) {
                        row.errors.quantity = `الكمية المتاحة في ${this.sourceLabel} ${this.available(row)} فقط.`;
                    }

                    return !this.rowError(row);
                },

                rowError(row) {
                    return Object.values(row.errors).find(message => message) || '';
                },

                validateAll() {
                    const isSale = this.kind === 'sale';

                    this.errors = {
                        items: !isSale || this.items.length ? '' : 'يجب إضافة بند واحد على الأقل للعقد.',
                        price: isSale || (Number(this.price) || 0) > 0 ? '' : 'اكتب سعر عقد الصيانة.',
                        discount: (Number(this.discount) || 0) > this.subtotal ? 'الخصم لا يمكن أن يتجاوز إجمالي البنود.' : '',
                        paid: (Number(this.paid) || 0) > this.total ? 'المبلغ المدفوع لا يمكن أن يتجاوز إجمالي العقد.' : '',
                        stockSource: ''
                    };

                    if (!this.errors.paid && this.type === 'installment' && this.remaining <= 0) {
                        this.errors.paid = 'لا يمكن إنشاء عقد تقسيط بدون مبلغ متبقٍّ للسداد.';
                    }

                    if (isSale && this.stockSource === 'sales_rep' && !this.salesRepId) {
                        this.errors.stockSource = 'يجب اختيار مندوب المبيعات لأن مصدر البضاعة منه.';
                    } else if (isSale && this.stockSource === 'installer' && !this.installerId) {
                        this.errors.stockSource = 'يجب اختيار فني التركيب لأن مصدر البضاعة منه.';
                    }

                    // بنود البيع المخفية في عقد الصيانة لا تُرسل للحفظ، فلا داعي للتحقق منها
                    const rowsValid = !isSale || this.items.map(row => this.validateRow(row)).every(Boolean);

                    return rowsValid && Object.values(this.errors).every(message => !message);
                }
            };
        }
    </script>
@endpush
