@extends('layouts.app')

@section('title', 'مخزون الموظفين')
@section('page-title', 'مخزون الموظفين')
@section('page-subtitle', 'ما يحمله كل موظف من عهدة أخذها من المخزن')

@section('content')
    <x-ui.page-header icon="user-cog" subtitle="ما يحمله كل موظف من عهدة أخذها من المخزن" title="مخزون الموظفين">
        <x-slot:actions>
            <x-ui.btn icon="package-plus" size="sm" variant="soft" :href="route('inventory.stock.create')">إضافة إلى
                المخزن</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('inventory.employee-stocks.create')">تسليم عهدة لموظف</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($balances->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-5">
            @foreach ($balances as $employeeName => $items)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center gap-3 mb-3.5">
                        <x-ui.avatar :name="$employeeName" size="w-10 h-10" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-bold text-gray-800 truncate">{{ $employeeName }}</p>
                            <p class="text-[11px] text-gray-400">{{ $items->first()->user?->role }}</p>
                        </div>
                        <span class="shrink-0 text-[11px] font-bold text-gray-400">
                            {{ number_format($items->sum('quantity')) }} قطعة</span>
                    </div>

                    <div class="space-y-1 -mx-1">
                        @foreach ($items as $balance)
                            <div class="flex items-center justify-between gap-3 px-1 py-2 rounded-xl hover:bg-gray-50/70 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-[12.5px] font-semibold text-gray-700 truncate">
                                        {{ $balance->product?->name ?? '—' }}</p>
                                    <p class="text-[10.5px] text-gray-400" dir="ltr">{{ $balance->product?->sku }}</p>
                                </div>
                                <span class="shrink-0 text-[13px] font-bold text-gray-800">
                                    {{ number_format($balance->quantity) }}
                                    <span class="text-[10px] font-semibold text-gray-400">قطعة</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <x-ui.empty-state icon="user-x" message="سلّم عهدة لأول موظف لتظهر هنا." title="لا يحمل أي موظف عهدة حاليًا">
                <x-slot:action>
                    <x-ui.btn icon="plus" size="sm" :href="route('inventory.employee-stocks.create')">تسليم عهدة
                        لموظف</x-ui.btn>
                </x-slot:action>
            </x-ui.empty-state>
        </div>
    @endif
@endsection
