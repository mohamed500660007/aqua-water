@props(['icon', 'label', 'href' => null, 'danger' => false])

@php
    $classes =
        'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-colors ' .
        ($danger ? 'text-red-500 hover:bg-red-50' : 'text-gray-700 hover:bg-gray-50');
@endphp

@if ($href)
    <a class="{{ $classes }}" href="{{ $href }}">
        <x-icon class="w-[18px] h-[18px] {{ $danger ? '' : 'text-gray-400' }}" :name="$icon" />
        {{ $label }}
    </a>
@else
    <button class="{{ $classes }}" type="button" {{ $attributes }}>
        <x-icon class="w-[18px] h-[18px] {{ $danger ? '' : 'text-gray-400' }}" :name="$icon" />
        {{ $label }}
    </button>
@endif
