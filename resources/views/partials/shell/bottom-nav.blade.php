@php($items = \App\Support\Lookups::bottomNav())

{{-- بداية: شريط التنقل السفلي العائم (الهاتف فقط — أهم أربعة أقسام)
     محفوظ عبر @@persist، والحالة النشطة تُحسب على العميل ($store.nav) --}}
<nav class="lg:hidden fixed inset-x-3 z-40 bottom-nav-safe transition-all duration-300 ease-out" id="bottomNav">
    <div
        class="mx-auto max-w-[480px] bg-white/95 backdrop-blur-md border border-gray-100 shadow-[0_10px_30px_-8px_rgba(26,26,26,0.25)] rounded-[1.75rem] p-1.5 flex items-stretch gap-0.5">

        @foreach ($items as $item)
            @php($href = route($item['route']))
            <a class="flex-1 min-w-0 min-h-[56px] flex flex-col items-center justify-center gap-1 rounded-[1.4rem] transition-all duration-200 {{ \App\Support\Lookups::isNavActive($href) ? 'bg-brand-green text-brand-dark' : 'text-gray-400 active:bg-gray-50' }}"
                x-bind:class="$store.nav.isActive(@js($href)) ? 'bg-brand-green text-brand-dark' : 'text-gray-400 active:bg-gray-50'"
                data-nav-href="{{ $href }}" href="{{ $href }}" wire:navigate>
                <x-icon class="w-[21px] h-[21px] shrink-0" :name="$item['icon']" />
                <span class="text-[9.5px] font-semibold leading-none truncate max-w-full px-0.5">{{ $item['label'] }}</span>
            </a>
        @endforeach

        {{-- المزيد: يفتح قائمة التنقل الكاملة --}}
        <button
            class="flex-1 min-w-0 min-h-[56px] flex flex-col items-center justify-center gap-1 rounded-[1.4rem] text-gray-400 active:bg-gray-50 transition-all duration-200"
            type="button" x-data x-on:click="$store.drawer.show()" aria-label="عرض كل الأقسام">
            <x-icon class="w-[21px] h-[21px] shrink-0" name="menu" />
            <span class="text-[9.5px] font-semibold leading-none truncate max-w-full px-0.5">المزيد</span>
        </button>
    </div>
</nav>
{{-- نهاية: شريط التنقل السفلي --}}
