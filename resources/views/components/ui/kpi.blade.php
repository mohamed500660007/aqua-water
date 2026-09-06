@props(['label', 'value', 'unit' => '', 'change' => null, 'up' => true, 'icon', 'tone' => 'green', 'hint' => null, 'compact' => false])

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm {{ $compact ? 'p-3' : 'p-4 lg:p-5' }}">
    <div class="flex items-start justify-between gap-3 {{ $compact ? 'mb-2' : 'mb-3' }}">
        <span
            class="rounded-xl flex items-center justify-center {{ $compact ? 'w-8 h-8' : 'w-11 h-11 rounded-2xl' }} {{ \App\Support\ToneMap::soft($tone) }}">
            <x-icon class="{{ $compact ? 'w-4 h-4' : 'w-5 h-5' }}" :name="$icon" />
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
    <p class="{{ $compact ? 'text-base lg:text-lg' : 'text-xl lg:text-2xl' }} font-bold text-gray-800 leading-none">
        {{ $value }}
        @if ($unit)
            <span class="text-xs font-semibold text-gray-400">{{ $unit }}</span>
        @endif
    </p>
    @if ($hint && ! $compact)
        <p class="text-[11px] text-gray-400 mt-2">{{ $hint }}</p>
    @endif
</div>
