@php($navGroups = \App\Support\Lookups::navGroups())

{{-- بداية: الشريط الجانبي (سطح المكتب فقط) — محفوظ عبر @@persist فلا يُعاد رسمه
     أو يومض بين الصفحات، وحالة الطي مبنية على CSS فتظهر صحيحة من أول رسم --}}
<aside class="app-sidebar hidden lg:flex shrink-0 bg-brand-dark flex-col py-6 text-white">
    {{-- الشعار --}}
    <a class="sb-item flex items-center gap-3 min-w-0 mb-1 shrink-0" href="{{ route('contracts.index') }}"
        title="أكوا ووتر" wire:navigate>
        <div class="w-9 h-9 shrink-0 rounded-xl brand-green flex items-center justify-center">
            <x-icon class="w-5 h-5" name="droplets" />
        </div>
        <div class="sb-expanded leading-tight min-w-0">
            <span class="block text-base font-bold tracking-wide truncate">أكوا ووتر</span>
            <span class="block text-[10px] text-gray-500">لوحة التحكم</span>
        </div>
    </a>

    {{-- زر طيّ/إظهار الشريط — ظاهر دائمًا في الحالتين --}}
    <button
        class="sb-item shrink-0 flex items-center gap-3 px-3 h-10 mb-4 rounded-xl text-gray-400 hover:text-white hover:bg-white/10 transition-colors"
        type="button" x-on:click="$store.sidebar.toggle()" aria-label="طيّ أو إظهار الشريط الجانبي">
        <span class="sb-expanded contents">
            <x-icon class="w-[18px] h-[18px] shrink-0" name="panel-left-close" />
            <span class="text-[12px] font-semibold flex-1 text-right">طيّ القائمة</span>
        </span>
        <span class="sb-collapsed-only items-center justify-center w-full" title="إظهار القائمة">
            <x-icon class="w-[18px] h-[18px]" name="panel-left-open" />
        </span>
    </button>

    {{-- روابط التنقل مجمّعة حسب المجال --}}
    <nav class="space-y-4 overflow-y-auto overflow-x-hidden scrollbar-hide flex-1 -mx-1 px-1">
        @foreach ($navGroups as $group)
            <div class="space-y-1">
                <p class="sb-group-label px-3 mb-1 text-[10px] font-bold text-gray-600 uppercase tracking-wider transition-all">
                    {{ $group['label'] }}</p>

                @foreach ($group['items'] as $item)
                    @php($href = route($item['route']))
                    <x-ui.nav-link :active="\App\Support\Lookups::isNavActive($href)" :badge="$item['badge'] ?? null"
                        :icon="$item['icon']" :label="$item['label']" :href="$href" />
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- بطاقة الدعم --}}
    <div class="sb-expanded brand-green rounded-2xl p-4 mt-4 shrink-0">
        <div class="mb-2">
            <x-icon class="w-5 h-5" name="headphones" />
        </div>
        <p class="font-bold text-[13px] leading-tight mb-1">دعم فني</p>
        <p class="text-[11px] leading-snug opacity-70 mb-3">فريقنا متاح من 9ص حتى 6م</p>
        <a class="inline-flex items-center gap-1.5 text-[11px] font-bold bg-brand-dark text-white px-3 py-1.5 rounded-lg"
            href="tel:19345">
            <x-icon class="w-3.5 h-3.5" name="phone" />
            19345
        </a>
    </div>

    {{-- نسخة مصغّرة من بطاقة الدعم عند الطي --}}
    <a class="sb-collapsed-only w-11 h-11 mx-auto mt-4 shrink-0 rounded-2xl brand-green items-center justify-center"
        href="tel:19345" title="دعم فني: 19345">
        <x-icon class="w-5 h-5" name="headphones" />
    </a>
</aside>
{{-- نهاية: الشريط الجانبي --}}
