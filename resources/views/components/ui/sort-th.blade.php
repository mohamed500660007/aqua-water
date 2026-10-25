@props(['column', 'align' => 'right'])

@php
    $active = request('sort') === $column;
    $direction = $active && request('dir') === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $direction, 'page' => null]);
    $arrow = $active ? ($direction === 'desc' ? 'rotate-180' : '') : 'opacity-0 group-hover:opacity-50';
@endphp

{{-- العمود المرتَّب به حاليًا يظهر كشريحة داكنة بسهم ليموني --}}
<th class="ui-th text-{{ $align }}">
    <a class="ui-th-sort group {{ $active ? 'is-active' : '' }}" href="{{ $url }}" wire:navigate>
        {{ $slot }}
        <svg class="{{ $arrow }}" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
            stroke-width="2.5" viewBox="0 0 24 24">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </a>
</th>
