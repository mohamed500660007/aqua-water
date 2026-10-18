{{-- محتوى لوحة معاينة تسوية العهدة (يُحقن داخل x-ui.drawer). --}}
@php
    $pieces = $settlement->items->sum('quantity');
    $sign = $settlement->type === \App\Domain\Inventory\Enums\CustodySettlementType::Negative ? '-' : '+';
@endphp

<div class="flex-1 min-h-0 flex flex-col">
    <header class="shrink-0 bg-brand-dark text-white px-5 pt-5 pb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-14 h-14 shrink-0 rounded-2xl bg-brand-green text-brand-dark flex items-center justify-center">
                    <x-icon class="w-7 h-7" name="scale" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-[17px] font-black leading-tight font-mono" dir="ltr">{{ $settlement->number() }}</h2>
                    <p class="text-[12px] text-white/60 mt-1">{{ $settlement->type->label() }} —
                        <span dir="ltr">{{ $settlement->settlement_date->format('Y-m-d') }}</span></p>
                </div>
            </div>
            <button class="w-9 h-9 shrink-0 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                type="button" aria-label="إغلاق" x-on:click="$store.drawer.close()">
                <x-icon class="w-[18px] h-[18px]" name="x" />
            </button>
        </div>

        <div class="grid grid-cols-3 gap-2 mt-4">
            <div class="rounded-xl bg-white/[.08] px-2.5 py-2">
                <p class="text-[10px] font-bold opacity-70">الحالة</p>
                <p class="text-[14px] font-black">{{ $settlement->status->label() }}</p>
            </div>
            <div class="rounded-xl bg-white/[.08] px-2.5 py-2">
                <p class="text-[10px] font-bold opacity-70">الأصناف</p>
                <p class="text-[15px] font-black">{{ $settlement->items->count() }}</p>
            </div>
            <div class="rounded-xl bg-brand-green text-brand-dark px-2.5 py-2">
                <p class="text-[10px] font-bold opacity-70">أثرها على العهدة</p>
                <p class="text-[15px] font-black" dir="ltr">{{ $sign }}{{ number_format($pieces) }}</p>
            </div>
        </div>
    </header>

    <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 sm:px-5 py-4 space-y-3.5">
        <section class="bg-white rounded-2xl border border-gray-100 p-4">
            <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                <x-icon class="w-4 h-4 text-gray-400" name="info" /> بيانات التسوية
            </h3>
            <x-ui.detail-row label="الموظف" :value="$settlement->user?->name ?? '—'" />
            <x-ui.detail-row label="النوع" :value="$settlement->type->label()" />
            <x-ui.detail-row label="السبب" :value="$settlement->reason" />
            <x-ui.detail-row label="تاريخ التسوية" :value="$settlement->settlement_date->format('Y-m-d')" />
            <x-ui.detail-row label="أُنشئت في"><span dir="ltr">{{ $settlement->created_at?->format('Y-m-d H:i') }}</span></x-ui.detail-row>
            <x-ui.detail-row label="رُحّلت في"><span dir="ltr">{{ $settlement->posted_at?->format('Y-m-d H:i') ?? '—' }}</span></x-ui.detail-row>
        </section>

        <section class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 px-4 pt-4 pb-3">
                <x-icon class="w-4 h-4 text-gray-400" name="package" /> أصناف التسوية
            </h3>
            <table class="w-full text-[12.5px]">
                <thead class="bg-gray-50 text-[11px] text-gray-500">
                    <tr>
                        <th class="px-4 py-2.5 text-right font-bold">الصنف</th>
                        <th class="px-3 py-2.5 text-right font-bold">الكمية</th>
                        <th class="px-3 py-2.5 text-right font-bold">قبل</th>
                        <th class="px-4 py-2.5 text-left font-bold">بعد</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($settlement->items as $item)
                        <tr>
                            <td class="px-4 py-2.5">
                                <p class="font-bold text-gray-700">{{ $item->product?->name ?? 'منتج محذوف' }}</p>
                                <p class="text-[10.5px] text-gray-400" dir="ltr">{{ $item->product?->sku }}</p>
                            </td>
                            <td class="px-3 py-2.5 font-black text-gray-800" dir="ltr">{{ $sign }}{{ number_format($item->quantity) }}</td>
                            <td class="px-3 py-2.5 text-gray-500">{{ $item->balance_before ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-left font-black text-gray-800">{{ $item->balance_after ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($settlement->isDraft())
                <p class="px-4 py-2.5 text-[11px] text-amber-700 bg-amber-50 border-t border-amber-100">
                    الرصيد قبل/بعد يُسجَّل لحظة الترحيل.</p>
            @endif
        </section>

        @if ($settlement->note)
            <section class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="flex items-center gap-2 text-[13px] font-bold text-gray-800 mb-2">
                    <x-icon class="w-4 h-4 text-gray-400" name="notebook-pen" /> ملاحظات
                </h3>
                <p class="text-[12.5px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $settlement->note }}</p>
            </section>
        @endif
    </div>

    @if ($settlement->isDraft())
        <footer class="shrink-0 bg-white border-t border-gray-100 px-4 sm:px-5 py-3 flex gap-2">
            <div class="flex-1">
                @include('pages.inventory.custody-settlements.partials.post-button')
            </div>
            <x-ui.btn icon="pencil" size="sm" variant="soft" :href="route('inventory.custody-settlements.edit', $settlement)">تعديل</x-ui.btn>
        </footer>
    @endif
</div>
