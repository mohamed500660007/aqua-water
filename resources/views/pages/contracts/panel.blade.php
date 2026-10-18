{{-- محتوى لوحة معاينة العقد (يُحقن داخل x-ui.drawer). كل البيانات محمّلة مسبقًا عبر
     SalesContract::loadDetails()، والحسابات هنا على مجموعات في الذاكرة بلا أي استعلام إضافي. --}}
@php
    $money = fn ($value) => number_format((float) $value, 2);
    $date = fn ($value) => $value?->format('Y-m-d') ?? '—';

    $customer = $contract->customer;
    $installments = $contract->installments;
    $paidInstallments = $installments->filter->isPaid();
    $overdue = $installments->filter->isOverdue();
    // المتأخر له تنبيه مستقل، فالقسط القادم هو أقرب قسط لم يحل موعده بعد
    $nextDue = $installments->reject(fn ($installment) => $installment->isPaid() || $installment->isOverdue())->sortBy('due_date')->first();
    $paidPercent = (float) $contract->total > 0 ? min(100, round((float) $contract->paid * 100 / (float) $contract->total)) : 100;
    $phone = $customer?->phones->firstWhere('is_primary', true) ?? $customer?->phones->first();

    $tabs = array_filter([
        'overview' => ['نظرة عامة', 'layout-grid', null],
        'items' => $contract->isMaintenance() && $contract->items->isEmpty() ? null : ['البنود', 'package', $contract->items->count()],
        'installments' => $contract->isInstallment() ? ['الأقساط', 'calendar-clock', $installments->count()] : null,
        'maintenance' => ['الصيانة', 'wrench', $contract->maintenanceItems->count()],
        'complaints' => ['الشكاوى', 'message-square-warning', $contract->complaints->count()],
    ]);
@endphp

