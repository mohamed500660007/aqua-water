@props(['name' => '', 'size' => 'w-10 h-10'])

@php
    $parts = preg_split('/\s+/', trim($name));
    $initials = mb_substr($parts[0] ?? '', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
    $palette = [
        'bg-brand-green text-brand-dark',
        'bg-purple-100 text-purple-600',
        'bg-blue-100 text-blue-600',
        'bg-amber-100 text-amber-700',
        'bg-teal-100 text-teal-700',
        'bg-red-100 text-red-500',
    ];
    $color = $palette[abs(crc32($name)) % count($palette)];
@endphp

<span
    {{ $attributes->merge(['class' => $size . ' ' . $color . ' shrink-0 rounded-full flex items-center justify-center font-bold text-xs select-none']) }}>{{ $initials }}</span>
