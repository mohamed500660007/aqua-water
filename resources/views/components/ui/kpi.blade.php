@props(['label', 'value', 'unit' => '', 'change' => null, 'up' => true, 'icon', 'tone' => 'green', 'hint' => null])

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 lg:p-5">
    <div class="flex items-start justify-between gap-3 mb-3">
        <span class="w-11 h-11 rounded-2xl flex items-center justify-center {{ \App\Support\ToneMap::soft($tone) }}">
            <x-icon class="w-5 h-5" :name="$icon" />
        </span>
        @if ($change)
            <span
                class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-full {{ $up ? 'bg-brand-green/30 text-green-700' : 'bg-red-50 text-red-500' }}">
                <x-icon class="w-3 h-3" :name="$up ? 'trending-up' : 'trending-down'" />
                {{ $change }}
            </span>
        @endif
    </div>
    <p class="text-xs text-gray-400 mb-1">{{ $label }}</p>
    <p class="text-xl lg:text-2xl font-bold text-gray-800 leading-none">
        {{ $value }}
        @if ($unit)
            <span class="text-xs font-semibold text-gray-400">{{ $unit }}</span>
        @endif
    </p>
    @if ($hint)
        <p class="text-[11px] text-gray-400 mt-2">{{ $hint }}</p>
    @endif
</div>
