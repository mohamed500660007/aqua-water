@props(['label' => null, 'name' => null, 'value' => '', 'placeholder' => 'اختر التاريخ', 'col' => '', 'error' => null, 'required' => false])

@php($error = $error ?? ($name ? $errors->first($name) : null))

{{-- حقل تاريخ بتسمية — منتقي التاريخ الموحّد (partials.shell.date-picker) يحوّله تلقائيًا إلى تقويم النظام --}}
<div class="{{ $col }}">
    @if ($label)
        <label class="block text-[12px] font-bold text-gray-600 mb-1.5" @if ($name) for="{{ $name }}" @endif>
            {{ $label }}
            @if ($required)
                <span class="text-red-400">*</span>
            @endif
        </label>
    @endif

    <input {{ $attributes->merge([
        'class' => 'w-full h-12 px-3.5 rounded-xl bg-white border text-base lg:text-[13px] text-gray-800 focus:ring-2 outline-none transition '
            . ($error ? 'border-red-300 focus:ring-red-200' : 'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'),
    ]) }} @if ($name) id="{{ $name }}" name="{{ $name }}" @endif placeholder="{{ $placeholder }}" type="date"
        value="{{ $name ? old($name, $value) : $value }}" @required($required) />

    @if ($error)
        <p class="text-xs font-semibold text-red-500 mt-1.5">{{ $error }}</p>
    @endif
</div>
