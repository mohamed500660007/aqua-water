@php($navItems = \App\Support\Lookups::navItems())

{{-- بداية: الشريط الجانبي (سطح المكتب فقط) --}}
<aside class="hidden lg:flex w-64 shrink-0 bg-brand-dark flex-col py-7 px-5 text-white">
    {{-- الشعار --}}
    <a class="flex items-center gap-3 mb-8 pr-1 shrink-0" href="{{ route('contracts.index') }}">
        <div class="w-9 h-9 rounded-xl brand-green flex items-center justify-center">
            <x-icon class="w-5 h-5" name="droplets" />
        </div>
        <div class="leading-tight">
            <span class="block text-lg font-bold tracking-wide">أكوا ووتر</span>
            <span class="block text-[10px] text-gray-500">لوحة التحكم</span>
        </div>
    </a>

    {{-- روابط التنقل --}}
    <nav class="space-y-1 overflow-y-auto scrollbar-hide flex-1 -mx-1 px-1">
        @foreach ($navItems as $item)
            <x-ui.nav-link :active="request()->routeIs($item['match'] ?? $item['route'])" :badge="$item['badge'] ?? null" :icon="$item['icon']"
                :label="$item['label']" :href="route($item['route'])" />
        @endforeach
    </nav>

    {{-- بطاقة الدعم --}}
    <div class="brand-green rounded-2xl p-4 relative mt-6 shrink-0">
        <div class="mb-3">
            <x-icon class="w-6 h-6" name="headphones" />
        </div>
        <p class="font-bold text-sm leading-tight mb-1">دعم فني</p>
        <p class="text-[11px] leading-snug opacity-70 mb-3">فريقنا متاح من 9ص حتى 6م</p>
        <a class="inline-flex items-center gap-1.5 text-[11px] font-bold bg-brand-dark text-white px-3 py-1.5 rounded-lg"
            href="tel:19345">
            <x-icon class="w-3.5 h-3.5" name="phone" />
            19345
        </a>
    </div>
</aside>
{{-- نهاية: الشريط الجانبي --}}