<div class="flex-1 min-h-0 flex flex-col" x-data="{ tab: 'overview' }">
    {{-- ===== الترويسة ===== --}}
    <header class="shrink-0 bg-brand-dark text-white px-5 pt-5 pb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-14 h-14 shrink-0 rounded-2xl bg-brand-green text-brand-dark flex items-center justify-center">
                    <x-icon class="w-7 h-7" :name="$contract->isMaintenance() ? 'shield-check' : 'file-text'" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-[17px] font-black leading-tight font-mono" dir="ltr">{{ $contract->code }}</h2>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                        <span class="inline-flex items-center rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-bold">{{ $contract->kind->label() }}</span>
                        <span class="inline-flex items-center rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-bold">{{ $contract->type->label() }}</span>
                        @if ($contract->isReferredToLegal())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500 px-2.5 py-0.5 text-[11px] font-bold">
                                <x-icon class="w-3 h-3" name="scale" /> شؤون قانونية
                            </span>
                        @elseif ((float) $contract->remaining <= 0)
                            <span class="inline-flex items-center rounded-full bg-brand-green text-brand-dark px-2.5 py-0.5 text-[11px] font-bold">مسدّد بالكامل</span>
                        @endif
                    </div>
                </div>
            </div>
            <button class="w-9 h-9 shrink-0 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                type="button" aria-label="إغلاق" x-on:click="$store.drawer.close()">
                <x-icon class="w-[18px] h-[18px]" name="x" />
            </button>
        </div>

        {{-- العميل: الضغط عليه يبدّل اللوحة إلى ملف العميل --}}
        @if ($customer)
            <button class="mt-3 w-full flex items-center justify-between gap-3 rounded-xl bg-white/[.06] hover:bg-white/10 px-3 py-2 text-right transition-colors"
                type="button" x-on:click="$store.drawer.show({{ Js::from(route('customers.panel', $customer)) }})">
                <span class="flex items-center gap-2.5 min-w-0">
                    <x-ui.avatar :name="$customer->name" size="w-8 h-8 text-[11px]" />
                    <span class="min-w-0">
                        <span class="block text-[13px] font-bold truncate">{{ $customer->name }}</span>
                        <span class="block text-[10.5px] text-white/60">
                            <span dir="ltr">{{ $customer->code }}</span>
                            @if ($phone) — <span dir="ltr">{{ $phone->phone }}</span>@endif
                        </span>
                    </span>
                </span>
                <span class="flex items-center gap-1 text-[11px] font-bold text-white/70 shrink-0">ملف العميل <x-icon class="w-3.5 h-3.5" name="chevron-left" /></span>
            </button>
        @else
            <p class="mt-3 rounded-xl bg-white/[.06] px-3 py-2 text-[12px] text-white/60">العميل غير متاح (محذوف من النظام).</p>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3">
            @foreach ([
                ['الإجمالي', $money($contract->total), 'ج.م', false],
                ['المدفوع', $money($contract->paid), 'ج.م', false],
                ['المتبقي', $money($contract->remaining), 'ج.م', (float) $contract->remaining > 0],
                $contract->isInstallment()
                    ? ['الأقساط المحصّلة', $paidInstallments->count() . ' / ' . $installments->count(), null, false]
                    : ['تاريخ العقد', $date($contract->contract_date), null, false],
            ] as [$label, $value, $unit, $highlight])
                <div @class(['rounded-xl px-2.5 py-2', 'bg-brand-green text-brand-dark' => $highlight, 'bg-white/[.08]' => ! $highlight])>
                    <p class="text-[10px] font-bold opacity-70">{{ $label }}</p>
                    <p class="text-[14px] font-black leading-tight truncate" @if ($unit === null) dir="ltr" @endif>{{ $value }}
                        @if ($unit)<span class="text-[9px] font-bold opacity-60">{{ $unit }}</span>@endif
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            <div class="flex items-center justify-between text-[10px] font-bold text-white/60 mb-1">
                <span>نسبة السداد</span>
                <span>{{ $paidPercent }}%</span>
            </div>
            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-brand-green" style="width: {{ $paidPercent }}%"></div>
            </div>
        </div>
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
            @if ($contract->isReferredToLegal())
                <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-3.5">
                    <x-icon class="w-5 h-5 text-red-500 mt-0.5" name="scale" />
                    <div class="text-[12px]">
                        <p class="font-bold text-red-700">العقد محوَّل للشؤون القانونية</p>
                        <p class="text-red-600/80 mt-0.5">منذ <span dir="ltr">{{ $date($contract->legal_referred_at) }}</span> — متبقٍّ عليه {{ $money($contract->remaining) }} ج.م</p>
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

            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                    <x-icon class="w-4 h-4 text-gray-400" name="calendar-days" /> المواعيد
                </h3>
                <div class="grid grid-cols-2 gap-x-5">
                    <x-ui.detail-row label="تاريخ التعاقد" :value="$date($contract->contract_date)" />
                    <x-ui.detail-row label="تاريخ التركيب" :value="$date($contract->installation_date)" />
                    <x-ui.detail-row label="موعد التحصيل" :value="$date($contract->collection_date)" />
                    <x-ui.detail-row label="موعد الصيانة" :value="$date($contract->maintenance_date)" />
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                    <x-icon class="w-4 h-4 text-gray-400" name="users" /> فريق العمل والمخزون
                </h3>
                <div class="grid grid-cols-2 gap-x-5">
                    <x-ui.detail-row label="مندوب المبيعات" :value="$contract->salesRep?->name ?? '—'" />
                    <x-ui.detail-row label="فني التركيب" :value="$contract->installer?->name ?? '—'" />
                    <x-ui.detail-row label="المخزن" :value="$contract->warehouse?->name ?? '—'" />
                    <x-ui.detail-row label="مصدر الصرف" :value="$contract->stock_source?->label() ?? '—'" />
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-3">
                    <x-icon class="w-4 h-4 text-gray-400" name="banknote" /> الحساب
                </h3>
                <div class="rounded-xl bg-gray-50 p-3 grid grid-cols-5 gap-2 text-center">
                    @foreach ([['قبل الخصم', $contract->subtotal], ['الخصم', $contract->discount], ['الإجمالي', $contract->total], ['المدفوع', $contract->paid], ['المتبقي', $contract->remaining]] as [$label, $value])
                        <div>
                            <p class="text-[10px] font-bold text-gray-400">{{ $label }}</p>
                            <p @class(['text-[12.5px] font-black', 'text-red-600' => $loop->last && $value > 0, 'text-gray-800' => ! ($loop->last && $value > 0)])>{{ $money($value) }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($contract->isInstallment())
                    <div class="grid grid-cols-2 gap-x-5 mt-2">
                        <x-ui.detail-row label="عدد الأقساط" :value="$contract->installments_count ?? $installments->count()" />
                        <x-ui.detail-row label="قيمة القسط" :value="$money($contract->installment_amount)" />
                        <x-ui.detail-row label="القسط كل" :value="($contract->installment_period_months ?? 1) . ' شهر'" />
                        <x-ui.detail-row label="أول استحقاق" :value="$date($contract->first_due_date)" />
                        <x-ui.detail-row label="القسط القادم">
                            @if ($nextDue)
                                {{ $money($nextDue->amount) }} — <span dir="ltr">{{ $date($nextDue->due_date) }}</span>
                            @else
                                —
                            @endif
                        </x-ui.detail-row>
                        <x-ui.detail-row label="المتأخر" :value="$overdue->isEmpty() ? 'لا يوجد' : $money($overdue->sum('amount'))" />
                    </div>
                @endif
            </section>

            @if ($contract->notes)
                <section class="bg-white rounded-2xl border border-gray-100 p-4">
                    <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                        <x-icon class="w-4 h-4 text-gray-400" name="notebook-pen" /> ملاحظات
                    </h3>
                    <p class="text-[12.5px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $contract->notes }}</p>
                </section>
            @endif
        </div>

        {{-- ===================== البنود ===================== --}}
        @isset($tabs['items'])
            <div class="space-y-3" x-cloak x-show="tab === 'items'">
                <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <table class="w-full text-[12.5px]">
                        <thead class="bg-gray-50 text-[11px] text-gray-500">
                            <tr>
                                <th class="px-4 py-2.5 text-right font-bold">المنتج</th>
                                <th class="px-3 py-2.5 text-center font-bold">الكمية</th>
                                <th class="px-3 py-2.5 text-left font-bold">سعر الوحدة</th>
                                <th class="px-4 py-2.5 text-left font-bold">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($contract->items as $item)
                                <tr>
                                    <td class="px-4 py-2.5 font-bold text-gray-700">{{ $item->product_name }}</td>
                                    <td class="px-3 py-2.5 text-center">{{ $item->quantity }}</td>
                                    <td class="px-3 py-2.5 text-left">{{ $money($item->unit_price) }}</td>
                                    <td class="px-4 py-2.5 text-left font-bold">{{ $money($item->total) }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-6 text-center text-gray-400" colspan="4">لا توجد بنود</td></tr>
                            @endforelse
                        </tbody>
                        @if ($contract->items->isNotEmpty())
                            <tfoot class="bg-gray-50 text-[12px]">
                                <tr>
                                    <td class="px-4 py-2 font-bold text-gray-600">قبل الخصم</td>
                                    <td class="px-3 py-2 text-center font-bold">{{ $contract->items->sum('quantity') }}</td>
                                    <td></td>
                                    <td class="px-4 py-2 text-left font-bold">{{ $money($contract->subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-1.5 text-gray-500" colspan="3">الخصم</td>
                                    <td class="px-4 py-1.5 text-left text-gray-600">− {{ $money($contract->discount) }}</td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td class="px-4 py-2 font-black text-gray-800" colspan="3">الإجمالي</td>
                                    <td class="px-4 py-2 text-left font-black text-gray-800">{{ $money($contract->total) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </section>
            </div>
        @endisset

        {{-- ===================== الأقساط ===================== --}}
        @isset($tabs['installments'])
            <div class="space-y-3" x-cloak x-show="tab === 'installments'">
                <div class="grid grid-cols-3 gap-2.5">
                    @foreach ([['محصّلة', $paidInstallments, 'text-green-700'], ['متأخرة', $overdue, 'text-red-600'], ['متبقية', $installments->reject->isPaid(), 'text-gray-800']] as [$label, $group, $color])
                        <div class="bg-white rounded-2xl border border-gray-100 p-3">
                            <p class="text-[10px] font-bold text-gray-400">{{ $label }}</p>
                            <p class="text-lg font-black {{ $color }}">{{ $group->count() }}</p>
                            <p class="text-[10px] text-gray-400">{{ $money($group->sum('amount')) }} ج.م</p>
                        </div>
                    @endforeach
                </div>

                <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden divide-y divide-gray-50">
                    @forelse ($installments as $installment)
                        @php($state = $installment->status())
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-7 h-7 shrink-0 rounded-lg bg-gray-100 text-[11px] font-black text-gray-600 flex items-center justify-center">{{ $installment->number }}</span>
                                <div class="min-w-0">
                                    <p class="text-[12px] font-bold text-gray-700">استحقاق <span dir="ltr">{{ $date($installment->due_date) }}</span></p>
                                    @if ($installment->isPaid())
                                        <p class="text-[10.5px] text-gray-400 truncate">حُصّل <span dir="ltr">{{ $date($installment->paid_at) }}</span>{{ $installment->collectedBy ? ' — بواسطة ' . $installment->collectedBy->name : '' }}</p>
                                    @endif
                                    @if ($installment->split_from_id)
                                        <p class="text-[10.5px] text-gray-400">متبقٍّ من دفعة جزئية</p>
                                    @endif
                                    @if ($installment->note)
                                        <p class="text-[10.5px] text-gray-400 truncate">{{ $installment->note }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[12.5px] font-black text-gray-800">{{ $money($installment->paid_amount ?? $installment->amount) }}</span>
                                <x-ui.badge :tone="$state['tone']">{{ $state['label'] }}</x-ui.badge>
                                @if ($installment->isPaid())
                                    <a class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-brand-dark"
                                        href="{{ route('installments.receipt', $installment) }}" target="_blank" rel="noopener" title="طباعة الإيصال">
                                        <x-icon class="w-3.5 h-3.5" name="printer" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state icon="calendar-x" title="لا توجد أقساط" />
                    @endforelse
                </section>
            </div>
        @endisset

        {{-- ===================== الصيانة ===================== --}}
        <div class="space-y-3" x-cloak x-show="tab === 'maintenance'">
            @if ($contract->maintenancePlan || $contract->maintenanceItems->isNotEmpty())
                <section class="bg-white rounded-2xl border border-gray-100 p-4">
                    <h3 class="text-[13px] font-bold text-gray-800 mb-2.5">خطة الصيانة: {{ $contract->maintenancePlan?->name ?? '—' }}</h3>
                    <div class="space-y-1.5">
                        @foreach ($contract->maintenanceItems as $item)
                            <div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-[12px]">
                                <span class="font-bold text-gray-700">{{ $item->product_name }}</span>
                                <span class="flex items-center gap-2 text-gray-500">
                                    {{ $item->interval_months ? 'كل ' . $item->interval_months . ' شهر — ' : '' }}<span dir="ltr">{{ $date($item->replacement_date) }}</span>
                                    @if ($item->replacement_date?->isPast())
                                        <x-ui.badge tone="red">فات موعده</x-ui.badge>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! $contract->maintenancePlan && $contract->maintenanceItems->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100">
                    <x-ui.empty-state icon="wrench" title="لا توجد خطة صيانة" message="لم تُربط خطة صيانة بهذا العقد." />
                </div>
            @endif
        </div>

        {{-- ===================== الشكاوى ===================== --}}
        <div class="space-y-3" x-cloak x-show="tab === 'complaints'">
            @forelse ($contract->complaints as $complaint)
                <article class="bg-white rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[13px] font-bold text-gray-800">شكوى بتاريخ <span dir="ltr">{{ $date($complaint->complaint_date) }}</span></p>
                            <p class="text-[11px] text-gray-400 mt-0.5">الفني: {{ $complaint->technician?->name ?? '—' }}</p>
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
                    <x-ui.empty-state icon="message-square-off" title="لا توجد شكاوى" message="لم تُسجَّل أي شكوى على هذا العقد." />
                </div>
            @endforelse
        </div>
    </div>

    {{-- ===== الإجراءات ===== --}}
    <footer class="shrink-0 bg-white border-t border-gray-100 px-4 sm:px-5 py-3 grid grid-cols-3 gap-2">
        <x-ui.btn icon="pencil" size="sm" :href="route('contracts.edit', $contract)">تعديل</x-ui.btn>
        @if ($contract->isInstallment())
            <x-ui.btn icon="hand-coins" size="sm" variant="soft" :href="route('installments.index', ['q' => $contract->code])">تحصيل الأقساط</x-ui.btn>
        @else
            <x-ui.btn icon="file-text" size="sm" variant="soft" :href="route('contracts.index', ['customer_id' => $contract->customer_id])">عقود العميل</x-ui.btn>
        @endif
        <x-ui.btn icon="maximize-2" size="sm" variant="soft" :href="route('contracts.show', $contract)">الصفحة الكاملة</x-ui.btn>
    </footer>
</div>
