@props(['name' => '', 'size' => 'w-10 h-10'])

@php
    $parts = preg_split('/\s+/', trim($name));
    $initials = mb_substr($parts[0] ?? '', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
    // نفس الاسم يأخذ دائمًا نفس اللون من ستة تدرّجات
    $palette = abs(crc32($name)) % 6;
@endphp

<span {{ $attributes->merge(['class' => "{$size} ui-avatar ui-avatar-{$palette}"]) }}>{{ $initials }}</span>
