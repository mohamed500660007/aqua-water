{{-- لوحة جانبية تنزلق من اليسار وتعرض محتوى يُجلب من الخادم عند الفتح ($store.drawer.show(url)).
     المحتوى المُجلب مسؤول عن ترويسته وزر الإغلاق، وهنا فقط الإطار وحالات التحميل والخطأ. --}}
<div class="fixed inset-0 z-[70]" x-cloak x-data x-show="$store.drawer.open"
    x-on:keydown.escape.window="$store.drawer.close()">
    <div class="absolute inset-0 bg-brand-dark/40 backdrop-blur-[2px]" x-on:click="$store.drawer.close()"
        x-show="$store.drawer.open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"></div>

    <aside aria-modal="true" role="dialog"
        class="absolute inset-y-0 left-0 w-full sm:w-[640px] max-w-full bg-[#F7F7F5] shadow-2xl flex flex-col overflow-hidden sm:rounded-r-[28px]"
        x-show="$store.drawer.open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full">

        <template x-if="$store.drawer.loading">
            <div class="flex-1 flex flex-col">
                <div class="bg-brand-dark px-5 pt-6 pb-5 space-y-4 animate-pulse">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-white/10"></div>
                        <div class="space-y-2 flex-1">
                            <div class="h-4 w-40 rounded bg-white/15"></div>
                            <div class="h-3 w-24 rounded bg-white/10"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="h-14 rounded-xl bg-white/10"></div>
                        <div class="h-14 rounded-xl bg-white/10"></div>
                        <div class="h-14 rounded-xl bg-white/10"></div>
                        <div class="h-14 rounded-xl bg-white/10"></div>
                    </div>
                </div>
                <div class="p-5 space-y-3 animate-pulse">
                    <div class="h-28 rounded-2xl bg-white"></div>
                    <div class="h-40 rounded-2xl bg-white"></div>
                    <div class="h-24 rounded-2xl bg-white"></div>
                </div>
            </div>
        </template>

        <template x-if="$store.drawer.failed">
            <div class="flex-1 flex flex-col items-center justify-center gap-3 p-8 text-center">
                <span class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center">
                    <x-icon class="w-6 h-6" name="wifi-off" />
                </span>
                <p class="text-sm font-bold text-gray-800">تعذّر تحميل التفاصيل</p>
                <div class="flex gap-2">
                    <x-ui.btn icon="rotate-ccw" size="sm" x-on:click="$store.drawer.reload()">إعادة المحاولة</x-ui.btn>
                    <x-ui.btn size="sm" variant="outline" x-on:click="$store.drawer.close()">إغلاق</x-ui.btn>
                </div>
            </div>
        </template>

        <div class="flex-1 min-h-0 flex flex-col" x-show="! $store.drawer.loading && ! $store.drawer.failed"
            x-html="$store.drawer.html"
            x-effect="$store.drawer.html; $nextTick(() => window.renderIcons && window.renderIcons())"></div>
    </aside>
</div>
