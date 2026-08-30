@props(['value' => 0, 'tone' => 'green', 'showLabel' => false])

<div class="flex items-center gap-2 w-full">
    <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
        <div class="h-full rounded-full transition-all duration-500 {{ \App\Support\ToneMap::solid($tone) }}"
            style="width: {{ max(0, min(100, $value)) }}%"></div>
    </div>
    @if ($showLabel)
        <span class="text-[11px] font-bold text-gray-600 w-9 text-left shrink-0">{{ round($value) }}%</span>
    @endif
</div>
