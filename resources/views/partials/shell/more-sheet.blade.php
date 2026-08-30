@php($nav = collect(\App\Support\Lookups::navItems()))
@php($primary = ['contracts.index', 'customers.index', 'products.index', 'inventory.index'])
@php($moreItems = $nav->reject(fn($i) => in_array($i['route'], $primary))->values())

{{-- بداية: قائمة "المزيد" (Bottom Sheet — الهاتف فقط) --}}

{{-- الخلفية المعتمة --}}
<div class="lg:hidden fixed inset-0 z-[45] bg-brand-dark/50 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300"
    id="sheetBackdrop"></div>

<div aria-labelledby="moreSheetTitle" aria-modal="true"
    class="lg:hidden fixed inset-x-0 bottom-0 z-50 translate-y-full transition-transform duration-300 ease-out"
    id="moreSheet" role="dialog">
    <div class="mx-auto w-full max-w-[480px] bg-white rounded-t-[28px] shadow-[0_-10px_40px_-12px_rgba(26,26,26,0.35)] pb-safe-sheet"
        id="moreSheetPanel">

        {{-- مقبض السحب --}}
        <div class="pt-3 pb-1.5 touch-none" id="moreSheetHandle">
            <span class="block w-10 h-1.5 rounded-full bg-gray-200 mx-auto"></span>
        </div>

        {{-- العنوان وزر الإغلاق --}}
        <div class="flex items-center justify-between px-5 pb-2">
            <h2 class="text-base font-bold text-gray-800" id="moreSheetTitle">المزيد</h2>
            <button
                class="w-9 h-9 rounded-full bg-gray-50 text-gray-500 flex items-center justify-center active:scale-90 transition-transform"
                id="closeMoreSheet" type="button">
                <x-icon class="w-[18px] h-[18px]" name="x" />
            </button>
        </div>

        {{-- بقية الأقسام --}}
        <div class="px-3 pb-3 space-y-0.5 max-h-[55dvh] overflow-y-auto scrollbar-hide">
            @foreach ($moreItems as $item)
                @php($active = request()->routeIs($item['match'] ?? $item['route']))
                <a class="flex items-center gap-3 px-2 py-1.5 min-h-[52px] rounded-2xl transition-colors {{ $active ? 'bg-brand-green/15' : 'active:bg-gray-50' }}"
                    href="{{ route($item['route']) }}">
                    <span
                        class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center transition-colors {{ $active ? 'bg-brand-green text-brand-dark' : 'bg-gray-50 text-gray-500' }}">
                        <x-icon class="w-5 h-5" :name="$item['icon']" />
                    </span>
                    <span
                        class="flex-1 text-[13px] font-semibold {{ $active ? 'text-brand-dark' : 'text-gray-700' }}">{{ $item['label'] }}</span>
                    @if (!empty($item['badge']))
                        <span
                            class="bg-[#FF9B9B] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $item['badge'] }}</span>
                    @endif
                    <x-icon class="w-4 h-4 {{ $active ? 'text-brand-dark/40' : 'text-gray-300' }}" name="chevron-left" />
                </a>
            @endforeach
        </div>
    </div>
</div>
{{-- نهاية: قائمة "المزيد" --}}
