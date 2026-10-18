@php($navGroups = \App\Support\Lookups::navGroups())

{{-- بداية: الشريط الجانبي (سطح المكتب فقط) — محفوظ عبر @@persist فلا يُعاد رسمه
     أو يومض بين الصفحات، وحالة الطي مبنية على CSS فتظهر صحيحة من أول رسم.
     x-data ضروري على الجذر: Alpine لا يفعّل أي توجيه خارج شجرة x-data. --}}
<aside class="app-sidebar hidden lg:flex shrink-0 bg-brand-dark flex-col py-6 text-white" x-data>
    {{-- الشعار + زر الطيّ (أيقونة فقط) --}}
    <div class="flex items-center gap-2 mb-5 shrink-0">
        <a class="sb-logo flex items-center gap-3 min-w-0 flex-1" href="{{ route('contracts.index') }}"
            title="أكوا ووتر" wire:navigate>
            <div class="w-9 h-9 shrink-0 rounded-xl brand-green flex items-center justify-center">
                <x-icon class="w-5 h-5" name="droplets" />
            </div>
            <div class="sb-label leading-tight min-w-0">
                <span class="block text-base font-bold tracking-wide truncate">أكوا ووتر</span>
                <span class="block text-[10px] text-gray-500">لوحة التحكم</span>
            </div>
        </a>

        {{-- ظاهر عند فتح الشريط: أيقونة الطيّ --}}
        <button
            class="sb-expanded w-9 h-9 shrink-0 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-colors"
            type="button" x-on:click="$store.sidebar.toggle()" title="طيّ القائمة" aria-label="طيّ القائمة">
            <x-icon class="w-[18px] h-[18px]" name="panel-left-close" />
        </button>
    </div>

    {{-- ظاهر عند طيّ الشريط: أيقونة الإظهار --}}
    <button
        class="sb-collapsed-only w-full h-10 mb-4 shrink-0 rounded-lg items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-colors"
        type="button" x-on:click="$store.sidebar.toggle()" title="إظهار القائمة" aria-label="إظهار القائمة">
        <x-icon class="w-[18px] h-[18px]" name="panel-left-open" />
    </button>

    {{-- روابط التنقل مجمّعة حسب المجال --}}
        <nav class="sb-nav space-y-4 overflow-y-auto overflow-x-hidden scrollbar-hide flex-1 -mx-1 px-1">
        @foreach ($navGroups as $group)
            <div class="space-y-1">
                <p class="sb-group-label px-3 mb-1 text-[10px] font-bold text-gray-600 uppercase tracking-wider whitespace-nowrap">
                    {{ $group['label'] }}</p>

                @foreach ($group['items'] as $item)
                    @php($href = route($item['route']))
                    <x-ui.nav-link :active="\App\Support\Lookups::isNavActive($href)" :badge="$item['badge'] ?? null"
                        :icon="$item['icon']" :label="$item['label']" :href="$href" />
                @endforeach
            </div>
        @endforeach
    </nav>



    {{-- نسخة مصغّرة من بطاقة الدعم عند الطي --}}
    <a class="sb-collapsed-only w-11 h-11 mx-auto mt-4 shrink-0 rounded-2xl brand-green items-center justify-center"
        href="tel:19345" title="دعم فني: 19345">
        <x-icon class="w-5 h-5" name="headphones" />
    </a>
</aside>
{{-- نهاية: الشريط الجانبي --}}
