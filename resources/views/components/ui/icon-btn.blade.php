{{-- document: رابط لصفحة طباعة مستقلة (إيصال، قسيمة) — يُفتح في تبويب جديد بتحميل كامل لا بتنقّل SPA --}}
@props(['icon', 'tone' => 'default', 'label' => '', 'href' => null, 'document' => false])

@php
    $classes = 'ui-icon-btn ' . match ($tone) {
        'primary' => 'is-primary',
        'danger' => 'is-danger',
        default => '',
    };
@endphp

@if ($href)
    <a aria-label="{{ $label }}" href="{{ $href }}" title="{{ $label }}"
        @if ($document) target="_blank" rel="noopener" @else wire:navigate @endif
        {{ $attributes->merge(['class' => $classes]) }}>
        <x-icon :name="$icon" />
    </a>
@else
    <button aria-label="{{ $label }}" title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        <x-icon :name="$icon" />
    </button>
@endif
