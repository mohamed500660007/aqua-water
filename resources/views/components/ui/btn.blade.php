{{-- document: رابط لصفحة طباعة مستقلة (إيصال، قسيمة) — يُفتح في تبويب جديد بتحميل كامل لا بتنقّل SPA.
     الأشكال (primary/dark/soft/outline/ghost/danger) معرّفة في partials.shell.page-chrome-assets
     بنفس لغة شريط التنقل: الأساسي ليموني بأيقونة في دائرة داكنة، والداكن بأيقونة ليمونية. --}}
@props(['variant' => 'primary', 'size' => 'md', 'icon' => null, 'href' => null, 'document' => false])

@php
    $variant = in_array($variant, ['primary', 'dark', 'soft', 'ghost', 'danger', 'outline'], true) ? $variant : 'primary';
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $classes = "ui-btn ui-btn-{$variant} ui-btn-{$size}";
@endphp

@if ($href)
    <a {{ $attributes->merge(['class' => $classes]) }} href="{{ $href }}"
        @if ($document) target="_blank" rel="noopener" @else wire:navigate @endif>
        @if ($icon)
            <span class="ui-btn-icon"><x-icon :name="$icon" /></span>
        @endif
        <span class="ui-btn-label">{{ $slot }}</span>
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        @if ($icon)
            <span class="ui-btn-icon"><x-icon :name="$icon" /></span>
        @endif
        <span class="ui-btn-label">{{ $slot }}</span>
    </button>
@endif
