@extends('layouts.app')

@section('title', 'الأقساط')
@section('page-title', 'الأقساط')
@section('page-subtitle', 'متابعة وتحصيل أقساط عقود التقسيط')

@php
    $hasFilters = collect(request()->only(['q', 'status', 'customer_id', 'from', 'to']))->filter()->isNotEmpty();
    $activeStatus = request('status') ?: 'all';
    $tabs = [
        'all' => 'كل العملاء',
        'overdue' => 'متأخرة',
        'due_this_month' => 'مستحقة هذا الشهر',
        'upcoming' => 'قادمة',
        'paid' => 'محصَّلة',
    ];
@endphp

@section('content')
    <x-ui.page-header icon="wallet" subtitle="متابعة وتحصيل أقساط عقود التقسيط" title="الأقساط" />

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 lg:gap-4 mb-4 lg:mb-6">
        <x-ui.kpi hint="تحتاج متابعة عاجلة" icon="triangle-alert" label="أقساط متأخرة" tone="red" unit="قسط"
            :value="number_format($stats['overdue_count'])" />
        <x-ui.kpi hint="من الأقساط المتأخرة" icon="wallet" label="قيمة المتأخر" tone="red" unit="ج.م"
            :value="number_format($stats['overdue_amount'])" />
        <a class="block rounded-2xl" href="{{ route('installments.index', ['status' => 'due_this_month']) }}"
            wire:navigate>
            <x-ui.kpi hint="حتى نهاية الشهر الحالي — اضغط للعرض" icon="calendar-clock" label="مستحق هذا الشهر" tone="amber"
                unit="ج.م" :value="number_format($stats['due_this_month_amount'])" />
        </a>
        <x-ui.kpi hint="من بداية الشهر الحالي" icon="banknote" label="تم تحصيله هذا الشهر" tone="green" unit="ج.م"
            :value="number_format($stats['collected_this_month'])" />
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-hide p-3 border-b border-gray-100">
            @foreach ($tabs as $value => $label)
                @php
                    $tabQuery = array_filter(
                        array_merge(request()->except(['status', 'page']), $value === 'all' ? [] : ['status' => $value]),
                    );
                @endphp
                <a class="shrink-0 px-3.5 py-2 rounded-xl text-[12px] font-bold transition-colors {{ $activeStatus === $value ? 'bg-brand-green/25 text-brand-dark' : 'text-gray-500 hover:bg-gray-50' }}"
                    href="{{ route('installments.index', $tabQuery) }}" wire:navigate>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('installments.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }"
            x-on:submit.prevent="navigateForm($el)">
            <input name="status" type="hidden" value="{{ request('status') }}" />
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث بكود العقد أو اسم العميل..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="user-round" name="customer_id" :options="$customers" placeholder="كل العملاء"
                :selected="request('customer_id')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('installments.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[860px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.th>العميل</x-ui.th>
                        <x-ui.th>العقد</x-ui.th>
                        <x-ui.th>القسط</x-ui.th>
                        <x-ui.sort-th column="due_date">تاريخ الاستحقاق</x-ui.sort-th>
                        <x-ui.sort-th column="amount">المبلغ</x-ui.sort-th>
                        <x-ui.th>الحالة</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" x-data="{ openId: null }">
                    @foreach ($installments as $installment)
                        @php($status = $installment->status())
                        @php($contract = $installment->contract)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="text-[13px] font-bold text-gray-800 truncate block"
                                    href="{{ route('customers.show', $contract->customer_id) }}" wire:navigate>
                                    {{ $contract->customer?->name ?? '—' }}</a>
                                @if ($phone = $contract->customer?->primaryPhone?->phone)
                                    <a class="text-[11px] text-gray-400" dir="ltr" href="tel:{{ $phone }}">{{ $phone }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a class="text-[12px] font-bold text-gray-700" dir="ltr"
                                    href="{{ route('contracts.show', $installment->sales_contract_id) }}" wire:navigate>
                                    {{ $contract->code }}</a>
                            </td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-700">#{{ $installment->number }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">
                                {{ $installment->due_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-[13px] font-bold text-gray-800">
                                    {{ number_format((float) $installment->amount, 2) }}</span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :tone="$status['tone']" dot>{{ $status['label'] }}</x-ui.badge>
                                @if ($installment->isPaid())
                                    <p class="text-[10px] text-gray-400 mt-1" dir="ltr">
                                        {{ $installment->paid_at->format('Y-m-d') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($installment->isPaid())
                                        <x-ui.icon-btn icon="printer" label="طباعة الإيصال"
                                            :href="route('installments.receipt', $installment)" />
                                        <form action="{{ route('installments.uncollect', $installment) }}" method="POST" x-data>
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.icon-btn icon="rotate-ccw" label="تراجع عن التحصيل" tone="danger"
                                                x-on:click="$store.confirm.ask({
                                                    title: 'التراجع عن التحصيل',
                                                    message: {{ Js::from('سيُعاد مبلغ '.number_format((float) $installment->paid_amount, 2).' ج.م إلى متبقي العقد '.$contract->code.'.') }},
                                                    label: 'تراجع',
                                                    onOk: () => $root.requestSubmit()
                                                })" />
                                        </form>
                                    @else
                                        <button class="shrink-0 h-9 px-3.5 rounded-xl text-[12px] font-bold bg-brand-green/25 text-green-700 hover:bg-brand-green/40 transition-colors flex items-center gap-1.5"
                                            type="button"
                                            x-on:click="openId = openId === {{ $installment->id }} ? null : {{ $installment->id }}">
                                            <x-icon class="w-3.5 h-3.5" name="banknote" />
                                            تعديل / تحصيل
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if (! $installment->isPaid())
                            <tr x-cloak x-show="openId === {{ $installment->id }}">
                                <td class="px-4 pb-4 pt-0" colspan="7">
                                    <form action="{{ route('installments.collect', $installment) }}" class="rounded-2xl bg-gray-50 border border-gray-100 p-3.5"
                                        method="POST">
                                        @csrf

                                        {{-- بيانات ثابتة للعرض --}}
                                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mb-3.5 pb-3.5 border-b border-gray-200">
                                            <span class="text-[12px] text-gray-500">
                                                العميل:
                                                <span class="font-bold text-gray-700">{{ $contract->customer?->name }}</span>
                                            </span>
                                            @if ($device = $contract->items->pluck('product_name')->join('، '))
                                                <span class="text-[12px] text-gray-500">
                                                    نوع الجهاز:
                                                    <span class="font-bold text-gray-700">{{ $device }}</span>
                                                </span>
                                            @endif
                                            @if ($contract->installation_date)
                                                <span class="text-[12px] text-gray-500" dir="ltr">
                                                    تاريخ التركيب:
                                                    <span class="font-bold text-gray-700">{{ $contract->installation_date->format('Y-m-d') }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">المبلغ
                                                    المحصَّل</label>
                                                <input
                                                    class="w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                                                    max="{{ $contract->remaining }}" min="0.01" name="amount"
                                                    step="0.01" type="number" value="{{ $installment->amount }}" />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ
                                                    التحصيل الفعلي</label>
                                                <input
                                                    class="w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                                                    max="{{ today()->toDateString() }}" name="paid_at" type="date"
                                                    value="{{ today()->toDateString() }}" />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الفني
                                                    المُحصِّل</label>
                                                <select
                                                    class="w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                                                    name="collected_by_id">
                                                    <option value="">بدون تحديد</option>
                                                    @foreach ($technicians as $id => $name)
                                                        <option value="{{ $id }}" @selected($contract->installer_id == $id)>
                                                            {{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">المندوبة
                                                    المسؤولة</label>
                                                <select
                                                    class="w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                                                    name="sales_rep_id">
                                                    <option value="">بدون تحديد</option>
                                                    @foreach ($salesReps as $id => $name)
                                                        <option value="{{ $id }}" @selected($contract->sales_rep_id == $id)>
                                                            {{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">ملاحظة
                                                    (اختياري)</label>
                                                <input
                                                    class="w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-[13px] text-gray-800 focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green outline-none transition"
                                                    name="note" placeholder="مثال: تحصيل نقدي عبر المندوب" type="text" />
                                            </div>
                                        </div>
                                        <p class="text-[11px] text-amber-600 mt-2.5">
                                            لو المبلغ المحصَّل أقل من قيمة القسط، يُنشأ تلقائيًا قسط جديد بالمتبقي.
                                        </p>
                                        @error('amount')
                                            <p class="text-[11px] font-semibold text-red-500 mt-2">{{ $message }}</p>
                                        @enderror
                                        <div class="flex justify-end gap-2 mt-3">
                                            <button class="h-9 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:bg-gray-100 transition-colors"
                                                type="button" x-on:click="openId = null">إلغاء</button>
                                            <button class="h-9 px-4 rounded-xl text-[12px] font-bold bg-brand-green text-brand-dark hover:brightness-95 transition"
                                                type="submit">تأكيد التحصيل</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($installments->isEmpty())
            <x-ui.empty-state icon="wallet" :title="$hasFilters ? 'لا توجد أقساط مطابقة' : 'لا توجد أقساط بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'تظهر هنا أقساط عقود التقسيط بمجرد إنشائها.'">
                @if ($hasFilters)
                    <x-slot:action>
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('installments.index')">إعادة
                            ضبط الفلاتر</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$installments" />
    </div>
@endsection
