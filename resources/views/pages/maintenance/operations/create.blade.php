@extends('layouts.app')

@section('title', 'تسجيل صيانة')
@section('page-title', 'تسجيل صيانة')
@section('page-subtitle', 'اختار الشمعات اللي اتغيرت والفني — والباقي بيتحسب لوحده')

@use('App\Domain\Maintenance\Enums\MaintenanceOperationType')
@use('App\Domain\Sales\Enums\StockSource')

@php
    if ($device) {
        $package = $device->activeMaintenancePackage;
        $schedules = $device->candleSchedules->keyBy('position');
        // المراحل المستحقة خلال أسبوع تُحدَّد مسبقًا
        $dueSoon = $schedules->filter(fn ($schedule) => $schedule->next_due_at->lte(today()->addDays(7)))->keys()->all();
        $stageRows = $stages->map(fn ($stage) => [
            'position' => $stage->position,
            'name' => $stage->product?->name,
            'price' => (float) $stage->product?->price,
            'due' => $schedules->get($stage->position)?->next_due_at?->format('Y-m-d'),
            'overdue' => (bool) $schedules->get($stage->position)?->isOverdue(),
        ])->values();
        $rates = $technicians->pluck('maintenance_commission', 'id')->map(fn ($rate) => (float) $rate)
            ->union($salesReps->pluck('maintenance_commission', 'id')->map(fn ($rate) => (float) $rate));
        $config = [
            'type' => old('type', $package ? MaintenanceOperationType::Package->value : MaintenanceOperationType::PerItem->value),
            'stages' => $stageRows,
            'selected' => array_map('intval', old('positions', $dueSoon)),
            'prices' => old('prices', $stageRows->mapWithKeys(fn ($row) => [$row['position'] => $row['price']])->all()),
            'technicianId' => (string) old('technician_id', $device->installer_id ?? ''),
            'salesRepId' => (string) old('sales_rep_id', $device->sales_rep_id ?? ''),
            'rates' => $rates,
            'technicianCommission' => old('technician_commission'),
            'salesRepCommission' => old('sales_rep_commission'),
            'stockSource' => old('stock_source', StockSource::Installer->value),
            'packageRemaining' => $package?->remainingCandles() ?? 0,
        ];

        // الخيارات الإضافية تُفتح تلقائيًا إن كان فيها خطأ أو قيمة سابقة حتى لا يختفي الخطأ داخل قسم مطوي
        $openExtras = $errors->hasAny(['sales_rep_id', 'stock_source', 'warehouse_id', 'technician_commission', 'sales_rep_commission', 'note'])
            || filled(old('note'))
            || old('stock_source') === StockSource::Warehouse->value;
    }
@endphp

