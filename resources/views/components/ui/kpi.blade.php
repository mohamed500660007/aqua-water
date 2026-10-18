@props(['label', 'value', 'unit' => '', 'change' => null, 'up' => true, 'icon', 'tone' => 'green', 'hint' => null, 'compact' => false])

{{-- bg-white rounded-2xl مطلوبان: سكربت الهاتف يكتشف صف المؤشرات بهما ليحوّله لشريط سحب --}}
<div class="ui-kpi ui-tone-{{ $tone }} {{ $compact ? 'is-compact' : '' }} bg-white rounded-2xl border border-gray-100 shadow-sm">
    <x-icon class="ui-kpi-glyph" :name="$icon" />

    <div class="ui-kpi-head">
        <span class="ui-kpi-icon"><x-icon :name="$icon" /></span>
        @if ($change)
            <span class="ui-kpi-change ui-badge ui-tone-{{ $up ? 'green' : 'red' }}">
                <x-icon :name="$up ? 'trending-up' : 'trending-down'" />
                {{ $change }}
            </span>
        @endif
    </div>

    <p class="ui-kpi-label">{{ $label }}</p>
    <p class="ui-kpi-value">
        {{ $value }}
        @if ($unit)
            <small>{{ $unit }}</small>
        @endif
    </p>
    @if ($hint && ! $compact)
        <p class="ui-kpi-hint">{{ $hint }}</p>
    @endif
</div>
