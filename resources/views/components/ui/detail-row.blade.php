@props(['label', 'value' => null, 'model' => null])

<div class="ui-detail">
    <span class="ui-detail-label">{{ $label }}</span>
    @if ($model)
        <span class="ui-detail-value" x-text="{{ $model }}"></span>
    @else
        <span class="ui-detail-value">{{ $value ?? $slot }}</span>
    @endif
</div>
