@props(['title', 'subtitle' => null, 'icon' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div class="flex items-center gap-3 min-w-0">
        @if ($icon)
            <span class="w-11 h-11 shrink-0 rounded-2xl bg-brand-dark text-white flex items-center justify-center lg:hidden">
                <x-icon class="w-5 h-5" :name="$icon" />
            </span>
        @endif
        <div class="min-w-0">
            <h1 class="text-lg lg:text-xl font-bold text-gray-800 truncate lg:hidden">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-xs text-gray-400 mt-0.5 lg:hidden">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>
