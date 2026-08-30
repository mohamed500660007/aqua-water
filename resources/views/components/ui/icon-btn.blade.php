@props(['icon', 'tone' => 'default', 'label' => '', 'href' => null])

@php
    $tones = [
        'default' => 'text-gray-400 hover:text-brand-dark hover:bg-gray-100',
        'danger' => 'text-gray-400 hover:text-red-600 hover:bg-red-50',
        'primary' => 'text-gray-400 hover:text-brand-dark hover:bg-brand-green/40',
    ];
@endphp

@php($classes = 'w-9 h-9 shrink-0 rounded-xl flex items-center justify-center transition-colors active:scale-90 ' . ($tones[$tone] ?? $tones['default']))

@if ($href)
    <a aria-label="{{ $label }}" href="{{ $href }}" title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}>
        <x-icon class="w-[17px] h-[17px]" :name="$icon" />
    </a>
@else
    <button aria-label="{{ $label }}" title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        <x-icon class="w-[17px] h-[17px]" :name="$icon" />
    </button>
@endif
