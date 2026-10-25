{{-- محتوى لوحة معاينة طلبية الوارد (يُحقن داخل x-ui.drawer). --}}
@php
    $pieces = $order->items->sum('quantity');
    $unitCost = $pieces > 0 ? (float) $order->total_amount / $pieces : 0;
@endphp

<div class="flex-1 min-h-0 flex flex-col">
    <header class="shrink-0 bg-brand-dark text-white px-5 pt-5 pb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-14 h-14 shrink-0 rounded-2xl bg-brand-green text-brand-dark flex items-center justify-center">
                    <x-icon class="w-7 h-7" name="truck" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-[17px] font-black leading-tight font-mono" dir="ltr">{{ $order->number() }}</h2>
                    <p class="text-[12px] text-white/60 mt-1">طلبية وارد — <span dir="ltr">{{ $order->order_date->format('Y-m-d') }}</span></p>
                </div>
            </div>
            <button class="w-9 h-9 shrink-0 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                type="button" aria-label="إغلاق" x-on:click="$store.drawer.close()">
                <x-icon class="w-[18px] h-[18px]" name="x" />
            </button>
        </div>

        <div class="grid grid-cols-3 gap-2 mt-4">
            <div class="rounded-xl bg-white/[.08] px-2.5 py-2">
                <p class="text-[10px] font-bold opacity-70">الأصناف</p>
                <p class="text-[15px] font-black">{{ $order->items->count() }}</p>
            </div>
            <div class="rounded-xl bg-white/[.08] px-2.5 py-2">
                <p class="text-[10px] font-bold opacity-70">إجمالي القطع</p>
                <p class="text-[15px] font-black">{{ number_format($pieces) }}</p>
            </div>
            <div class="rounded-xl bg-brand-green text-brand-dark px-2.5 py-2">
                <p class="text-[10px] font-bold opacity-70">إجمالي المبلغ</p>
                <p class="text-[15px] font-black">{{ number_format((float) $order->total_amount, 2) }} <span class="text-[9px] opacity-60">ج.م</span></p>
            </div>
        </div>
    </header>

    <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 sm:px-5 py-4 space-y-3.5">
        <section class="bg-white rounded-2xl border border-gray-100 p-4">
            <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                <x-icon class="w-4 h-4 text-gray-400" name="info" /> بيانات الطلبية
            </h3>
            <x-ui.detail-row label="رقم الطلبية" :value="$order->number()" />
            <x-ui.detail-row label="تاريخ الطلبية" :value="$order->order_date->format('Y-m-d')" />
            <x-ui.detail-row label="المخزن" :value="$order->warehouse?->name ?? '—'" />
            <x-ui.detail-row label="المورد" :value="$order->supplier ?? '—'" />
            <x-ui.detail-row label="متوسط تكلفة القطعة" :value="number_format($unitCost, 2) . ' ج.م'" />
            <x-ui.detail-row label="سُجّلت في"><span dir="ltr">{{ $order->created_at?->format('Y-m-d H:i') }}</span></x-ui.detail-row>
        </section>

        <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 px-4 pt-4 pb-3">
                <x-icon class="w-4 h-4 text-gray-400" name="package" /> محتوى الطلبية
            </h3>
            <table class="w-full text-[12.5px]">
                <thead class="bg-gray-50 text-[11px] text-gray-500">
                    <tr>
                        <th class="px-4 py-2.5 text-right font-bold">#</th>
                        <th class="px-3 py-2.5 text-right font-bold">المنتج</th>
                        <th class="px-3 py-2.5 text-right font-bold">الكمية</th>
                        <th class="px-4 py-2.5 text-left font-bold">سعر الشراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-2.5 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2.5">
                                <p class="font-bold text-gray-700">{{ $item->product?->name ?? 'منتج محذوف' }}</p>
                                <p class="text-[10.5px] text-gray-400" dir="ltr">{{ $item->product?->sku }}</p>
                            </td>
                            <td class="px-3 py-2.5 font-black text-gray-800">{{ number_format($item->quantity) }}</td>
                            <td class="px-4 py-2.5 text-left font-bold text-gray-700">
                                {{ $item->unit_price !== null ? number_format((float) $item->unit_price, 2).' ج.م' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td class="px-4 py-2.5 font-black text-gray-800" colspan="2">الإجمالي</td>
                        <td class="px-3 py-2.5 font-black text-gray-800">{{ number_format($pieces) }}</td>
                        <td class="px-4 py-2.5 text-left font-black text-gray-800">{{ number_format((float) $order->total_amount, 2) }} ج.م</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        @if ($order->note)
            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                    <x-icon class="w-4 h-4 text-gray-400" name="notebook-pen" /> ملاحظة
                </h3>
                <p class="text-[12.5px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $order->note }}</p>
            </section>
        @endif
    </div>

    <footer class="shrink-0 bg-white border-t border-gray-100 px-4 sm:px-5 py-3">
        <x-ui.btn class="w-full" icon="plus" size="sm" :href="route('inventory.purchase-orders.create')">طلبية جديدة</x-ui.btn>
    </footer>
</div>
