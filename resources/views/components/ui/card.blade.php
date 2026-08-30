@props(['title' => null, 'subtitle' => null, 'padding' => 'p-4 lg:p-5'])

<section {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-gray-100 ' . $padding]) }}>
    @if ($title || isset($action))
        <div class="flex items-start justify-between gap-3 mb-4">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-base lg:text-lg font-bold text-gray-800 truncate">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($action)
                <div class="shrink-0 flex items-center gap-2">{{ $action }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</section>
