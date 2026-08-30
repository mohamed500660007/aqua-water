@props(['label' => null, 'name' => null, 'value' => '', 'placeholder' => 'اختر التاريخ', 'col' => '', 'error' => null, 'required' => false])

@php($error = $error ?? ($name ? $errors->first($name) : null))

{{-- منتقي تاريخ بنفس شكل تقويم النظام --}}
<div class="{{ $col }}" x-data="datePicker('{{ $value }}')">
    @if ($label)
        <label class="block text-[12px] font-bold text-gray-600 mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-red-400">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if ($name)
            <input name="{{ $name }}" type="hidden" value="{{ $value }}" x-model="value" />
        @endif

        <button
            class="w-full h-11 pr-10 pl-9 rounded-xl bg-white border text-[13px] text-right focus:ring-2 outline-none transition flex items-center {{ $error ? 'border-red-300 focus:ring-red-200' : 'border-gray-200 focus:ring-brand-green/50' }}"
            type="button" x-on:click="open = !open">
            <span x-bind:class="value ? 'text-gray-800 font-medium' : 'text-gray-300'"
                x-text="value || '{{ $placeholder }}'"></span>
        </button>
        <x-icon class="w-4 h-4 absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
            name="calendar" />
        <x-icon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
            name="chevron-down" />

        <div class="absolute z-50 mt-2 w-[286px] bg-white rounded-2xl border border-gray-100 shadow-[0_20px_50px_-20px_rgba(26,26,26,0.35)] p-4"
            x-cloak x-on:click.outside="open = false" x-show="open"
            x-transition:enter="transition ease-out duration-150" x-transition:enter-end="opacity-100 scale-100"
            x-transition:enter-start="opacity-0 scale-95">

            <div class="flex items-center justify-between mb-3">
                <button class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-50"
                    type="button" x-on:click="move(-1)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
                <span class="text-[13px] font-bold text-gray-800" x-text="label"></span>
                <button class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-50"
                    type="button" x-on:click="move(1)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center mb-1">
                <template x-bind:key="d" x-for="d in days">
                    <span class="text-[10px] font-bold text-gray-400 py-1" x-text="d"></span>
                </template>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center">
                <template x-bind:key="i" x-for="(c, i) in cells">
                    <button class="h-8 rounded-lg text-[12px] font-medium transition-colors"
                        type="button" x-bind:class="!c.day ? 'invisible' : (c.iso === value ?
                            'bg-brand-green text-brand-dark font-bold' : 'text-gray-700 hover:bg-gray-100')"
                        x-on:click="pick(c.iso)" x-text="c.day"></button>
                </template>
            </div>

            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                <button class="text-[11px] font-bold text-gray-400 hover:text-gray-700" type="button"
                    x-on:click="clear()">مسح</button>
                <button class="text-[11px] font-bold text-brand-dark bg-brand-green px-3 py-1.5 rounded-lg"
                    type="button" x-on:click="open = false">تم</button>
            </div>
        </div>
    </div>

    @if ($error)
        <p class="text-[11px] font-semibold text-red-500 mt-1.5">{{ $error }}</p>
    @endif
</div>
