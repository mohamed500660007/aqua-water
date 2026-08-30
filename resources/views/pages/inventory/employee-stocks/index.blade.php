@extends('layouts.app')

@section('title', 'مخزون الموظفين')
@section('page-title', 'مخزون الموظفين')
@section('page-subtitle', 'ما يحمله كل موظف من بضاعة أخذها من المخزن')

@section('content')
    <x-ui.page-header icon="user-cog" subtitle="ما يحمله كل موظف من بضاعة أخذها من المخزن" title="مخزون الموظفين">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.index')">أرصدة المخزن</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('inventory.employee-stocks.create')">تسليم بضاعة لموظف</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="space-y-4 lg:space-y-5">
        @forelse ($balances as $employeeName => $items)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                    <x-ui.avatar :name="$employeeName" size="w-10 h-10" />
                    <div class="min-w-0">
                        <p class="text-[13px] font-bold text-gray-800 truncate">{{ $employeeName }}</p>
                        <p class="text-[11px] text-gray-400">{{ $items->first()->user?->role }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto scrollbar-hide">
                    <table class="w-full min-w-[480px]">
                        <thead class="bg-gray-50/70 border-b border-gray-100">
                            <tr>
                                <x-ui.th>المنتج</x-ui.th>
                                <x-ui.th align="left">الكمية الحالية</x-ui.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($items as $balance)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="text-[13px] font-bold text-gray-800">{{ $balance->product?->name ?? '—' }}</p>
                                        <p class="text-[11px] text-gray-400" dir="ltr">{{ $balance->product?->sku }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-left">
                                        <span class="text-[15px] font-bold text-gray-800">
                                            {{ number_format($balance->quantity) }}</span>
                                        <span class="text-[10px] text-gray-400">قطعة</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <x-ui.empty-state icon="user-x" message="سلّم بضاعة لأول موظف لتظهر هنا." title="لا يحمل أي موظف بضاعة حاليًا">
                    <x-slot:action>
                        <x-ui.btn icon="plus" size="sm" :href="route('inventory.employee-stocks.create')">تسليم بضاعة
                            لموظف</x-ui.btn>
                    </x-slot:action>
                </x-ui.empty-state>
            </div>
        @endforelse
    </div>
@endsection
