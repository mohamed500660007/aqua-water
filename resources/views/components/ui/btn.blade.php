@props(['variant' => 'primary', 'size' => 'md', 'icon' => null, 'href' => null])

@php
    $variants = [
        'primary' => 'brand-green hover:bg-[#c2f040] shadow-sm',
        'dark' => 'bg-brand-dark text-white hover:bg-black',
        'soft' => 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-100',
        'ghost' => 'text-gray-500 hover:bg-gray-50',
        'danger' => 'bg-red-50 text-red-600 hover:bg-red-100',
        'outline' => 'border border-gray-200 text-gray-700 hover:bg-gray-50',
    ];
    $sizes = [
        'sm' => 'text-[11px] px-3 py-2 gap-1.5 rounded-lg',
        'md' => 'text-[13px] px-4 py-2.5 gap-2 rounded-xl',
        'lg' => 'text-sm px-5 py-3 gap-2 rounded-xl',
    ];
    $iconSize = $size === 'sm' ? 'w-3.5 h-3.5' : 'w-[18px] h-[18px]';
    $base =
        'inline-flex items-center justify-center font-bold whitespace-nowrap transition-all active:scale-95 disabled:opacity-50 disabled:pointer-events-none ' .
        ($variants[$variant] ?? $variants['primary']) .
        ' ' .
        ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a {{ $attributes->merge(['class' => $base]) }} href="{{ $href }}">
        @if ($icon)
            <x-icon class="{{ $iconSize }}" :name="$icon" />
        @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $base, 'type' => 'button']) }}>
        @if ($icon)
            <x-icon class="{{ $iconSize }}" :name="$icon" />
        @endif
        {{ $slot }}
    </button>
@endif
