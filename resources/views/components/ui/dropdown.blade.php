@props([
    'options' => [],
    'placeholder' => 'اختر...',
    'selected' => null,
    'icon' => null,
    'error' => false,
    'width' => 'w-full',
])

@php
    $choices = array_is_list($options) ? array_combine($options, $options) : $options;

    // النص الظاهر قبل تشغيل Alpine حتى لا يظهر الزر فارغًا للحظة
    $selectedLabel = $selected !== null && isset($choices[$selected]) ? $choices[$selected] : null;

    $trigger = $width . ' ui-input ui-select-trigger h-12 lg:h-11 text-base lg:text-[13px] text-right flex items-center gap-2 cursor-pointer '
        . ($icon ? 'pr-10 pl-9 ' : 'pr-3.5 pl-9 ')
        . ($error ? 'is-invalid ' : '')
        . ($selectedLabel ? 'is-filled' : '');
@endphp

{{-- قائمة منسدلة بنفس نظام التصميم — القائمة الأصلية تبقى مخفية لتعمل مع الفورم و x-model كما هي.
     القيمة المختارة تلوّن الزر ليمونيًا حتى تظهر الفلاتر النشطة بنظرة واحدة. --}}
<div class="relative {{ $width === 'w-full' ? '' : 'shrink-0' }}" x-data="styledSelect()"
    x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">

    <select {{ $attributes->merge(['class' => 'sr-only']) }} tabindex="-1" x-ref="native">
        <option value="">{{ $placeholder }}</option>
        @foreach ($choices as $value => $label)
            <option @selected($selected !== null && (string) $selected === (string) $value) value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    <button aria-haspopup="listbox" class="{{ $trigger }}" type="button" x-bind:aria-expanded="open"
        x-bind:class="{ 'is-filled': value }" x-on:click="open = ! open">
        <span class="ui-select-text truncate {{ $selectedLabel ? 'text-gray-800' : 'text-gray-400' }}"
            x-bind:class="value ? 'text-gray-800' : 'text-gray-400'"
            x-text="label">{{ $selectedLabel ?? $placeholder }}</span>
    </button>

    @if ($icon)
        <x-icon class="w-4 h-4 absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" :name="$icon" />
    @endif
    {{-- الربط على العنصر الحاوي لأن lucide يستبدل وسم الأيقونة نفسه بعد التحميل --}}
    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none transition-transform"
        x-bind:class="open && 'rotate-180'">
        <x-icon class="w-4 h-4" name="chevron-down" />
    </span>

    <div class="ui-menu absolute z-50 mt-2 min-w-full w-max max-w-[280px] max-h-[264px] overflow-y-auto scrollbar-hide"
        role="listbox" x-cloak x-show="open" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:enter-start="opacity-0 -translate-y-1">

        <template x-bind:key="option.value" x-for="option in options">
            <button class="ui-option" type="button" x-bind:class="{ 'is-selected': option.value === value }"
                x-on:click="choose(option.value)">
                <span class="truncate" x-text="option.label"></span>
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24" x-show="option.value === value">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </button>
        </template>
    </div>
</div>
