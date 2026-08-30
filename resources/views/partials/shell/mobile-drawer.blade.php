@php($navGroups = \App\Support\Lookups::navGroups())

{{-- بداية: قائمة التنقل الجانبية للهاتف (Drawer) — تعرض كل أقسام التطبيق،
     محفوظة عبر @@persist وتُغلق تلقائيًا بعد كل انتقال --}}
<div class="lg:hidden" x-cloak x-data>
    {{-- الخلفية المعتمة --}}
    <div class="fixed inset-0 z-[60] bg-brand-dark/50 backdrop-blur-[2px]" x-on:click="$store.drawer.close()"
        x-show="$store.drawer.open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-end="opacity-100" x-transition:enter-start="opacity-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0"
        x-transition:leave-start="opacity-100"></div>

    {{-- اللوحة المنزلقة من اليمين (RTL) --}}
    <aside class="fixed inset-y-0 right-0 z-[61] w-[82vw] max-w-[300px] bg-brand-dark text-white flex flex-col py-6 px-4 shadow-2xl"
        x-on:keydown.escape.window="$store.drawer.close()" x-show="$store.drawer.open"
        x-transition:enter="transition ease-out duration-250" x-transition:enter-end="translate-x-0"
        x-transition:enter-start="translate-x-full" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-end="translate-x-full" x-transition:leave-start="translate-x-0"
        aria-label="قائمة التنقل">

        {{-- الشعار وزر الإغلاق --}}
        <div class="flex items-center justify-between gap-3 mb-6 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 shrink-0 rounded-xl brand-green flex items-center justify-center">
                    <x-icon class="w-5 h-5" name="droplets" />
                </div>
                <div class="leading-tight min-w-0">
                    <span class="block text-base font-bold tracking-wide truncate">أكوا ووتر</span>
                    <span class="block text-[10px] text-gray-500">لوحة التحكم</span>
                </div>
            </div>

            <button
                class="w-10 h-10 shrink-0 rounded-xl bg-white/10 text-gray-300 flex items-center justify-center active:scale-90 transition-transform"
                type="button" x-on:click="$store.drawer.close()" aria-label="إغلاق القائمة">
                <x-icon class="w-[18px] h-[18px]" name="x" />
            </button>
        </div>

        {{-- أقسام التطبيق --}}
        <nav class="space-y-5 overflow-y-auto overflow-x-hidden scrollbar-hide flex-1 -mx-1 px-1">
            @foreach ($navGroups as $group)
                <div class="space-y-1">
                    <p class="px-3 mb-1 text-[10px] font-bold text-gray-600 uppercase tracking-wider">
                        {{ $group['label'] }}</p>

                    @foreach ($group['items'] as $item)
                        @php($href = route($item['route']))
                        <a class="flex items-center gap-3 px-3 min-h-[48px] rounded-xl text-[13px] font-semibold transition-colors {{ \App\Support\Lookups::isNavActive($href) ? 'nav-item-active' : 'text-gray-400 active:bg-white/10' }}"
                            x-bind:class="$store.nav.isActive(@js($href)) ? 'nav-item-active' : 'text-gray-400 active:bg-white/10'"
                            data-nav-href="{{ $href }}" href="{{ $href }}" wire:navigate
                            x-on:click="$store.drawer.close()">
                            <x-icon class="w-[18px] h-[18px] shrink-0" :name="$item['icon']" />
                            <span class="truncate flex-1">{{ $item['label'] }}</span>
                            <x-icon class="w-4 h-4 shrink-0 opacity-40" name="chevron-left" />
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- الدعم الفني --}}
        <a class="brand-green rounded-2xl px-4 min-h-[52px] mt-4 shrink-0 flex items-center gap-2.5 font-bold text-[13px]"
            href="tel:19345">
            <x-icon class="w-[18px] h-[18px]" name="headphones" />
            <span class="flex-1">دعم فني</span>
            <span class="text-[12px]">19345</span>
        </a>
    </aside>
</div>
{{-- نهاية: قائمة التنقل الجانبية للهاتف --}}
