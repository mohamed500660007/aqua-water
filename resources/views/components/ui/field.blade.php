@props([
    'label',
    'name' => null,
    'value' => null,
    'model' => null,
    'type' => 'text',
    'placeholder' => '',
    'options' => null,
    'rows' => 3,
    'hint' => null,
    'required' => false,
    'col' => 'sm:col-span-1',
    'error' => null,
    'clientError' => null,
])

@php
    $error = $error ?? ($name ? $errors->first($name) : null);
    $current = $name ? old($name, $value) : $value;
    $choices = $options ?? [];
    $choices = array_is_list($choices) ? array_combine($choices, $choices) : $choices;

    $base =
        'w-full h-12 px-3.5 rounded-xl bg-white border text-base lg:text-[13px] text-gray-800 focus:ring-2 outline-none transition placeholder:text-gray-300 ' .
        ($error
            ? 'border-red-300 focus:ring-red-200 focus:border-red-400'
            : 'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green');

@endphp

<div class="{{ $col }}">
    <label class="block text-[12px] font-bold text-gray-600 mb-1.5" @if ($name) for="{{ $name }}" @endif>
        {{ $label }}
        @if ($required)
            <span class="text-red-400">*</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea {{ $attributes->merge(['class' => str_replace('h-11 ', 'py-2.5 ', $base) . ' resize-none']) }}
            @if ($name) id="{{ $name }}" name="{{ $name }}" @endif placeholder="{{ $placeholder }}"
            rows="{{ $rows }}" @if ($model) x-model="{{ $model }}" @endif>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <x-ui.dropdown :error="(bool) $error" :options="$choices" :placeholder="$placeholder ?: 'اختر...'"
            :selected="$current" :id="$name" :name="$name" :x-model="$model" {{ $attributes }} />
    @else
        <input {{ $attributes->merge(['class' => $base]) }} @if ($name) id="{{ $name }}" name="{{ $name }}" @endif
            placeholder="{{ $placeholder }}" type="{{ $type }}" value="{{ $current }}"
            @if ($model) x-model="{{ $model }}" @endif />
    @endif

    @if ($error)
        <p class="text-xs font-semibold text-red-500 mt-1.5">{{ $error }}</p>
    @elseif ($clientError)
        <p class="text-xs font-semibold text-red-500 mt-1.5" x-cloak x-show="{{ $clientError }}"
            x-text="{{ $clientError }}"></p>
        @if ($hint)
            <p class="text-xs text-gray-400 mt-1.5" x-show="!{{ $clientError }}">{{ $hint }}</p>
        @endif
    @elseif ($hint)
        <p class="text-xs text-gray-400 mt-1.5">{{ $hint }}</p>
    @endif
</div>
