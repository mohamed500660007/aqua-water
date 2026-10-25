{{-- نموذج تسوية العهدة بالجرد المشترك بين الإنشاء وتعديل المسودة: لكل صنف الكمية الفعلية التي رجع بها الموظف،
     والفرق عن عهدته يحدد التسوية (أقل = سالب، أكثر = موجب).
     المتغيرات: $settlement (null عند الإنشاء)، $action، $method، $employees، $products، $availability --}}
@php
    $defaultUser = old('user_id', $settlement?->user_id ?? ($selectedUserId ?? ''));
    $itemRows = array_values(old('items', $settlement
        ? $settlement->items->map(fn ($item) => ['product_id' => (string) $item->product_id, 'counted_quantity' => $item->counted_quantity])->all()
        : [['product_id' => '', 'counted_quantity' => '']]));
@endphp

<form action="{{ $action }}" method="POST"
    x-data="custodySettlementForm({ availability: @js($availability), userId: @js((string) $defaultUser), items: @js($itemRows) })"
    x-on:submit="if (! valid) $event.preventDefault()">
    @csrf
    @method($method)
    <input name="post" type="hidden" x-bind:value="postNow ? 1 : 0" />

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
            <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر حفظ التسوية:</p>
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
                        <x-icon class="w-[18px] h-[18px]" name="scale" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">بيانات التسوية</h2>
                        <p class="text-[11px] text-gray-400">جرد عهدة الموظف — يُصحَّح رصيد العهدة فقط، ولا يتغير أي مخزن ولا يُخصم أي راتب</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.field col="sm:col-span-2" label="الموظف" name="user_id" type="select" :options="$employees"
                        :value="$defaultUser" placeholder="اختر الموظف صاحب العهدة" required x-model="userId" />

                    <x-ui.field label="تاريخ التسوية" name="settlement_date" type="date"
                        :value="old('settlement_date', $settlement?->settlement_date?->toDateString() ?? today()->toDateString())" required />

                    <x-ui.field label="السبب" name="reason" :value="old('reason', $settlement?->reason)"
                        placeholder="مثال: جرد نهاية الشهر، تسليم عهدة، عجز في الجرد" required />

                    <x-ui.field col="sm:col-span-2" label="ملاحظات" name="note" type="textarea" rows="2"
                        :value="old('note', $settlement?->note)" placeholder="اختياري — رقم محضر الجرد أو أي توضيح" />
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="package" />
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">جرد الأصناف</h2>
                            <p class="text-[11px] text-gray-400">اكتب الكمية الفعلية التي رجع بها الموظف — الفرق عن عهدته هو التسوية</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.btn icon="list-checks" size="sm" variant="outline" x-bind:disabled="! userId || ! custodyCount"
                            x-on:click="loadCustody()">تحميل كل العهدة</x-ui.btn>
                        <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addItem()">إضافة صنف</x-ui.btn>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-bind:key="row.uid" x-for="(row, index) in items">
                        <div class="rounded-2xl border p-3.5 transition-colors"
                            x-bind:class="difference(row) < 0 ? 'border-red-200 bg-red-50/40' : (difference(row) > 0 ? 'border-brand-green bg-brand-green/10' : 'border-gray-100')">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-5">
                                    <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الصنف</label>
                                    <select
                                        class="w-full h-11 px-3.5 rounded-xl bg-white border text-[13px] text-gray-800 cursor-pointer focus:ring-2 outline-none transition"
                                        x-bind:class="duplicate(row) ? 'border-red-300 focus:ring-red-200' :
                                            'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                        x-bind:name="`items[${index}][product_id]`" x-model="row.product_id">
                                        <option value="">اختر الصنف...</option>
                                        @foreach ($products as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] font-semibold text-red-500 mt-1.5" x-cloak x-show="duplicate(row)">
                                        هذا الصنف مُضاف بالفعل في بند آخر.</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <span class="block text-[11px] font-bold text-gray-500 mb-1.5">في العهدة</span>
                                    <p class="h-11 px-3.5 rounded-xl bg-gray-50 text-[13px] font-bold text-gray-700 flex items-center"
                                        x-text="row.product_id && userId ? current(row).toLocaleString('en') : '—'"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الكمية الفعلية</label>
                                    <input
                                        class="w-full h-11 px-3.5 rounded-xl bg-white border text-[13px] text-gray-800 focus:ring-2 outline-none transition"
                                        min="0" placeholder="0" type="number"
                                        x-bind:class="row.counted_quantity !== '' && ! countValid(row) ? 'border-red-300 focus:ring-red-200' :
                                            'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                        x-bind:name="`items[${index}][counted_quantity]`" x-model.number="row.counted_quantity" />
                                </div>

                                <div class="sm:col-span-2 flex sm:items-end sm:justify-end">
                                    <x-ui.icon-btn icon="trash-2" label="حذف البند" tone="danger"
                                        x-bind:disabled="items.length === 1" x-on:click="removeItem(index)" />
                                </div>
                            </div>

                            <p class="mt-2.5 text-[11.5px] font-bold" x-cloak x-show="row.product_id && userId && countValid(row)"
                                x-bind:class="difference(row) < 0 ? 'text-red-600' : (difference(row) > 0 ? 'text-green-700' : 'text-gray-400')"
                                x-text="differenceLabel(row)"></p>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <div class="lg:col-span-1">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                <h2 class="text-sm font-bold text-gray-800 mb-4">ملخص التسوية</h2>

                <div class="grid grid-cols-2 gap-2.5 mb-2.5">
                    <div class="p-3.5 rounded-2xl bg-red-50">
                        <p class="text-[11px] text-red-500 mb-1">عجز (بالسالب)</p>
                        <p class="text-xl font-bold text-red-600" dir="ltr" x-text="'−' + shortage.toLocaleString('en')"></p>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-brand-green/15">
                        <p class="text-[11px] text-green-700 mb-1">زيادة (بالموجب)</p>
                        <p class="text-xl font-bold text-green-700" dir="ltr" x-text="'+' + surplus.toLocaleString('en')"></p>
                    </div>
                </div>
                <p class="text-[12px] font-bold text-gray-700 mb-4" x-text="typeLabel"></p>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-3.5 text-[11.5px] leading-relaxed text-gray-500">
                    مثال: في عهدته 10 ورجع بـ 8 = <b class="text-red-600">تسوية بالسالب 2</b>، ورجع بـ 12 = <b class="text-green-700">تسوية بالموجب 2</b>.
                    <b class="text-gray-700">المسودة</b> لا تغيّر أي رصيد، و<b class="text-gray-700">الترحيل</b> يعيد حساب الفرق من العهدة لحظتها
                    ويضبطها على الكمية الفعلية نهائيًا.
                </div>

                <p class="text-[11px] font-semibold text-red-500 mt-3" x-cloak x-show="! valid" x-text="invalidReason"></p>

                <div class="flex flex-col gap-2.5 mt-5">
                    <x-ui.btn class="w-full" icon="send" x-bind:disabled="! valid"
                        x-on:click="$store.confirm.ask({
                            title: 'ترحيل التسوية',
                            message: 'سيتم ضبط عهدة الموظف على الكميات الفعلية نهائيًا، ولن يتغير رصيد أي مخزن.',
                            label: 'ترحيل',
                            onOk: () => { postNow = true; $nextTick(() => $root.requestSubmit()) }
                        })">حفظ وترحيل</x-ui.btn>
                    <x-ui.btn class="w-full" icon="save" type="submit" variant="soft" x-bind:disabled="! valid"
                        x-on:click="postNow = false">حفظ كمسودة</x-ui.btn>
                    <x-ui.btn class="w-full" variant="outline" :href="route('inventory.custody-settlements.index')">إلغاء</x-ui.btn>
                </div>
            </section>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        // تسوية العهدة بالجرد: الفرق بين الكمية الفعلية ورصيد العهدة يُحسب محليًا من أرصدة عهد كل الموظفين
        function custodySettlementForm(config) {
            const blank = uid => ({ uid, product_id: '', counted_quantity: '' });

            return {
                availability: config.availability || {},
                userId: config.userId || '',
                postNow: false,
                items: (config.items || []).map((row, index) => ({ uid: index + 1, ...row })),

                nextUid() {
                    return this.items.length ? Math.max(...this.items.map(r => r.uid)) + 1 : 1;
                },

                addItem() {
                    this.items.push(blank(this.nextUid()));
                },

                removeItem(index) {
                    if (this.items.length === 1) return;
                    this.items.splice(index, 1);
                },

                get custody() {
                    return this.availability[this.userId] || {};
                },

                get custodyCount() {
                    return Object.values(this.custody).filter(quantity => Number(quantity) > 0).length;
                },

                // سطر لكل صنف في عهدة الموظف، والكمية الفعلية تبدأ مساوية للعهدة ثم تُعدَّل ما اختلف فقط
                loadCustody() {
                    let uid = 1;
                    this.items = Object.entries(this.custody)
                        .filter(([, quantity]) => Number(quantity) > 0)
                        .map(([productId, quantity]) => ({ uid: uid++, product_id: String(productId), counted_quantity: Number(quantity) }));
                },

                current(row) {
                    return Number(this.custody[row.product_id] || 0);
                },

                countValid(row) {
                    return Number.isInteger(row.counted_quantity) && row.counted_quantity >= 0;
                },

                difference(row) {
                    if (!row.product_id || !this.userId || !this.countValid(row)) return 0;
                    return row.counted_quantity - this.current(row);
                },

                differenceLabel(row) {
                    const difference = this.difference(row);
                    if (difference < 0) return `عجز ${Math.abs(difference).toLocaleString('en')} — تسوية بالسالب`;
                    if (difference > 0) return `زيادة ${difference.toLocaleString('en')} — تسوية بالموجب`;
                    return 'مطابق للعهدة — لا تسوية';
                },

                duplicate(row) {
                    if (!row.product_id) return false;
                    return this.items.filter(r => r.product_id === row.product_id).length > 1;
                },

                get shortage() {
                    return this.items.reduce((sum, row) => sum + Math.max(0, -this.difference(row)), 0);
                },

                get surplus() {
                    return this.items.reduce((sum, row) => sum + Math.max(0, this.difference(row)), 0);
                },

                get typeLabel() {
                    if (this.shortage && this.surplus) return 'تسوية موجبة وسالبة';
                    if (this.shortage) return 'تسوية بالسالب';
                    if (this.surplus) return 'تسوية بالموجب';
                    return 'لا يوجد فرق بعد';
                },

                get invalidReason() {
                    if (!this.userId) return 'اختر الموظف أولًا.';
                    if (this.items.some(row => !row.product_id || this.duplicate(row) || !this.countValid(row))) {
                        return 'أكمل كل بند بصنف غير مكرر وكمية فعلية صحيحة.';
                    }
                    return 'الكميات الفعلية مطابقة للعهدة — لا يوجد عجز أو زيادة للتسوية.';
                },

                get valid() {
                    if (!this.userId || !this.items.length) return false;

                    const rowsValid = this.items.every(row => row.product_id && !this.duplicate(row) && this.countValid(row));

                    return rowsValid && (this.shortage > 0 || this.surplus > 0);
                }
            };
        }
    </script>
@endpush
