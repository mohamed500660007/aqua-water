{{-- نموذج تسوية العهدة المشترك بين الإنشاء وتعديل المسودة.
     المتغيرات: $settlement (null عند الإنشاء)، $action، $method، $employees، $products، $types، $availability --}}
@php
    $defaultType = old('type', $settlement?->type->value ?? \App\Domain\Inventory\Enums\CustodySettlementType::Negative->value);
    $defaultUser = old('user_id', $settlement?->user_id ?? ($selectedUserId ?? ''));
    $itemRows = array_values(old('items', $settlement
        ? $settlement->items->map(fn ($item) => ['product_id' => (string) $item->product_id, 'quantity' => $item->quantity])->all()
        : [['product_id' => '', 'quantity' => '']]));
@endphp

<form action="{{ $action }}" method="POST"
    x-data="custodySettlementForm({ availability: @js($availability), userId: @js((string) $defaultUser), type: @js($defaultType), items: @js($itemRows) })"
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
                        <p class="text-[11px] text-gray-400">تصحيح رصيد العهدة فقط — لا يتغير أي مخزن ولا يُخصم أي راتب</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.field col="sm:col-span-2" label="الموظف" name="user_id" type="select" :options="$employees"
                        :value="$defaultUser" placeholder="اختر الموظف صاحب العهدة" required x-model="userId" />

                    <div class="sm:col-span-2">
                        <span class="block text-[12px] font-bold text-gray-600 mb-1.5">نوع التسوية <span class="text-red-400">*</span></span>
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach ($types as $value => $label)
                                @php($negative = $value === \App\Domain\Inventory\Enums\CustodySettlementType::Negative->value)
                                <label class="flex items-start gap-2.5 rounded-xl border p-3 cursor-pointer transition-colors"
                                    x-bind:class="type === @js($value) ? '{{ $negative ? 'border-red-300 bg-red-50' : 'border-brand-green bg-brand-green/10' }}' : 'border-gray-200 hover:bg-gray-50'">
                                    <input class="mt-0.5 accent-[#0F172A]" name="type" type="radio" value="{{ $value }}" x-model="type" />
                                    <span>
                                        <span class="block text-[13px] font-bold text-gray-800">{{ $label }}</span>
                                        <span class="block text-[11px] text-gray-500">
                                            {{ $negative ? 'عجز: إنقاص العهدة دون إضافة للمخزن' : 'زيادة: إضافة للعهدة دون خصم من المخزن' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <x-ui.field label="تاريخ التسوية" name="settlement_date" type="date"
                        :value="old('settlement_date', $settlement?->settlement_date?->toDateString() ?? today()->toDateString())" required />

                    <x-ui.field label="السبب" name="reason" :value="old('reason', $settlement?->reason)"
                        placeholder="مثال: عجز في الجرد، زيادة جرد، تصحيح تسجيل" required />

                    <x-ui.field col="sm:col-span-2" label="ملاحظات" name="note" type="textarea" rows="2"
                        :value="old('note', $settlement?->note)" placeholder="اختياري — رقم محضر الجرد أو أي توضيح" />
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="package" />
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">أصناف التسوية</h2>
                            <p class="text-[11px] text-gray-400">اكتب الكمية موجبة دائمًا — نوع التسوية يحدد الإشارة</p>
                        </div>
                    </div>
                    <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addItem()">إضافة صنف</x-ui.btn>
                </div>

                <div class="space-y-3">
                    <template x-bind:key="row.uid" x-for="(row, index) in items">
                        <div class="rounded-2xl border border-gray-100 p-3.5">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-7">
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
                                    <x-ui.icon-btn icon="trash-2" label="حذف البند" tone="danger"
                                        x-bind:disabled="items.length === 1" x-on:click="removeItem(index)" />
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2.5 text-[11px] text-gray-400" x-cloak x-show="row.product_id && userId">
                                <span>الرصيد الحالي: <b class="text-gray-700" x-text="current(row).toLocaleString('en')"></b></span>
                                <span>بعد التسوية:
                                    <b x-bind:class="after(row) < 0 ? 'text-red-500' : 'text-gray-800'" x-text="after(row).toLocaleString('en')"></b></span>
                                <span class="font-semibold text-red-500" x-show="after(row) < 0">الكمية أكبر من رصيد العهدة.</span>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <div class="lg:col-span-1">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                <h2 class="text-sm font-bold text-gray-800 mb-4">ملخص التسوية</h2>

                <div class="grid grid-cols-2 gap-2.5 mb-4">
                    <div class="p-3.5 rounded-2xl bg-gray-50">
                        <p class="text-[11px] text-gray-400 mb-1">عدد الأصناف</p>
                        <p class="text-xl font-bold text-gray-800" x-text="items.length"></p>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-gray-50">
                        <p class="text-[11px] text-gray-400 mb-1">أثرها على العهدة</p>
                        <p class="text-xl font-bold" x-bind:class="type === 'negative' ? 'text-red-500' : 'text-green-700'" dir="ltr"
                            x-text="(type === 'negative' ? '-' : '+') + Number(totalQuantity).toLocaleString('en')"></p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-3.5 text-[11.5px] leading-relaxed text-gray-500">
                    <b class="text-gray-700">المسودة</b> لا تغيّر أي رصيد. <b class="text-gray-700">الترحيل</b> يحدّث عهدة الموظف نهائيًا،
                    ولا يمكن تعديل التسوية أو حذفها بعده.
                </div>

                <p class="text-[11px] font-semibold text-red-500 mt-3" x-cloak x-show="! valid">
                    اختر الموظف وأكمل كل بند بصنف غير مكرر وكمية صحيحة.
                </p>

                <div class="flex flex-col gap-2.5 mt-5">
                    <x-ui.btn class="w-full" icon="send" x-bind:disabled="! valid"
                        x-on:click="$store.confirm.ask({
                            title: 'ترحيل التسوية',
                            message: 'سيتم تحديث عهدة الموظف نهائيًا، ولن يتغير رصيد أي مخزن.',
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
        // نموذج تسوية العهدة: يعرض الرصيد قبل/بعد لكل صنف محليًا من أرصدة عهد كل الموظفين
        function custodySettlementForm(config) {
            return {
                availability: config.availability || {},
                userId: config.userId || '',
                type: config.type || 'negative',
                postNow: false,
                items: (config.items || []).map((row, index) => ({ uid: index + 1, ...row })),

                addItem() {
                    const uid = this.items.length ? Math.max(...this.items.map(r => r.uid)) + 1 : 1;
                    this.items.push({ uid, product_id: '', quantity: '' });
                },

                removeItem(index) {
                    if (this.items.length === 1) return;
                    this.items.splice(index, 1);
                },

                current(row) {
                    const balances = this.availability[this.userId] || {};
                    return Number(balances[row.product_id] || 0);
                },

                after(row) {
                    const quantity = Number(row.quantity) || 0;
                    return this.type === 'negative' ? this.current(row) - quantity : this.current(row) + quantity;
                },

                duplicate(row) {
                    if (!row.product_id) return false;
                    return this.items.filter(r => r.product_id === row.product_id).length > 1;
                },

                rowValid(row) {
                    if (!row.product_id || this.duplicate(row)) return false;

                    return (Number(row.quantity) || 0) >= 1 && this.after(row) >= 0;
                },

                get totalQuantity() {
                    return this.items.reduce((sum, row) => sum + (Number(row.quantity) || 0), 0);
                },

                get valid() {
                    if (!this.userId || !this.items.length) return false;

                    return this.items.every(row => this.rowValid(row));
                }
            };
        }
    </script>
@endpush