@section('content')
    <x-ui.page-header icon="wrench" subtitle="اختار الشمعات اللي اتغيرت والفني — والباقي بيتحسب لوحده" title="تسجيل صيانة">
        <x-slot:actions>
            @if ($device)
                <x-ui.btn icon="repeat" size="sm" variant="soft" :href="route('maintenance.operations.create')">جهاز آخر</x-ui.btn>
            @endif
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('maintenance.file')">رجوع</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @if (! $device)
        @include('pages.maintenance.partials.device-picker', ['route' => 'maintenance.operations.create'])
    @elseif ($stages->isEmpty())
        <x-ui.empty-state icon="sliders-horizontal" title="اضبط مراحل الشمع أولًا" message="لا يمكن تسجيل صيانة قبل تحديد شمعة كل مرحلة.">
            <x-slot:action><x-ui.btn icon="sliders-horizontal" size="sm" :href="route('maintenance.stages.edit')">ضبط المراحل</x-ui.btn></x-slot:action>
        </x-ui.empty-state>
    @else
        <form class="max-w-3xl mx-auto space-y-4" action="{{ route('maintenance.operations.store') }}" method="POST"
            x-data="maintenanceOperationForm(@js($config))" x-on:submit="if (! valid) $event.preventDefault()">
            @csrf
            <input name="sales_contract_id" type="hidden" value="{{ $device->id }}" />
            <input name="type" type="hidden" x-bind:value="type" />
            @if ($package)
                <input name="maintenance_package_id" type="hidden" value="{{ $package->id }}" x-bind:disabled="type !== 'package'" />
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                    <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر تسجيل الصيانة:</p>
                    <ul class="list-disc pr-5 space-y-1">
                        @foreach ($errors->all() as $message)
                            <li class="text-[12px] text-red-500">{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3.5">
                <x-ui.avatar :name="$device->customer?->name ?? '—'" size="w-11 h-11" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-gray-800 truncate">{{ $device->customer?->name }}</p>
                    <p class="text-[12px] text-gray-400 truncate">
                        <span dir="ltr">{{ $device->code }}</span> · {{ $device->items->pluck('product_name')->join('، ') }} ·
                        تركيب <span dir="ltr">{{ $device->installation_date->format('Y-m-d') }}</span>
                    </p>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-gray-800">
                        <span class="w-6 h-6 rounded-lg bg-brand-dark text-brand-green text-[12px] font-black flex items-center justify-center">1</span>
                        الشمعات اللي اتغيرت
                    </h2>
                    <button class="text-[12px] font-bold text-gray-500 hover:text-brand-dark" type="button" x-on:click="toggleAll()">
                        <span x-text="selected.length === stages.length ? 'إلغاء الكل' : 'تحديد الكل'"></span>
                    </button>
                </div>
                <p class="text-[11.5px] text-gray-400 mb-3">الشمعات المستحقة متعلّمة جاهزة — شيل أو زوّد حسب اللي اتغير فعلًا.</p>

                @if ($package)
                    {{-- للعميل رصيد عقد صيانة: يُخصم منه تلقائيًا بلا أسعار، مع إمكانية التحويل لقطاعي --}}
                    <div class="mb-3 flex flex-wrap items-center gap-2 rounded-xl px-3.5 py-2.5 text-[12px]"
                        x-bind:class="type === 'package' ? 'bg-purple-50 text-purple-800' : 'bg-gray-50 text-gray-600'">
                        <x-icon class="w-4 h-4 shrink-0" name="package-check" />
                        <span class="flex-1" x-text="type === 'package'
                            ? `هتتخصم من رصيد عقد الصيانة (${packageRemaining} شمعة) — من غير أسعار`
                            : 'محسوبة قطاعي بالسعر'"></span>
                        <button class="font-bold underline underline-offset-2" type="button"
                            x-on:click="type = type === 'package' ? 'per_item' : 'package'"
                            x-text="type === 'package' ? 'احسبها قطاعي' : 'اخصمها من العقد'"></button>
                    </div>
                @endif

                <div class="space-y-2">
                    <template x-bind:key="stage.position" x-for="stage in stages">
                        <div class="flex items-center gap-3 rounded-2xl border p-2.5 transition-colors"
                            x-bind:class="isSelected(stage.position) ? 'border-brand-dark bg-[#FBFDF2]' : 'border-gray-100'">
                            <label class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer">
                                <input class="w-4 h-4 accent-[#1A1A1A]" name="positions[]" type="checkbox" x-bind:value="stage.position"
                                    x-bind:checked="isSelected(stage.position)" x-on:change="toggle(stage.position)" />
                                <span class="w-8 h-8 shrink-0 rounded-lg bg-brand-dark text-brand-green text-[13px] font-black flex items-center justify-center"
                                    x-text="stage.position"></span>
                                <span class="min-w-0">
                                    <span class="block text-[13px] font-bold text-gray-800 truncate" x-text="stage.name"></span>
                                    <span class="block text-[11px]" x-show="stage.due" x-bind:class="stage.overdue ? 'text-red-500 font-bold' : 'text-gray-400'">
                                        <span x-text="stage.overdue ? 'متأخرة من' : 'موعدها'"></span>
                                        <span dir="ltr" x-text="stage.due"></span>
                                    </span>
                                </span>
                            </label>
                            <div class="w-24 sm:w-28 shrink-0 relative" x-show="type === 'per_item' && isSelected(stage.position)" x-cloak>
                                <input class="ui-input w-full h-9 pl-9 pr-2.5 text-[13px]" min="0" step="0.01" type="number" aria-label="السعر"
                                    x-bind:name="`prices[${stage.position}]`" x-model.number="prices[stage.position]"
                                    x-bind:disabled="type !== 'per_item' || ! isSelected(stage.position)" />
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[10.5px] text-gray-400">ج.م</span>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-[11px] font-semibold text-red-500 mt-2" x-cloak x-show="type === 'package' && selected.length > packageRemaining"
                    x-text="`رصيد عقد الصيانة ${packageRemaining} شمعة فقط.`"></p>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="flex items-center gap-2 text-sm font-bold text-gray-800 mb-3">
                    <span class="w-6 h-6 rounded-lg bg-brand-dark text-brand-green text-[12px] font-black flex items-center justify-center">2</span>
                    مين عمل الصيانة وإمتى
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.field label="الفني" name="technician_id" type="select" :options="$technicians->pluck('name', 'id')->all()"
                        :value="$config['technicianId']" placeholder="اختر الفني" required x-model="technicianId" />
                    <x-ui.field label="تاريخ الصيانة" name="serviced_at" type="date" :value="old('serviced_at', today()->toDateString())" required />
                </div>

                <details class="group mt-4 rounded-xl border border-gray-100" @if ($openExtras) open @endif>
                    <summary class="flex flex-wrap items-center gap-x-2 gap-y-0.5 px-3.5 py-2.5 cursor-pointer list-none text-[12px] font-bold text-gray-500 hover:text-gray-800">
                        <x-icon class="w-4 h-4 transition-transform group-open:-rotate-90" name="chevron-left" />
                        خيارات إضافية
                        <span class="font-normal text-gray-400">الموظفة، مكان الشمع، العمولة، ملاحظة</span>
                    </summary>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3.5 pt-1">
                        <x-ui.field label="الموظفة / المندوبة" name="sales_rep_id" type="select" :options="$salesReps->pluck('name', 'id')->all()"
                            :value="$config['salesRepId']" placeholder="بدون" x-model="salesRepId" hint="«بدون» = العملية باسم الفني فقط" />
                        <x-ui.field label="الشمع اتصرف من" name="stock_source" type="select"
                            :options="[StockSource::Installer->value => 'عهدة الفني', StockSource::Warehouse->value => 'المخزن الرئيسي']"
                            :value="$config['stockSource']" required x-model="stockSource" />
                        <div x-show="stockSource === 'warehouse'" x-cloak>
                            <x-ui.field label="المخزن" name="warehouse_id" type="select" :options="$warehouses"
                                :value="old('warehouse_id', array_key_first($warehouses))" placeholder="اختر المخزن" />
                        </div>
                        <div x-show="type === 'per_item'">
                            <label class="block text-[12px] font-bold text-gray-600 mb-1.5">عمولة الفني</label>
                            <input class="ui-input w-full h-11 px-3.5 text-[13px]" min="0" name="technician_commission" step="0.01" type="number"
                                x-model.number="technicianCommission" x-on:input="touched.technician = true" x-bind:disabled="type !== 'per_item'" />
                            <p class="text-[11px] text-gray-400 mt-1">بتتحسب لوحدها من عمولته لكل شمعة</p>
                        </div>
                        <div x-show="type === 'per_item' && salesRepId">
                            <label class="block text-[12px] font-bold text-gray-600 mb-1.5">عمولة الموظفة</label>
                            <input class="ui-input w-full h-11 px-3.5 text-[13px]" min="0" name="sales_rep_commission" step="0.01" type="number"
                                x-model.number="salesRepCommission" x-on:input="touched.salesRep = true" x-bind:disabled="type !== 'per_item' || ! salesRepId" />
                            <p class="text-[11px] text-gray-400 mt-1">بتتحسب لوحدها من عمولتها لكل شمعة</p>
                        </div>
                        <x-ui.field col="sm:col-span-2" label="ملاحظة" name="note" :value="old('note')" placeholder="اختياري" />
                    </div>
                </details>
            </section>

            {{-- فوق شريط التنقل السفلي في الموبايل --}}
            <div class="sticky bottom-[96px] lg:bottom-3 z-10 flex items-center gap-3 rounded-2xl bg-brand-dark text-white px-4 py-3 shadow-lg">
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold" x-text="`${selected.length} شمعة`"></p>
                    <p class="text-[11.5px] text-white/60" x-text="type === 'package'
                        ? `من عقد الصيانة — يتبقى ${Math.max(0, packageRemaining - selected.length)} شمعة`
                        : `الإجمالي ${total.toLocaleString('en')} ج.م`"></p>
                </div>
                <x-ui.btn icon="check" type="submit" x-bind:disabled="! valid">تمت الصيانة</x-ui.btn>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        // العمولات تُقترح تلقائيًا (سعر الموظف لكل شمعة × عدد الشمعات) حتى يعدّلها المستخدم يدويًا
        function maintenanceOperationForm(config) {
            return {
                ...config,
                touched: { technician: config.technicianCommission !== null, salesRep: config.salesRepCommission !== null },

                init() {
                    this.$watch('selected', () => this.suggest());
                    this.$watch('technicianId', () => { this.touched.technician = false; this.suggest(); });
                    this.$watch('salesRepId', () => { this.touched.salesRep = false; this.suggest(); });
                    this.suggest();
                },

                rateOf(userId) {
                    return Number(this.rates[userId] || 0);
                },

                suggest() {
                    if (! this.touched.technician) this.technicianCommission = this.rateOf(this.technicianId) * this.selected.length;
                    if (! this.touched.salesRep) this.salesRepCommission = this.rateOf(this.salesRepId) * this.selected.length;
                },

                isSelected(position) {
                    return this.selected.includes(position);
                },

                toggle(position) {
                    this.selected = this.isSelected(position)
                        ? this.selected.filter(p => p !== position)
                        : [...this.selected, position].sort((a, b) => a - b);
                },

                toggleAll() {
                    this.selected = this.selected.length === this.stages.length ? [] : this.stages.map(s => s.position);
                },

                get total() {
                    return this.selected.reduce((sum, position) => sum + (Number(this.prices[position]) || 0), 0);
                },

                get valid() {
                    if (! this.selected.length || ! this.technicianId) return false;
                    if (this.type === 'package') return this.selected.length <= this.packageRemaining;

                    return this.selected.every(position => this.prices[position] !== '' && this.prices[position] !== null);
                },
            };
        }
    </script>
@endpush
