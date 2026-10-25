{{-- محتوى اللوحة الجانبية لتفاصيل العميل (يُحقن داخل x-ui.drawer). كل البيانات محمّلة مسبقًا عبر
     Customer::loadProfile()، والحسابات هنا على مجموعات في الذاكرة بلا أي استعلام إضافي. --}}
@use('App\Domain\Customers\Enums\CustomerStatus')

@php
    $money = fn ($value) => number_format((float) $value, 2);
    $date = fn ($value) => $value?->format('Y-m-d') ?? '—';

    $contracts = $customer->contracts;
    $contractCodes = $contracts->pluck('code', 'id');
    $installments = $contracts->flatMap->installments;

    $totals = [
        'total' => (float) $contracts->sum('total'),
        'paid' => (float) $contracts->sum('paid'),
        'remaining' => (float) $contracts->sum('remaining'),
    ];
    $paidPercent = $totals['total'] > 0 ? min(100, round($totals['paid'] * 100 / $totals['total'])) : 0;

    $overdue = $installments->filter->isOverdue();
    $paidInstallments = $installments->filter->isPaid();
    // المتأخر له تنبيه مستقل، فالقسط القادم هو أقرب قسط لم يحل موعده بعد
    $nextDue = $installments->reject(fn ($installment) => $installment->isPaid() || $installment->isOverdue())->sortBy('due_date')->first();
    $legalContracts = $contracts->filter->isReferredToLegal();
    $upcomingReplacements = $contracts->flatMap->maintenanceItems
        ->filter(fn ($item) => $item->replacement_date)
        ->sortBy('replacement_date');

    $mainPhone = $customer->phones->firstWhere('is_primary', true) ?? $customer->phones->first();
    $address = collect([$customer->governorate, $customer->center, $customer->village])->filter()->join(' — ');

    $tabs = [
        'overview' => ['نظرة عامة', 'layout-grid', null],
        'contracts' => ['العقود', 'file-text', $contracts->count()],
        'installments' => ['الأقساط', 'calendar-clock', $installments->count()],
        'maintenance' => ['الصيانة', 'wrench', $upcomingReplacements->count()],
        'complaints' => ['الشكاوى', 'message-square-warning', $customer->complaints->count()],
        'gifts' => ['الهدايا', 'gift', $customer->gifts->count()],
    ];
@endphp

