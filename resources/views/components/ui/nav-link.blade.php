@props(['icon', 'label', 'href' => '#', 'active' => false, 'badge' => null])

<a class="nav-item flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold {{ $active ? 'nav-item-active' : 'text-gray-400' }} transition-colors"
    href="{{ $href }}">
    <span class="flex items-center gap-3 min-w-0">
        <x-icon class="w-[18px] h-[18px]" :name="$icon" />
        <span class="truncate">{{ $label }}</span>
    </span>
    @if ($badge)
        <span
            class="shrink-0 bg-[#FF9B9B] text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center">{{ $badge }}</span>
    @endif
</a>
