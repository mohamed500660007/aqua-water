@extends('layouts.app')

@section('title', 'سجل الصيانة')
@section('page-title', 'سجل الصيانة')
@section('page-subtitle', 'كل عمليات تغيير الشمع: القطاعي ومن عقود الصيانة')

@php($hasFilters = collect(request()->only(['q', 'type', 'technician_id', 'from', 'to']))->filter()->isNotEmpty())

@section('content')
    <x-ui.page-header icon="history" subtitle="كل عمليات تغيير الشمع: القطاعي ومن عقود الصيانة" title="سجل الصيانة">
        <x-slot:actions>
            <x-ui.btn icon="wrench" size="sm" :href="route('maintenance.operations.create')">تسجيل صيانة</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @include('pages.maintenance.partials.tabs')

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_170px_190px_150px_150px] gap-2.5 lg:items-end"
            action="{{ route('maintenance.operations.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }"
            x-on:submit.prevent="navigateForm($el)">
            <div class="sm:col-span-2 lg:col-span-1">
                <x-ui.search-input name="q" placeholder="ابحث باسم العميل أو كوده أو كود الجهاز..." value="{{ request('q') }}"
                    x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />
            </div>
            <x-ui.select icon="layers" name="type" :options="$types" placeholder="كل الأنواع" :selected="request('type')"
                x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="user-cog" name="technician_id" :options="$technicians" placeholder="كل الفنيين"
                :selected="request('technician_id')" x-on:change="navigateForm($el.form)" />
            <input class="ui-input h-11 px-3 text-[13px]" name="from" type="date" value="{{ request('from') }}" placeholder="من تاريخ"
                x-on:change="navigateForm($el.form)" />
            <input class="ui-input h-11 px-3 text-[13px]" name="to" type="date" value="{{ request('to') }}" placeholder="إلى تاريخ"
                x-on:change="navigateForm($el.form)" />
        </form>

        <div class="overflow-x-auto scrollbar-hide" x-data>
            <table class="w-full min-w-[980px]">
                <thead>
                    <tr>
                        <x-ui.th>العملية</x-ui.th>
                        <x-ui.th>العميل / الجهاز</x-ui.th>
                        <x-ui.th>النوع</x-ui.th>
                        <x-ui.th>الشمعات</x-ui.th>
                        <x-ui.th>الفني / الموظفة</x-ui.th>
                        <x-ui.th>المبلغ</x-ui.th>
                        <x-ui.th>العمولات</x-ui.th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($operations as $operation)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-[12.5px] font-black text-gray-800 font-mono" dir="ltr">{{ $operation->number() }}</p>
                                <p class="text-[11px] text-gray-400" dir="ltr">{{ $operation->serviced_at->format('Y-m-d') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-bold text-gray-800">{{ $operation->customer?->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400" dir="ltr">{{ $operation->device?->code }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :tone="$operation->type->tone()">{{ $operation->type->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-[240px]">
                                    @foreach ($operation->items as $item)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600"
                                            title="{{ $item->product_name }}">
                                            <b class="text-gray-800">{{ $item->position }}</b> {{ \Illuminate\Support\Str::limit($item->product_name, 12) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[12.5px] font-semibold text-gray-700">{{ $operation->technician?->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400">{{ $operation->salesRep?->name ?? 'بدون موظفة' }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($operation->maintenance_package_id)
                                    <span class="text-[12px] text-gray-400">ضمن العقد</span>
                                @else
                                    <span class="text-[13px] font-black text-gray-800">{{ number_format((float) $operation->total, 2) }}</span>
                                    <span class="text-[10px] text-gray-400">ج.م</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600 whitespace-nowrap">
                                @if ($operation->maintenance_package_id)
                                    <span class="text-gray-400">موحّدة على العقد</span>
                                @else
                                    فني {{ number_format((float) $operation->technician_commission, 2) }}
                                    @if ($operation->sales_rep_id)
                                        <span class="text-gray-300">·</span> موظفة {{ number_format((float) $operation->sales_rep_commission, 2) }}
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <form action="{{ route('maintenance.operations.destroy', $operation) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="undo-2" label="إلغاء العملية" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'إلغاء عملية الصيانة',
                                                message: {{ Js::from('ستُرد شمعات ' . $operation->number() . ' إلى مصدرها ويعود موعد الجهاز السابق.') }},
                                                label: 'إلغاء العملية',
                                                onOk: () => $root.requestSubmit()
                                            })" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($operations->isEmpty())
            <x-ui.empty-state icon="wrench" :title="$hasFilters ? 'لا توجد عمليات مطابقة' : 'لا توجد عمليات صيانة بعد'"
                :message="$hasFilters ? 'جرّب تعديل البحث أو الفلاتر.' : 'سجّل أول صيانة من ملف الصيانة.'" />
        @endif

        <x-ui.paginator :paginator="$operations" />
    </div>
@endsection
