@extends('layouts.app')

@section('title', 'عقود الصيانة الجملة')
@section('page-title', 'عقود الصيانة الجملة')
@section('page-subtitle', 'باكيج الشمعات في حساب كل عميل — يُستهلك مع كل زيارة ويُقفل عند انتهائه')

@php($hasFilters = collect(request()->only(['q', 'status']))->filter()->isNotEmpty())

@section('content')
    <x-ui.page-header icon="package-check" subtitle="باكيج الشمعات في حساب كل عميل — يُستهلك مع كل زيارة ويُقفل عند انتهائه" title="عقود الصيانة الجملة">
        <x-slot:actions>
            <x-ui.btn icon="plus" size="sm" :href="route('maintenance.packages.create')">عقد صيانة جملة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.maintenance.partials.tabs')

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center gap-2.5" action="{{ route('maintenance.packages.index') }}"
            method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <x-ui.search-input name="q" placeholder="ابحث باسم العميل أو كوده..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />
            <x-ui.select icon="circle-dot" name="status" :options="$statuses" placeholder="كل الحالات" :selected="request('status')"
                x-on:change="navigateForm($el.form)" />
        </form>

        <div class="overflow-x-auto scrollbar-hide" x-data>
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr>
                        <x-ui.th>العقد</x-ui.th>
                        <x-ui.th>العميل / الجهاز</x-ui.th>
                        <x-ui.th>الرصيد</x-ui.th>
                        <x-ui.th>العقد المالي</x-ui.th>
                        <x-ui.th>العمولة الموحّدة</x-ui.th>
                        <x-ui.th>الحالة</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($packages as $package)
                        @php($usedPercent = $package->totalCandles() ? round($package->used_candles / $package->totalCandles() * 100) : 0)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-[12.5px] font-black text-gray-800 font-mono" dir="ltr">{{ $package->number() }}</p>
                                <p class="text-[11px] text-gray-400">من <span dir="ltr">{{ $package->started_at->format('Y-m-d') }}</span></p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-bold text-gray-800">{{ $package->customer?->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400" dir="ltr">{{ $package->device?->code }}</p>
                            </td>
                            <td class="px-4 py-3 min-w-[190px]">
                                <p class="text-[12px] text-gray-600 mb-1.5">
                                    استُهلك <b class="text-gray-800">{{ $package->used_candles }}</b> من <b class="text-gray-800">{{ $package->totalCandles() }}</b>
                                    <span class="text-gray-400">({{ $package->candles }} أساسية + {{ $package->gift_candles }} هدية)</span>
                                </p>
                                <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-l from-[#B494F7] to-[#C6F23E]" style="width: {{ $usedPercent }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[12px] font-semibold text-gray-700" dir="ltr">{{ $package->maintenanceContract?->code ?? '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600 whitespace-nowrap">
                                @if ($package->technician)
                                    {{ $package->technician->name }}: <b>{{ number_format((float) $package->technician_commission, 2) }}</b><br>
                                @endif
                                @if ($package->salesRep)
                                    {{ $package->salesRep->name }}: <b>{{ number_format((float) $package->sales_rep_commission, 2) }}</b>
                                @endif
                                @unless ($package->technician || $package->salesRep)
                                    <span class="text-gray-400">—</span>
                                @endunless
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :tone="$package->status->tone()" dot>{{ $package->status->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($package->isActive())
                                        <x-ui.icon-btn icon="wrench" label="تسجيل زيارة" tone="primary"
                                            :href="route('maintenance.operations.create', ['contract' => $package->sales_contract_id])" />
                                    @endif
                                    @if ($package->used_candles === 0)
                                        <form action="{{ route('maintenance.packages.destroy', $package) }}" method="POST" x-data>
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                                x-on:click="$store.confirm.ask({
                                                    title: 'حذف عقد الصيانة',
                                                    message: {{ Js::from('سيُحذف ' . $package->number() . ' ورصيده من حساب العميل.') }},
                                                    label: 'حذف',
                                                    onOk: () => $root.requestSubmit()
                                                })" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($packages->isEmpty())
            <x-ui.empty-state icon="package-check" :title="$hasFilters ? 'لا توجد عقود مطابقة' : 'لا توجد عقود صيانة جملة بعد'"
                :message="$hasFilters ? 'جرّب تعديل البحث أو الحالة.' : 'سجّل باكيج الـ 7 شمعات لعميل اشترك في عقد صيانة.'" />
        @endif

        <x-ui.paginator :paginator="$packages" />
    </div>
@endsection