<div class="flex-1 min-h-0 flex flex-col" x-data="{ tab: 'overview' }">
    {{-- ===== الترويسة ===== --}}
    <header class="shrink-0 bg-brand-dark text-white px-5 pt-5 pb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <x-ui.avatar :name="$customer->name" size="w-14 h-14 text-lg" />
                <div class="min-w-0">
                    <h2 class="text-[17px] font-black leading-tight truncate">{{ $customer->name }}</h2>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                        <span class="text-[11px] font-bold text-white/60 font-mono" dir="ltr">{{ $customer->code }}</span>
                        {{-- شارات فاتحة على الخلفية الداكنة: النقطة وحدها تحمل لون الحالة --}}
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full {{ \App\Support\ToneMap::solid($customer->status->tone()) }}"></span>
                            {{ $customer->status->label() }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-bold">{{ $customer->origin->label() }}</span>
                    </div>
                </div>
            </div>
            <button class="w-9 h-9 shrink-0 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                type="button" aria-label="إغلاق" x-on:click="$store.drawer.close()">
                <x-icon class="w-[18px] h-[18px]" name="x" />
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-[12px] text-white/70">
            @if ($mainPhone)
                <a class="flex items-center gap-1.5 hover:text-white" href="tel:{{ $mainPhone->phone }}">
                    <x-icon class="w-3.5 h-3.5" name="phone" />
                    <span dir="ltr">{{ $mainPhone->phone }}</span>
                </a>
            @endif
            @if ($address)
                <span class="flex items-center gap-1.5">
                    <x-icon class="w-3.5 h-3.5" name="map-pin" />
                    {{ $address }}
                </span>
            @endif
            <span class="flex items-center gap-1.5">
                <x-icon class="w-3.5 h-3.5" name="calendar" />
                تاريخ التركيب <span dir="ltr">{{ $date($customer->registered_at) }}</span>
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4">
            @foreach ([['العقود', $contracts->count(), null], ['الإجمالي', $money($totals['total']), 'ج.م'], ['المدفوع', $money($totals['paid']), 'ج.م'], ['المتبقي', $money($totals['remaining']), 'ج.م']] as [$label, $value, $unit])
                <div @class(['rounded-xl px-2.5 py-2', 'bg-brand-green text-brand-dark' => $loop->last && $totals['remaining'] > 0, 'bg-white/[.08]' => ! ($loop->last && $totals['remaining'] > 0)])>
                    <p class="text-[10px] font-bold opacity-70">{{ $label }}</p>
                    <p class="text-[14px] font-black leading-tight truncate">{{ $value }}
                        @if ($unit)<span class="text-[9px] font-bold opacity-60">{{ $unit }}</span>@endif
                    </p>
                </div>
            @endforeach
        </div>

        @if ($totals['total'] > 0)
            <div class="mt-3">
                <div class="flex items-center justify-between text-[10px] font-bold text-white/60 mb-1">
                    <span>نسبة السداد</span>
                    <span>{{ $paidPercent }}%</span>
                </div>
                <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full bg-brand-green" style="width: {{ $paidPercent }}%"></div>
                </div>
            </div>
        @endif
    </header>

    {{-- ===== التبويبات ===== --}}
    <nav class="shrink-0 bg-white border-b border-gray-100 px-2 overflow-x-auto scrollbar-hide">
        <div class="flex min-w-max">
            @foreach ($tabs as $key => [$label, $icon, $count])
                <button class="relative flex items-center gap-1.5 px-2.5 py-3 text-[12px] font-bold whitespace-nowrap transition-colors"
                    type="button" x-on:click="tab = '{{ $key }}'"
                    x-bind:class="tab === '{{ $key }}' ? 'text-brand-dark' : 'text-gray-400 hover:text-gray-600'">
                    <x-icon class="w-4 h-4" :name="$icon" />
                    {{ $label }}
                    @if ($count !== null)
                        <span class="min-w-[18px] h-[18px] px-1 rounded-full text-[10px] flex items-center justify-center"
                            x-bind:class="tab === '{{ $key }}' ? 'bg-brand-dark text-white' : 'bg-gray-100 text-gray-500'">{{ $count }}</span>
                    @endif
                    <span class="absolute inset-x-2 -bottom-px h-[3px] rounded-full bg-brand-green" x-show="tab === '{{ $key }}'"></span>
                </button>
            @endforeach
        </div>
    </nav>

    <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 sm:px-5 py-4 space-y-3.5">

        {{-- ===================== نظرة عامة ===================== --}}
        <div class="space-y-3.5" x-show="tab === 'overview'">
            @if ($customer->status === CustomerStatus::Legal || $legalContracts->isNotEmpty())
                <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-3.5">
                    <x-icon class="w-5 h-5 text-red-500 mt-0.5" name="scale" />
                    <div class="text-[12px]">
                        <p class="font-bold text-red-700">العميل في الشؤون القانونية</p>
                        @if ($legalContracts->isNotEmpty())
                            <p class="text-red-600/80 mt-0.5">عقود محوَّلة: {{ $legalContracts->pluck('code')->join('، ') }}</p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($overdue->isNotEmpty())
                <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-3.5">
                    <x-icon class="w-5 h-5 text-amber-600 mt-0.5" name="alarm-clock" />
                    <div class="text-[12px]">
                        <p class="font-bold text-amber-800">{{ $overdue->count() }} قسط متأخر بإجمالي {{ $money($overdue->sum('amount')) }} ج.م</p>
                        <p class="text-amber-700/80 mt-0.5">أقدمها مستحق منذ <span dir="ltr">{{ $date($overdue->sortBy('due_date')->first()->due_date) }}</span></p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-3 gap-2.5">
                <div class="bg-white rounded-2xl border border-gray-100 p-3">
                    <p class="text-[10px] font-bold text-gray-400">أقساط محصّلة</p>
                    <p class="text-lg font-black text-gray-800">{{ $paidInstallments->count() }}<span class="text-[11px] text-gray-400"> / {{ $installments->count() }}</span></p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-3">
                    <p class="text-[10px] font-bold text-gray-400">القسط القادم</p>
                    @if ($nextDue)
                        <p class="text-[14px] font-black text-gray-800">{{ $money($nextDue->amount) }}</p>
                        <p class="text-[10px] text-gray-400" dir="ltr">{{ $date($nextDue->due_date) }}</p>
                    @else
                        <p class="text-[13px] font-bold text-gray-400 mt-1">لا يوجد</p>
                    @endif
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-3">
                    <p class="text-[10px] font-bold text-gray-400">الاستبدال القادم</p>
                    @php($nextReplacement = $upcomingReplacements->first(fn ($item) => $item->replacement_date->isFuture()) ?? $upcomingReplacements->last())
                    @if ($nextReplacement)
                        <p class="text-[13px] font-black text-gray-800 truncate">{{ $nextReplacement->product_name }}</p>
                        <p class="text-[10px] text-gray-400" dir="ltr">{{ $date($nextReplacement->replacement_date) }}</p>
                    @else
                        <p class="text-[13px] font-bold text-gray-400 mt-1">لا يوجد</p>
                    @endif
                </div>
            </div>

            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                    <x-icon class="w-4 h-4 text-gray-400" name="id-card" /> البيانات الأساسية
                </h3>
                <x-ui.detail-row label="كود العميل" :value="$customer->code" />
                <x-ui.detail-row label="الرقم القومي" :value="$customer->national_id ?? '—'" />
                <x-ui.detail-row label="الحالة" :value="$customer->status->label()" />
                <x-ui.detail-row label="نوع العميل" :value="$customer->origin->label() . ($customer->origin->value === 'external' ? ' (صيانة فقط)' : '')" />
                <x-ui.detail-row label="تاريخ التركيب" :value="$date($customer->registered_at)" />
                <x-ui.detail-row label="أُضيف إلى النظام"><span dir="ltr">{{ $customer->created_at?->format('Y-m-d H:i') }}</span></x-ui.detail-row>
                <x-ui.detail-row label="آخر تعديل"><span dir="ltr">{{ $customer->updated_at?->format('Y-m-d H:i') }}</span></x-ui.detail-row>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-3">
                    <x-icon class="w-4 h-4 text-gray-400" name="phone" /> أرقام الهاتف
                    <span class="text-[11px] font-normal text-gray-400">({{ $customer->phones->count() }})</span>
                </h3>
                <div class="space-y-2">
                    @forelse ($customer->phones as $phone)
                        <div class="flex items-center justify-between gap-2 rounded-xl bg-gray-50 px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="text-[13px] font-bold text-gray-800" dir="ltr">{{ $phone->phone }}</span>
                                @if ($phone->is_primary)
                                    <x-ui.badge tone="green">أساسي</x-ui.badge>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <a class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:bg-white hover:text-brand-dark"
                                    href="tel:{{ $phone->phone }}" title="اتصال"><x-icon class="w-4 h-4" name="phone-call" /></a>
                                <a class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:bg-white hover:text-green-600"
                                    href="https://wa.me/2{{ $phone->phone }}" target="_blank" rel="noopener" title="واتساب"><x-icon class="w-4 h-4" name="message-circle" /></a>
                            </div>
                        </div>
                    @empty
                        <p class="text-[12px] text-gray-400">لا توجد أرقام مسجلة.</p>
                    @endforelse
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                    <x-icon class="w-4 h-4 text-gray-400" name="map-pin" /> العنوان
                </h3>
                <x-ui.detail-row label="المحافظة" :value="$customer->governorate ?? '—'" />
                <x-ui.detail-row label="المركز" :value="$customer->center ?? '—'" />
                <x-ui.detail-row label="القرية" :value="$customer->village ?? '—'" />
                <x-ui.detail-row label="العنوان التفصيلي" :value="$customer->detailed_address ?? '—'" />
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                    <x-icon class="w-4 h-4 text-gray-400" name="notebook-pen" /> ملاحظات
                </h3>
                <p class="text-[12.5px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $customer->notes ?: 'لا توجد ملاحظات مسجلة على هذا العميل.' }}</p>
            </section>
        </div>

        {{-- ===================== العقود ===================== --}}
        <div class="space-y-3" x-cloak x-show="tab === 'contracts'">
            @forelse ($contracts as $contract)
                @php($contractPaid = (float) $contract->total > 0 ? min(100, round((float) $contract->paid * 100 / (float) $contract->total)) : 100)
                <article class="bg-white rounded-2xl border border-gray-100 overflow-hidden" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                    <button class="w-full flex items-center justify-between gap-3 p-4 text-right" type="button" x-on:click="open = ! open">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[13px] font-black text-gray-800 font-mono" dir="ltr">{{ $contract->code }}</span>
                                <x-ui.badge :tone="$contract->kind->tone()">{{ $contract->kind->label() }}</x-ui.badge>
                                <x-ui.badge :tone="$contract->type->tone()">{{ $contract->type->label() }}</x-ui.badge>
                                @if ($contract->isReferredToLegal())
                                    <x-ui.badge tone="red">شؤون قانونية</x-ui.badge>
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">بتاريخ <span dir="ltr">{{ $date($contract->contract_date) }}</span> — {{ $contract->items->count() }} بند</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="text-left">
                                <p class="text-[13px] font-black text-gray-800">{{ $money($contract->total) }}</p>
                                <p @class(['text-[10px] font-bold', 'text-red-500' => $contract->remaining > 0, 'text-green-600' => $contract->remaining <= 0])>
                                    {{ $contract->remaining > 0 ? 'متبقي ' . $money($contract->remaining) : 'مسدّد بالكامل' }}</p>
                            </div>
                            <x-icon class="w-4 h-4 text-gray-400 transition-transform" name="chevron-down" x-bind:class="open && 'rotate-180'" />
                        </div>
                    </button>

                    <div class="border-t border-gray-100 p-4 space-y-4" x-show="open" x-collapse>
                        <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-brand-green" style="width: {{ $contractPaid }}%"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-x-5">
                            <x-ui.detail-row label="تاريخ التعاقد" :value="$date($contract->contract_date)" />
                            <x-ui.detail-row label="تاريخ التركيب" :value="$date($contract->installation_date)" />
                            <x-ui.detail-row label="موعد التحصيل" :value="$date($contract->collection_date)" />
                            <x-ui.detail-row label="موعد الصيانة" :value="$date($contract->maintenance_date)" />
                            <x-ui.detail-row label="مندوب المبيعات" :value="$contract->salesRep?->name ?? '—'" />
                            <x-ui.detail-row label="فني التركيب" :value="$contract->installer?->name ?? '—'" />
                            <x-ui.detail-row label="المخزن" :value="$contract->warehouse?->name ?? '—'" />
                            <x-ui.detail-row label="مصدر الصرف" :value="$contract->stock_source?->label() ?? '—'" />
                            @if ($contract->isReferredToLegal())
                                <x-ui.detail-row label="تاريخ التحويل للقانونية" :value="$date($contract->legal_referred_at)" />
                            @endif
                        </div>

                        @if ($contract->items->isNotEmpty())
                            <div>
                                <p class="text-[11px] font-bold text-gray-500 mb-1.5">البنود</p>
                                <div class="rounded-xl border border-gray-100 overflow-hidden">
                                    <table class="w-full text-[12px]">
                                        <thead class="bg-gray-50 text-[10.5px] text-gray-500">
                                            <tr>
                                                <th class="px-3 py-2 text-right font-bold">المنتج</th>
                                                <th class="px-3 py-2 text-center font-bold">الكمية</th>
                                                <th class="px-3 py-2 text-left font-bold">السعر</th>
                                                <th class="px-3 py-2 text-left font-bold">الإجمالي</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach ($contract->items as $item)
                                                <tr>
                                                    <td class="px-3 py-2 font-bold text-gray-700">{{ $item->product_name }}</td>
                                                    <td class="px-3 py-2 text-center">{{ $item->quantity }}</td>
                                                    <td class="px-3 py-2 text-left">{{ $money($item->unit_price) }}</td>
                                                    <td class="px-3 py-2 text-left font-bold">{{ $money($item->total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div @class(['rounded-xl bg-gray-50 p-3 grid gap-2 text-center', 'grid-cols-6' => $contract->isInstallment(), 'grid-cols-5' => ! $contract->isInstallment()])>
                            @foreach ($contract->moneyBreakdown() as $label => $value)
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400">{{ $label }}</p>
                                    <p @class(['text-[12.5px] font-black', 'text-red-600' => $loop->last && $value > 0, 'text-gray-800' => ! ($loop->last && $value > 0)])>{{ $money($value) }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ($contract->isInstallment())
                            <div class="grid grid-cols-2 gap-x-5">
                                <x-ui.detail-row label="عدد الأقساط" :value="$contract->installments_count ?? $contract->installments->count()" />
                                <x-ui.detail-row label="قيمة القسط" :value="$money($contract->installment_amount)" />
                                <x-ui.detail-row label="كل" :value="($contract->installment_period_months ?? 1) . ' شهر'" />
                                <x-ui.detail-row label="أول استحقاق" :value="$date($contract->first_due_date)" />
                            </div>
                        @endif

                        @if ($contract->maintenancePlan || $contract->maintenanceItems->isNotEmpty())
                            <div>
                                <p class="text-[11px] font-bold text-gray-500 mb-1.5">خطة الصيانة: {{ $contract->maintenancePlan?->name ?? '—' }}</p>
                                <div class="space-y-1.5">
                                    @foreach ($contract->maintenanceItems as $item)
                                        <div class="flex items-center justify-between gap-2 rounded-lg bg-teal-50/60 px-3 py-1.5 text-[12px]">
                                            <span class="font-bold text-gray-700">{{ $item->product_name }}</span>
                                            <span class="text-gray-500">{{ $item->interval_months ? 'كل ' . $item->interval_months . ' شهر — ' : '' }}<span dir="ltr">{{ $date($item->replacement_date) }}</span></span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($contract->notes)
                            <p class="text-[12px] text-gray-600 rounded-xl bg-amber-50/60 px-3 py-2 whitespace-pre-line">{{ $contract->notes }}</p>
                        @endif

                        <div class="grid grid-cols-2 gap-2">
                            <x-ui.btn icon="eye" size="sm" variant="soft"
                                x-on:click="$store.drawer.show({{ Js::from(route('contracts.panel', $contract)) }})">معاينة العقد</x-ui.btn>
                            <x-ui.btn icon="external-link" size="sm" variant="soft" :href="route('contracts.show', $contract)">فتح صفحة العقد</x-ui.btn>
                        </div>
                    </div>
                </article>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100">
                    <x-ui.empty-state icon="file-x" title="لا توجد عقود" message="لم يُسجَّل أي عقد لهذا العميل بعد." />
                </div>
            @endforelse
        </div>

        {{-- ===================== الأقساط ===================== --}}
        <div class="space-y-3" x-cloak x-show="tab === 'installments'">
            @if ($installments->isNotEmpty())
                <div class="grid grid-cols-3 gap-2.5">
                    @foreach ([['محصّلة', $paidInstallments, 'text-green-700'], ['متأخرة', $overdue, 'text-red-600'], ['متبقية', $installments->reject->isPaid(), 'text-gray-800']] as [$label, $group, $color])
                        <div class="bg-white rounded-2xl border border-gray-100 p-3">
                            <p class="text-[10px] font-bold text-gray-400">{{ $label }}</p>
                            <p class="text-lg font-black {{ $color }}">{{ $group->count() }}</p>
                            <p class="text-[10px] text-gray-400">{{ $money($group->sum('amount')) }} ج.م</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @forelse ($contracts->filter(fn ($contract) => $contract->installments->isNotEmpty()) as $contract)
                <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50/70 border-b border-gray-100">
                        <span class="text-[12px] font-black text-gray-800 font-mono" dir="ltr">{{ $contract->code }}</span>
                        <span class="text-[11px] text-gray-500">{{ $contract->installments->filter->isPaid()->count() }} / {{ $contract->installments->count() }} محصّل</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @foreach ($contract->installments as $installment)
                            @php($state = $installment->status())
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-7 h-7 shrink-0 rounded-lg bg-gray-100 text-[11px] font-black text-gray-600 flex items-center justify-center">{{ $installment->number }}</span>
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-bold text-gray-700">استحقاق <span dir="ltr">{{ $date($installment->due_date) }}</span></p>
                                        @if ($installment->isPaid())
                                            <p class="text-[10.5px] text-gray-400 truncate">حُصّل <span dir="ltr">{{ $date($installment->paid_at) }}</span>{{ $installment->collectedBy ? ' — بواسطة ' . $installment->collectedBy->name : '' }}</p>
                                        @endif
                                        @if ($installment->note)
                                            <p class="text-[10.5px] text-gray-400 truncate">{{ $installment->note }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[12.5px] font-black text-gray-800">{{ $money($installment->paid_amount ?? $installment->amount) }}</span>
                                    <x-ui.badge :tone="$state['tone']">{{ $state['label'] }}</x-ui.badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100">
                    <x-ui.empty-state icon="calendar-x" title="لا توجد أقساط" message="عقود هذا العميل نقدية أو لم تُسجَّل له عقود بعد." />
                </div>
            @endforelse
        </div>

        {{-- ===================== الصيانة ===================== --}}
        <div class="space-y-3" x-cloak x-show="tab === 'maintenance'">
            @if ($upcomingReplacements->isNotEmpty())
                <section class="bg-white rounded-2xl border border-gray-100 p-4">
                    <h3 class="text-[13px] font-bold text-gray-800 mb-2.5">مواعيد استبدال القطع</h3>
                    <div class="space-y-1.5">
                        @foreach ($upcomingReplacements as $item)
                            <div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-[12px]">
                                <span class="font-bold text-gray-700">{{ $item->product_name }}</span>
                                <span class="flex items-center gap-2">
                                    <span class="text-gray-500" dir="ltr">{{ $date($item->replacement_date) }}</span>
                                    @if ($item->replacement_date->isPast())
                                        <x-ui.badge tone="red">فات موعده</x-ui.badge>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($upcomingReplacements->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100">
                    <x-ui.empty-state icon="wrench" title="لا توجد مواعيد صيانة" message="لا توجد خطة صيانة مرتبطة بعقود هذا العميل." />
                </div>
            @endif
        </div>

        {{-- ===================== الشكاوى ===================== --}}
        <div class="space-y-3" x-cloak x-show="tab === 'complaints'">
            @forelse ($customer->complaints as $complaint)
                <article class="bg-white rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[13px] font-bold text-gray-800">شكوى بتاريخ <span dir="ltr">{{ $date($complaint->complaint_date) }}</span></p>
                            <p class="text-[11px] text-gray-400 mt-0.5">عقد <span dir="ltr">{{ $contractCodes[$complaint->sales_contract_id] ?? '—' }}</span> — الفني: {{ $complaint->technician?->name ?? '—' }}</p>
                        </div>
                        <x-ui.badge :tone="$complaint->resolved ? 'green' : 'amber'" dot>{{ $complaint->resolved ? 'تم الحل' : 'مفتوحة' }}</x-ui.badge>
                    </div>
                    @if ($complaint->note)
                        <p class="text-[12px] text-gray-600 mt-2 whitespace-pre-line">{{ $complaint->note }}</p>
                    @endif
                    <div class="mt-3 rounded-xl bg-gray-50 p-3 text-[12px] space-y-1">
                        <div class="flex justify-between"><span class="text-gray-500">رسوم الزيارة</span><span class="font-bold">{{ $money($complaint->visit_fee) }}</span></div>
                        @foreach ($complaint->parts as $part)
                            <div class="flex justify-between"><span class="text-gray-500">{{ $part->product?->name ?? 'قطعة' }} × {{ $part->quantity }}</span><span class="font-bold">{{ $money($part->price) }}</span></div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100">
                    <x-ui.empty-state icon="message-square-off" title="لا توجد شكاوى" message="لم يُسجِّل هذا العميل أي شكوى." />
                </div>
            @endforelse
        </div>

        {{-- ===================== الهدايا ===================== --}}
        <div class="space-y-3" x-cloak x-show="tab === 'gifts'">
            @forelse ($customer->gifts as $gift)
                <article class="flex items-start justify-between gap-3 bg-white rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-start gap-3">
                        <span class="w-9 h-9 shrink-0 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center"><x-icon class="w-[18px] h-[18px]" name="gift" /></span>
                        <div>
                            <p class="text-[13px] font-bold text-gray-800">{{ $gift->product?->name ?? '—' }} <span class="text-gray-400 font-normal">× {{ $gift->quantity }}</span></p>
                            <p class="text-[11px] text-gray-400 mt-0.5">سلّمها: {{ $gift->technician?->name ?? '—' }} — {{ $gift->stock_source?->label() }}</p>
                            @if ($gift->note)
                                <p class="text-[11.5px] text-gray-500 mt-1">{{ $gift->note }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="text-[11px] text-gray-500 shrink-0" dir="ltr">{{ $date($gift->gift_date) }}</span>
                </article>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100">
                    <x-ui.empty-state icon="gift" title="لا توجد هدايا" message="لم تُصرف أي هدية لهذا العميل." />
                </div>
            @endforelse
        </div>
    </div>

    {{-- ===== الإجراءات ===== --}}
    <footer class="shrink-0 bg-white border-t border-gray-100 px-4 sm:px-5 py-3 grid grid-cols-3 gap-2">
        <x-ui.btn icon="pencil" size="sm" :href="route('customers.edit', $customer)">تعديل</x-ui.btn>
        <x-ui.btn icon="file-text" size="sm" variant="soft" :href="route('contracts.index', ['customer_id' => $customer->id])">كل العقود</x-ui.btn>
        <x-ui.btn icon="maximize-2" size="sm" variant="soft" :href="route('customers.show', $customer)">الصفحة الكاملة</x-ui.btn>
    </footer>
</div>
