@extends('layouts.app')

@section('title', $product->name)
@section('page-title', $product->name)
@section('page-subtitle', 'تفاصيل المنتج ورصيده')

@section('content')
    <x-ui.page-header icon="package" :subtitle="$product->sku" :title="$product->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('products.index')">رجوع للقائمة</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" variant="soft"
                :href="route('inventory.movements.create', ['product_id' => $product->id])">تسجيل حركة</x-ui.btn>
            <x-ui.btn icon="pencil" size="sm" :href="route('products.edit', $product)">تعديل</x-ui.btn>

            <form action="{{ route('products.destroy', $product) }}" method="POST" x-data>
                @csrf
                @method('DELETE')
                <x-ui.btn icon="trash-2" size="sm" variant="danger"
                    x-on:click="$store.confirm.ask({
                        title: 'حذف المنتج',
                        message: {{ Js::from('سيتم حذف «' . $product->name . '» من الكتالوج. العقود وحركات المخزون السابقة تبقى كما هي.') }},
                        label: 'حذف',
                        onOk: () => $root.requestSubmit()
                    })">حذف</x-ui.btn>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
        <div class="lg:col-span-1 space-y-4 lg:space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <span class="text-[12px] text-gray-400">الحالة</span>
                    <x-ui.badge :status="$product->status->label()" dot />
                </div>

                <x-ui.detail-row label="كود المنتج" :value="$product->sku" />
                <x-ui.detail-row label="التصنيف" :value="$product->category ?? '—'" />
                <x-ui.detail-row label="سعر البيع" :value="number_format((float) $product->price) . ' ج.م'" />
                <x-ui.detail-row label="سعر التكلفة" :value="$product->cost ? number_format((float) $product->cost) . ' ج.م' : '—'" />
                <x-ui.detail-row label="الضمان" :value="$product->warranty ?? '—'" />
                <x-ui.detail-row label="تاريخ الإضافة" :value="$product->created_at?->format('Y-m-d')" />
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="boxes" />
                    </span>
                    <h2 class="text-sm font-bold text-gray-800">الرصيد في المستودعات</h2>
                </div>

                @forelse ($product->stockBalances as $balance)
                    <div class="flex items-center justify-between gap-3 py-2.5 border-b border-gray-50 last:border-0">
                        <span class="text-[12px] text-gray-600">{{ $balance->warehouse?->name }}</span>
                        <span class="text-[15px] font-bold {{ $balance->quantity > 0 ? 'text-gray-800' : 'text-red-500' }}">
                            {{ number_format($balance->quantity) }}
                            <span class="text-[10px] text-gray-400">قطعة</span>
                        </span>
                    </div>
                @empty
                    <p class="text-[12px] text-gray-400">لا يوجد رصيد مسجل لهذا المنتج بعد.</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-3 p-5 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" name="list" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">آخر حركات المخزون</h2>
                    </div>
                    <a class="text-[11px] font-bold text-gray-400 hover:text-brand-dark"
                        href="{{ route('inventory.movements.index', ['product_id' => $product->id]) }}" wire:navigate>كل الحركات</a>
                </div>

                <div class="overflow-x-auto scrollbar-hide">
                    <table class="w-full min-w-[520px]">
                        <thead class="bg-gray-50/70 border-b border-gray-100">
                            <tr>
                                <x-ui.th>التاريخ</x-ui.th>
                                <x-ui.th>المخزن</x-ui.th>
                                <x-ui.th>النوع</x-ui.th>
                                <x-ui.th>الكمية</x-ui.th>
                                <x-ui.th align="left">الرصيد بعدها</x-ui.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($movements as $movement)
                                <tr>
                                    <td class="px-4 py-3 text-[12px] text-gray-600" dir="ltr">
                                        {{ $movement->moved_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-[12px] text-gray-600">{{ $movement->warehouse?->name }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :tone="$movement->type->tone()" dot>{{ $movement->type->label() }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-[13px] font-bold text-gray-800">{{ $movement->quantity }}</td>
                                    <td class="px-4 py-3 text-left text-[13px] font-bold text-gray-700">
                                        {{ $movement->balance_after }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($movements->isEmpty())
                    <x-ui.empty-state icon="list-x" message="سجّل حركة وارد لبدء تتبع رصيد هذا المنتج."
                        title="لا توجد حركات بعد" />
                @endif
            </div>
        </div>
    </div>
@endsection
