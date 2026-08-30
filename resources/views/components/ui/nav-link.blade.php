@props(['icon', 'label', 'href' => '#', 'badge' => null, 'active' => false])

{{-- الحالة النشطة تُرسم من الخادم أولًا (بلا وميض عند أول تحميل) ثم يتولّاها
     Alpine أثناء التنقّل، لأن هذا الرابط يعيش داخل شريط محفوظ (@@persist)
     لا يُعاد رسمه بين الصفحات. --}}
<a class="sb-item nav-item relative flex items-center gap-3 px-3 h-11 rounded-xl text-[13px] font-semibold transition-colors {{ $active ? 'nav-item-active' : 'text-gray-400' }}"
    x-bind:class="$store.nav.isActive(@js($href)) ? 'nav-item-active' : 'text-gray-400'"
    data-nav-href="{{ $href }}" href="{{ $href }}" title="{{ $label }}" wire:navigate>
    <x-icon class="w-[18px] h-[18px] shrink-0" :name="$icon" />
    <span class="sb-expanded truncate flex-1">{{ $label }}</span>

    @if ($badge)
        <span
            class="sb-expanded shrink-0 bg-[#FF9B9B] text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center">{{ $badge }}</span>
        <span class="sb-collapsed-only absolute top-1.5 left-1.5 w-2 h-2 rounded-full bg-[#FF9B9B]"></span>
    @endif
</a>
