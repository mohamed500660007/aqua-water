@props([
    'store' => 'modal',
    'name' => null,
    'title' => '',
    'subtitle' => null,
    'size' => 'sm:max-w-lg',
    'icon' => null,
])

@php
    $cond = $name ? "\$store.modal.is('" . $name . "')" : '$store.' . $store . '.open';
    $closeFn = $name ? '$store.modal.hide()' : '$store.' . $store . '.close()';
@endphp

<div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center" x-cloak x-data
    x-show="{{ $cond }}">
    {{-- الخلفية المعتمة --}}
    <div class="absolute inset-0 bg-brand-dark/50 backdrop-blur-[2px]" x-on:click="{{ $closeFn }}"
        x-show="{{ $cond }}" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-end="opacity-100" x-transition:enter-start="opacity-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0"
        x-transition:leave-start="opacity-100"></div>

    {{-- اللوحة: bottom sheet على الهاتف، نافذة في المنتصف على الشاشات الأكبر --}}
    <div class="relative w-full {{ $size }} bg-white rounded-t-[28px] sm:rounded-3xl shadow-2xl max-h-[92dvh] sm:max-h-[88dvh] flex flex-col overflow-hidden"
        x-on:keydown.escape.window="{{ $closeFn }}" x-show="{{ $cond }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100">

        {{-- مقبض السحب على الهاتف --}}
        <div class="pt-3 pb-1 sm:hidden shrink-0">
            <span class="block w-10 h-1.5 rounded-full bg-gray-200 mx-auto"></span>
        </div>

        <div class="flex items-start justify-between gap-3 px-5 pt-4 pb-4 sm:pt-6 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                @if ($icon)
                    <span
                        class="w-10 h-10 shrink-0 rounded-2xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                        <x-icon class="w-5 h-5" :name="$icon" />
                    </span>
                @endif
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-800 truncate">
                        @isset($heading)
                            {{ $heading }}
                        @else
                            {{ $title }}
                        @endisset
                    </h3>
                    @if ($subtitle)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            <button
                class="w-9 h-9 shrink-0 rounded-full bg-gray-50 text-gray-500 hover:bg-gray-100 flex items-center justify-center active:scale-90 transition-all"
                type="button" x-on:click="{{ $closeFn }}">
                <x-icon class="w-[18px] h-[18px]" name="x" />
            </button>
        </div>

        <div class="px-5 py-5 overflow-y-auto flex-1">{{ $slot }}</div>

        @isset($footer)
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/60 shrink-0 pb-safe-sheet sm:pb-4">{{ $footer }}
            </div>
        @endisset
    </div>
</div>
