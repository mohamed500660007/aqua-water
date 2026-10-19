@extends('layouts.app')

@section('title', 'عقد صيانة جملة')
@section('page-title', 'عقد صيانة جملة')
@section('page-subtitle', 'إضافة باكيج شمعات (الأساسية + الهدايا) لحساب العميل وعمولة العقد الموحّدة')

@section('content')
    <x-ui.page-header icon="package-check" subtitle="إضافة باكيج شمعات (الأساسية + الهدايا) لحساب العميل وعمولة العقد الموحّدة" title="عقد صيانة جملة">
        <x-slot:actions>
            @if ($device)
                <x-ui.btn icon="repeat" size="sm" variant="soft" :href="route('maintenance.packages.create')">جهاز آخر</x-ui.btn>
            @endif
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('maintenance.packages.index')">رجوع للعقود</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @if (! $device)
        @include('pages.maintenance.partials.device-picker', ['route' => 'maintenance.packages.create'])
    @else
        @php
            $rates = $technicians->pluck('maintenance_commission', 'id')->map(fn ($rate) => (float) $rate)
                ->union($salesReps->pluck('maintenance_commission', 'id')->map(fn ($rate) => (float) $rate));
            $contractOptions = $maintenanceContracts->mapWithKeys(fn ($contract) => [
                $contract->id => $contract->code . ' — ' . number_format((float) $contract->total, 2) . ' ج.م (' . $contract->type->label() . ')',
            ])->all();
        @endphp

        <form action="{{ route('maintenance.packages.store') }}" method="POST"
            x-data="{ candles: @js((int) old('candles', 7)), gifts: @js((int) old('gift_candles', 0)),
                technicianId: @js((string) old('technician_id', $device->installer_id ?? '')), salesRepId: @js((string) old('sales_rep_id', $device->sales_rep_id ?? '')), rates: @js($rates) }">
            @csrf
            <input name="sales_contract_id" type="hidden" value="{{ $device->id }}" />

            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
                    <ul class="list-disc pr-5 space-y-1">
                        @foreach ($errors->all() as $message)
                            <li class="text-[12px] text-red-500">{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
                <div class="lg:col-span-2 space-y-4 lg:space-y-5">
                    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
                        <x-ui.avatar :name="$device->customer?->name ?? '—'" size="w-12 h-12" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-gray-800 truncate">{{ $device->customer?->name }}</p>
                            <p class="text-[12px] text-gray-400 truncate">
                                <span dir="ltr">{{ $device->code }}</span> · {{ $device->items->pluck('product_name')->join('، ') }}
                            </p>
                        </div>
                    </section>

                    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h2 class="text-sm font-bold text-gray-800 mb-1">الباكيج</h2>
                        <p class="text-[12px] text-gray-400 mb-4">الشمعات تُضاف رصيدًا لحساب العميل، وتُخصم من المخزون مع كل زيارة فعلية.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-ui.field label="شمعات الباكيج" name="candles" type="number" min="1" max="50" required x-model.number="candles"
                                :value="old('candles', 7)" hint="باكيج المراحل السبع الكاملة = 7" />
                            <x-ui.field label="شمعات هدايا العقد" name="gift_candles" type="number" min="0" max="100" x-model.number="gifts"
                                :value="old('gift_candles', 0)" hint="شمعات إضافية مجانية تُفرّغ في حساب العميل" />
                            <x-ui.field col="sm:col-span-2" label="عقد الصيانة المالي" name="maintenance_contract_id" type="select" :options="$contractOptions"
                                :value="old('maintenance_contract_id')" placeholder="{{ $contractOptions ? 'اختر العقد الذي سُجّل فيه المبلغ' : 'لا يوجد عقد صيانة مالي لهذا العميل' }}"
                                hint="المبلغ (كاش أو أقساط) يُسجَّل في عقد الصيانة نفسه — لا تُدخل أسعار للشمعات هنا" />
                            <x-ui.field label="تاريخ بدء العقد" name="started_at" type="date" :value="old('started_at', today()->toDateString())" required />
                            <x-ui.field label="ملاحظة" name="note" :value="old('note')" placeholder="اختياري" />
                        </div>
                    </section>

                    <details class="group bg-white rounded-2xl border border-gray-100 shadow-sm"
                        @if ($errors->hasAny(['technician_id', 'sales_rep_id', 'technician_commission', 'sales_rep_commission'])) open @endif>
                        <summary class="flex flex-wrap items-center gap-x-2 gap-y-0.5 p-5 cursor-pointer list-none">
                            <x-icon class="w-4 h-4 text-gray-400 transition-transform group-open:-rotate-90" name="chevron-left" />
                            <span class="text-sm font-bold text-gray-800">العمولة</span>
                            <span class="text-[12px] text-gray-400">بتتحسب لوحدها للفني والموظفة — افتح لو عايز تعدّلها</span>
                        </summary>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 pb-5">
                            <x-ui.field label="الفني" name="technician_id" type="select" :options="$technicians->pluck('name', 'id')->all()"
                                :value="old('technician_id', $device->installer_id)" placeholder="بدون" x-model="technicianId" />
                            <x-ui.field label="عمولة الفني" name="technician_commission" type="number" min="0" step="0.01"
                                :value="old('technician_commission')" x-bind:placeholder="`المقترح ${(rates[technicianId] || 0) * (candles || 0)}`" />
                            <x-ui.field label="الموظفة / المندوبة" name="sales_rep_id" type="select" :options="$salesReps->pluck('name', 'id')->all()"
                                :value="old('sales_rep_id', $device->sales_rep_id)" placeholder="بدون" x-model="salesRepId"
                                hint="«بدون» = العقد باسم الفني فقط" />
                            <div x-show="salesRepId">
                                <x-ui.field label="عمولة الموظفة" name="sales_rep_commission" type="number" min="0" step="0.01"
                                    :value="old('sales_rep_commission')" x-bind:placeholder="`المقترح ${(rates[salesRepId] || 0) * (candles || 0)}`" />
                            </div>
                        </div>
                    </details>
                </div>

                <aside class="lg:col-span-1">
                    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-2 space-y-4">
                        <h2 class="text-sm font-bold text-gray-800">رصيد العميل بعد التسجيل</h2>
                        <div class="rounded-2xl bg-brand-dark text-white p-4">
                            <p class="text-[11px] text-white/60 mb-1">إجمالي الشمعات</p>
                            <p class="text-3xl font-black text-brand-green" x-text="(candles || 0) + (gifts || 0)"></p>
                            <p class="text-[11px] text-white/60 mt-1" x-text="`${candles || 0} أساسية + ${gifts || 0} هدية`"></p>
                        </div>
                        <x-ui.btn class="w-full" icon="check" type="submit">تسجيل العقد</x-ui.btn>
                    </section>
                </aside>
            </div>
        </form>
    @endif
@endsection
