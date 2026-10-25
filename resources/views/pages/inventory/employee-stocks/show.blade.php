@extends('layouts.app')

@section('title', 'عهدة ' . $employee->name)
@section('page-title', 'عهدة ' . $employee->name)
@section('page-subtitle', 'الأرصدة الحالية والتسويات وسجل كل حركات العهدة')

@section('content')
    <x-ui.page-header icon="user-round-check" subtitle="الأرصدة الحالية والتسويات وسجل كل حركات العهدة" :title="'عهدة ' . $employee->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('inventory.employee-stocks.index')">رجوع للعهد</x-ui.btn>
            <x-ui.btn icon="scale" size="sm" :href="route('inventory.custody-settlements.create', ['user_id' => $employee->id])">إنشاء
                تسوية</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5" x-data>
        <section class="lg:col-span-1 bg-white rounded-2xl border border-gray-100 shadow-sm p-4 self-start">
            <div class="flex items-center gap-3 mb-3.5">
                <x-ui.avatar :name="$employee->name" size="w-10 h-10" />
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-bold text-gray-800 truncate">{{ $employee->name }}</p>
                    <p class="text-[11px] text-gray-400">{{ $employee->role }}</p>
                </div>
                <span class="shrink-0 text-[11px] font-bold text-gray-400">{{ number_format($balances->sum('quantity')) }} قطعة</span>
            </div>

            @forelse ($balances as $balance)
                <div class="flex items-center justify-between gap-3 px-1 py-2 border-t border-gray-50">
                    <div class="min-w-0">
                        <p class="text-[12.5px] font-semibold text-gray-700 truncate">{{ $balance->product?->name ?? '—' }}</p>
                        <p class="text-[10.5px] text-gray-400" dir="ltr">{{ $balance->product?->sku }}</p>
                    </div>
                    <span class="shrink-0 text-[13px] font-bold text-gray-800">{{ number_format($balance->quantity) }}</span>
                </div>
            @empty
                <p class="text-[12px] text-gray-400 py-3 text-center">لا يحمل الموظف أي عهدة حاليًا.</p>
            @endforelse
        </section>

        <div class="lg:col-span-2 space-y-4 lg:space-y-5">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-4 pt-4 pb-3">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-gray-800">
                        <x-icon class="w-4 h-4 text-gray-400" name="scale" /> آخر التسويات
                    </h2>
                    <a class="text-[12px] font-bold text-gray-500 hover:text-brand-dark"
                        href="{{ route('inventory.custody-settlements.index', ['user_id' => $employee->id]) }}" wire:navigate>كل التسويات</a>
                </div>

                @forelse ($settlements as $settlement)
                    <button class="w-full text-right flex items-start justify-between gap-3 px-4 py-3 border-t border-gray-50 hover:bg-gray-50/70 transition-colors"
                        type="button" x-on:click="$store.drawer.show({{ Js::from(route('inventory.custody-settlements.panel', $settlement)) }})">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="text-[12.5px] font-black text-gray-800 font-mono" dir="ltr">{{ $settlement->number() }}</span>
                                <x-ui.badge :status="$settlement->type->label()" :tone="$settlement->type->tone()" />
                                <x-ui.badge :status="$settlement->status->label()" :tone="$settlement->status->tone()" dot />
                            </p>
                            <p class="text-[12px] text-gray-600 mt-1">{{ $settlement->reason }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ $settlement->items->map(fn ($item) => ($item->product?->name ?? 'منتج محذوف') . ' (' . $item->signedDifference() . ')')->join('، ') }}
                            </p>
                        </div>
                        <span class="shrink-0 text-[11.5px] font-semibold text-gray-500" dir="ltr">{{ $settlement->settlement_date->format('Y-m-d') }}</span>
                    </button>
                @empty
                    <p class="text-[12px] text-gray-400 px-4 pb-4">لا توجد تسويات لهذا الموظف.</p>
                @endforelse
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <h2 class="flex items-center gap-2 text-sm font-bold text-gray-800 px-4 pt-4 pb-3">
                    <x-icon class="w-4 h-4 text-gray-400" name="history" /> سجل حركات العهدة
                </h2>

                <div class="overflow-x-auto scrollbar-hide">
                    <table class="w-full min-w-[620px] text-[12.5px]">
                        <thead class="bg-gray-50/70 border-y border-gray-100">
                            <tr>
                                <x-ui.th>التاريخ</x-ui.th>
                                <x-ui.th>الصنف</x-ui.th>
                                <x-ui.th>الكمية</x-ui.th>
                                <x-ui.th>الرصيد بعدها</x-ui.th>
                                <x-ui.th>البيان</x-ui.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($movements as $movement)
                                @php($incoming = $movement->type === \App\Domain\Inventory\Enums\StockMovementType::In)
                                <tr>
                                    <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap" dir="ltr">{{ $movement->moved_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2.5 font-semibold text-gray-700">{{ $movement->product?->name ?? 'منتج محذوف' }}</td>
                                    <td class="px-4 py-2.5 font-black {{ $incoming ? 'text-green-700' : 'text-red-500' }}" dir="ltr">
                                        {{ $incoming ? '+' : '-' }}{{ number_format($movement->quantity) }}</td>
                                    <td class="px-4 py-2.5 font-bold text-gray-800">{{ number_format($movement->balance_after) }}</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $movement->note ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($movements->isEmpty())
                    <p class="text-[12px] text-gray-400 px-4 py-4">لا توجد حركات على عهدة هذا الموظف.</p>
                @endif

                <x-ui.paginator :paginator="$movements" />
            </section>
        </div>
    </div>
@endsection
