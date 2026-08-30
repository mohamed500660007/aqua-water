@props(['label', 'value' => null, 'model' => null])

<div class="flex items-start justify-between gap-4 py-2.5 border-b border-gray-50 last:border-0">
    <span class="text-[12px] text-gray-400 shrink-0">{{ $label }}</span>
    @if ($model)
        <span class="text-[13px] font-semibold text-gray-800 text-left" x-text="{{ $model }}"></span>
    @else
        <span class="text-[13px] font-semibold text-gray-800 text-left">{{ $value ?? $slot }}</span>
    @endif
</div>
