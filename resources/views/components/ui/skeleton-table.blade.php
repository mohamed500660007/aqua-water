@props(['rows' => 6, 'cols' => 5])

<div {{ $attributes->merge(['class' => 'p-4 space-y-3']) }}>
    @for ($r = 0; $r < $rows; $r++)
        <div class="flex items-center gap-4">
            <div class="skeleton w-10 h-10 rounded-xl shrink-0"></div>
            @for ($c = 0; $c < $cols; $c++)
                <div class="skeleton h-3 rounded-full flex-1" style="max-width: {{ [140, 90, 110, 70, 100][$c % 5] }}px"></div>
            @endfor
        </div>
    @endfor
</div>
