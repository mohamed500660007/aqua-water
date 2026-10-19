{{-- تنقل موحّد بين صفحات الصيانة الأربع بدل أزرار متفرقة في كل صفحة --}}
@php
    $tabs = [
        'maintenance.file' => ['calendar-clock', 'المستحقين'],
        'maintenance.operations.index' => ['history', 'سجل الصيانة'],
        'maintenance.packages.index' => ['package-check', 'عقود الجملة'],
        'maintenance.stages.edit' => ['sliders-horizontal', 'إعداد الشمع'],
    ];
@endphp

<nav class="mb-4 flex gap-1.5 overflow-x-auto scrollbar-hide rounded-2xl bg-white border border-gray-100 shadow-sm p-1.5">
    @foreach ($tabs as $route => [$icon, $label])
        @php($active = request()->routeIs($route))
        <a class="shrink-0 flex items-center gap-2 h-10 px-4 rounded-xl text-[12.5px] font-bold transition-colors
                {{ $active ? 'bg-brand-dark text-brand-green' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}"
            href="{{ route($route) }}" wire:navigate @if ($active) aria-current="page" @endif>
            <x-icon class="w-4 h-4" :name="$icon" />
            {{ $label }}
        </a>
    @endforeach
</nav>
