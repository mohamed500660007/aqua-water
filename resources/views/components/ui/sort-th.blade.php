@props(['column', 'align' => 'right'])

@php
    $active = request('sort') === $column;
    $direction = $active && request('dir') === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $direction, 'page' => null]);
    $arrow = $active ? ($direction === 'desc' ? 'opacity-100 rotate-180' : 'opacity-100') : 'opacity-0 group-hover:opacity-40';
@endphp

<th class="px-4 py-3 text-{{ $align }} text-[11px] font-bold text-gray-400 uppercase tracking-wide whitespace-nowrap select-none">
    <a class="inline-flex items-center gap-1.5 group hover:text-gray-700 transition-colors" href="{{ $url }}">
        {{ $slot }}
        <svg class="w-3 h-3 transition-all duration-200 {{ $arrow }}" fill="none" stroke="currentColor"
            stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </a>
</th>
