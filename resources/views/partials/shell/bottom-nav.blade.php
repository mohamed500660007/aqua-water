@php
    $items = \App\Support\Lookups::bottomNav();

    // ما لا يظهر في الشريط السفلي يظهر داخل لوحة «المزيد»
    $primaryRoutes = collect($items)->pluck('route')->all();
    $moreItems = collect(\App\Support\Lookups::navItems())
        ->reject(fn($item) => in_array($item['route'], $primaryRoutes))
        ->values();
    $moreHrefs = $moreItems->map(fn($item) => route($item['route']))->all();
    $moreIsActive = collect($moreHrefs)->contains(fn($href) => \App\Support\Lookups::isNavActive($href));
@endphp

{{-- بداية: شريط التنقل السفلي + لوحة «المزيد» المنبثقة منه (الهاتف فقط)
     محفوظ عبر @@persist، وx-data على الجذر لأن Alpine لا يفعّل أي توجيه خارجه --}}
<nav class="lg:hidden fixed inset-x-3 z-40 bottom-nav-safe" id="bottomNav" x-data>

    {{-- الخلفية المعتمة: تظهر خلف الشريط واللوحة وتُغلق عند اللمس --}}
    <div class="fixed inset-0 -z-10 bg-brand-dark/45 backdrop-blur-[3px]" x-cloak
        x-on:click="$store.more.close()" x-show="$store.more.open"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-end="opacity-100"
        x-transition:enter-start="opacity-0" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0" x-transition:leave-start="opacity-100"></div>

    <div class="relative mx-auto max-w-[480px] space-y-2">

        {{-- لوحة «المزيد»: تنبثق من الشريط نفسه وبنفس لغته البصرية --}}
        <div class="origin-bottom bg-white/95 backdrop-blur-md border border-gray-100 shadow-[0_18px_45px_-12px_rgba(26,26,26,0.4)] rounded-[1.75rem] p-3"
            x-cloak x-show="$store.more.open" x-on:keydown.escape.window="$store.more.close()"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100">

            <div class="flex items-center justify-between px-2 pb-2.5">
                <span class="text-[11px] font-bold text-gray-400">كل الأقسام</span>
                <span class="text-[11px] font-bold text-gray-300">{{ count($moreItems) }}</span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                @foreach ($moreItems as $index => $item)
                    @php($href = route($item['route']))
                    <a class="more-tile flex flex-col items-center justify-center gap-2 py-3.5 px-1 rounded-2xl border border-gray-100 bg-white active:scale-95 transition-transform"
                        style="animation-delay: {{ $index * 45 }}ms" data-nav-href="{{ $href }}" href="{{ $href }}"
                        wire:navigate x-on:click="$store.more.close()">
                        <span
                            class="w-11 h-11 shrink-0 rounded-2xl flex items-center justify-center transition-colors {{ \App\Support\Lookups::isNavActive($href) ? 'nav-item-active' : 'bg-gray-50 text-gray-500' }}"
                            x-bind:class="{ 'nav-item-active': $store.nav.isActive(@js($href)), 'bg-gray-50': !$store.nav.isActive(@js($href)), 'text-gray-500': !$store.nav.isActive(@js($href)) }">
                            <x-icon class="w-[19px] h-[19px]" :name="$item['icon']" />
                        </span>
                        <span class="text-xs font-semibold text-gray-600 leading-tight text-center truncate max-w-full">
                            {{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- الشريط العائم --}}
        <div
            class="bg-white/95 backdrop-blur-md border border-gray-100 shadow-[0_10px_30px_-8px_rgba(26,26,26,0.25)] rounded-[1.75rem] p-1.5 flex items-stretch gap-0.5">

            @foreach ($items as $item)
                @php($href = route($item['route']))
                <a class="flex-1 min-w-0 min-h-[56px] flex flex-col items-center justify-center gap-1 rounded-[1.4rem] transition-all duration-200 {{ \App\Support\Lookups::isNavActive($href) ? 'nav-item-active' : 'text-gray-400 active:bg-gray-50' }}"
                    x-bind:class="{ 'nav-item-active': $store.nav.isActive(@js($href)), 'text-gray-400': !$store.nav.isActive(@js($href)), 'active:bg-gray-50': !$store.nav.isActive(@js($href)) }"
                    data-nav-href="{{ $href }}" href="{{ $href }}" wire:navigate x-on:click="$store.more.close()">
                    <x-icon class="w-[21px] h-[21px] shrink-0" :name="$item['icon']" />
                    <span class="text-xs font-semibold leading-none truncate max-w-full px-0.5">{{ $item['label'] }}</span>
                </a>
            @endforeach

            {{-- «المزيد»: تدور الأيقونة 135° فتتحوّل من + إلى × عند الفتح --}}
            <button
                class="flex-1 min-w-0 min-h-[56px] flex flex-col items-center justify-center gap-1 rounded-[1.4rem] transition-all duration-200 {{ $moreIsActive ? 'nav-item-active' : 'text-gray-400 active:bg-gray-50' }}"
                x-bind:class="{
                    'nav-item-active': $store.more.open || $store.nav.isActiveAny(@js($moreHrefs)),
                    'text-gray-400': !($store.more.open || $store.nav.isActiveAny(@js($moreHrefs))),
                    'active:bg-gray-50': !($store.more.open || $store.nav.isActiveAny(@js($moreHrefs)))
                }"
                type="button" x-on:click="$store.more.toggle()"
                x-bind:aria-expanded="$store.more.open ? 'true' : 'false'" aria-label="عرض بقية الأقسام">
                <span class="shrink-0 transition-transform duration-300"
                    x-bind:class="$store.more.open ? 'rotate-[135deg]' : ''">
                    <x-icon class="w-[21px] h-[21px]" name="plus" />
                </span>
                <span class="text-xs font-semibold leading-none truncate max-w-full px-0.5"
                    x-text="$store.more.open ? 'إغلاق' : 'المزيد'">المزيد</span>
            </button>
        </div>
    </div>
</nav>
{{-- نهاية: شريط التنقل السفلي --}}
