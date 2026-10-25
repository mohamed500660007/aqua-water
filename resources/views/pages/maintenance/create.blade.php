@extends('layouts.app')

@section('title', 'إضافة صيانة')
@section('page-title', 'إضافة صيانة')
@section('page-subtitle', 'قطاعي لشمعة واحدة أو جملة لكل شمعات العميل')

@php
    $customer = $contract->customer;
    $oldLines = old('lines', []);
    $lines = $pending->mapWithKeys(fn ($item) => [$item->id => [
        'product_id' => (string) ($oldLines[$item->id]['product_id'] ?? ''),
        'price' => $oldLines[$item->id]['price'] ?? '',
        'discount' => $oldLines[$item->id]['discount'] ?? '',
        'under_warranty' => (bool) ($oldLines[$item->id]['under_warranty'] ?? false),
    ]]);
    $inputClass = 'w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition disabled:bg-gray-50 disabled:text-gray-400';
@endphp

@section('content')
    <x-ui.page-header icon="wrench" subtitle="قطاعي لشمعة واحدة أو جملة لكل شمعات العميل" title="إضافة صيانة">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('maintenance.file')">رجوع لملف الصيانة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('maintenance.store', $candle) }}" method="POST"
        x-data="maintenanceForm({
            type: @js(old('type', 'per_item')),
            selectedId: {{ $candle->id }},
            technicianId: @js((string) old('technician_id', $defaultTechnician ?? '')),
            done: @js((bool) old('done', true)),
            lines: @js($lines),
            custody: @js($custody),
            productNames: @js($productNames),
        })">
        @csrf
        <input name="done" type="hidden" x-bind:value="done ? 1 : 0" />

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسجيل الصيانة:</p>
                <ul class="list-disc pr-5 space-y-1">
                    @foreach ($errors->all() as $message)
                        <li class="text-[12px] text-red-500">{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- العميل والجهاز --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <p class="text-[11px] font-bold text-gray-400">كود العميل</p>
                <p class="text-[13px] font-bold text-gray-800" dir="ltr">{{ $customer?->code ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400">العميل</p>
                <p class="text-[13px] font-bold text-gray-800">{{ $customer?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400">الجهاز</p>
                <p class="text-[13px] font-bold text-gray-800">{{ $contract->items->pluck('product_name')->unique()->join('، ') ?: '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400">العقد</p>
                <p class="text-[13px] font-bold text-gray-800" dir="ltr">{{ $contract->code }}</p>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
            <div class="lg:col-span-2 space-y-4">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <label class="block text-[12px] font-bold text-gray-600 mb-2">قطاعي ولا جملة؟</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($types as $value => $label)
                            <label class="h-12 rounded-xl border flex items-center justify-center gap-2 cursor-pointer text-[13px] font-bold transition-colors"
                                x-bind:class="type === @js($value) ? 'border-brand-green bg-brand-green/20 text-brand-dark' : 'border-gray-200 text-gray-500 hover:bg-gray-50'">
                                <input class="sr-only" name="type" type="radio" value="{{ $value }}" x-model="type" />
                                {{ $label }}
                                <span class="text-[11px] font-semibold opacity-70">{{ $value === 'package' ? "({$pending->count()} شمعات)" : '(شمعة واحدة)' }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2" x-show="type === 'package'">
                        جملة: كل شمعات العميل اللي فاضلة بتتسجل مرة واحدة، والعميل بيخرج من ملف الصيانة. الفلوس ممكن تكون صفر لو متسجلة في عقد الصيانة.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-[12px] font-bold text-gray-600 mb-1.5" for="technician_id">الفني <span class="text-red-400">*</span></label>
                            <select class="{{ $inputClass }} h-11" id="technician_id" name="technician_id" x-model="technicianId" required>
                                <option value="">اختار الفني</option>
                                @foreach ($technicians as $technician)
                                    <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-gray-600 mb-1.5" for="date">تاريخ الصيانة <span class="text-red-400">*</span></label>
                            <input class="{{ $inputClass }} h-11" id="date" name="date" required type="date"
                                value="{{ old('date', today()->toDateString()) }}" />
                        </div>
                    </div>

                    <label class="mt-4 flex items-center gap-3 rounded-xl border border-gray-200 px-3.5 h-11 cursor-pointer select-none">
                        <input class="w-4 h-4" style="accent-color: #84CC16" type="checkbox" x-model="done" />
                        <span class="text-[13px] font-bold text-gray-700">تم — الصيانة اتعملت فعلًا</span>
                        <span class="text-[11px] text-gray-400" x-show="! done">(هيتحفظ الفني والميعاد بس)</span>
                    </label>
                </section>

                {{-- الشمعات: قطاعي يعرض الشمعة المختارة فقط، وجملة يعرضها كلها --}}
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5" x-show="done">
                    <h2 class="text-sm font-bold text-gray-800 mb-1">الشمعات</h2>
                    <p class="text-[11px] text-gray-400 mb-3">الشمعة المستخدمة بتتخصم من عهدة الفني.</p>

                    <div class="space-y-3">
                        @foreach ($pending as $item)
                            <div class="rounded-2xl border border-gray-100 p-3.5" x-show="type === 'package' || selectedId === {{ $item->id }}">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <p class="text-[13.5px] font-extrabold text-gray-800">{{ $item->product_name }}</p>
                                    <label class="flex items-center gap-2 text-[12px] font-bold text-gray-600 cursor-pointer">
                                        <input name="lines[{{ $item->id }}][under_warranty]" type="hidden" value="0" />
                                        <input class="w-4 h-4" style="accent-color: #84CC16" name="lines[{{ $item->id }}][under_warranty]" type="checkbox" value="1"
                                            x-model="lines[{{ $item->id }}].under_warranty" />
                                        ضمان
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الشمعة من عهدة الفني</label>
                                        <select class="{{ $inputClass }}" name="lines[{{ $item->id }}][product_id]" x-model="lines[{{ $item->id }}].product_id">
                                            <option value="" x-text="technicianId ? (custodyProducts.length ? 'اختار الشمعة' : 'عهدة الفني فاضية') : 'اختار الفني الأول'"></option>
                                            <template x-for="product in custodyProducts" x-bind:key="product.id">
                                                <option x-bind:value="product.id" x-bind:selected="lines[{{ $item->id }}].product_id === product.id"
                                                    x-text="`${product.name} (معاه ${product.quantity})`"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">السعر (ج.م)</label>
                                        <input class="{{ $inputClass }}" min="0" name="lines[{{ $item->id }}][price]" placeholder="0" step="0.01" type="number"
                                            x-bind:disabled="lines[{{ $item->id }}].under_warranty" x-model="lines[{{ $item->id }}].price" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الخصم (ج.م)</label>
                                        <input class="{{ $inputClass }}" min="0" name="lines[{{ $item->id }}][discount]" placeholder="0" step="0.01" type="number"
                                            x-bind:disabled="lines[{{ $item->id }}].under_warranty" x-model="lines[{{ $item->id }}].discount" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="lg:col-span-1">
                <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">الملخص</h2>

                    <div class="grid grid-cols-2 gap-2.5 mb-4" x-show="done">
                        <div class="p-4 rounded-2xl bg-gray-50">
                            <p class="text-[11px] text-gray-400 mb-1">الشمعات</p>
                            <p class="text-2xl font-bold text-gray-800" x-text="activeIds.length"></p>
                        </div>
                        <div class="p-4 rounded-2xl bg-brand-green/25">
                            <p class="text-[11px] text-gray-500 mb-1">المبلغ (ج.م)</p>
                            <p class="text-xl font-bold text-gray-800" x-text="total.toLocaleString('en', { minimumFractionDigits: 2 })"></p>
                        </div>
                    </div>

                    <x-ui.btn class="w-full" icon="check" type="submit">حفظ</x-ui.btn>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // نموذج الصيانة: الشمعات المعروضة حسب النوع، والشمع المتاح من عهدة الفني المختار، والمبلغ فوريًا
        function maintenanceForm(config) {
            return {
                type: config.type,
                selectedId: config.selectedId,
                technicianId: config.technicianId,
                done: config.done,
                lines: config.lines,
                custody: config.custody || {},
                productNames: config.productNames || {},

                get activeIds() {
                    return Object.keys(this.lines).map(Number)
                        .filter(id => this.type === 'package' || id === this.selectedId);
                },

                get custodyProducts() {
                    const balances = this.custody[this.technicianId] || {};

                    return Object.entries(balances)
                        .filter(([, quantity]) => quantity > 0)
                        .map(([id, quantity]) => ({ id: String(id), name: this.productNames[id] || 'منتج', quantity }));
                },

                get total() {
                    return this.activeIds.reduce((sum, id) => {
                        const line = this.lines[id];
                        if (line.under_warranty) return sum;

                        return sum + Math.max(0, (Number(line.price) || 0) - (Number(line.discount) || 0));
                    }, 0);
                },
            };
        }
    </script>
@endpush
