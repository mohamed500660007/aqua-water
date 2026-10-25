{{-- نافذة تأكيد عامة تُستدعى عبر $store.confirm.ask({ ... }) --}}
<div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center" x-cloak x-data
    x-show="$store.confirm.open">
    <div class="absolute inset-0 bg-brand-dark/60 backdrop-blur-[2px]" x-on:click="$store.confirm.close()"
        x-show="$store.confirm.open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-end="opacity-100" x-transition:enter-start="opacity-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0"
        x-transition:leave-start="opacity-100"></div>

    <div class="relative w-full sm:max-w-sm bg-white rounded-t-[28px] sm:rounded-3xl shadow-2xl p-6 pb-safe-sheet sm:pb-6"
        x-on:keydown.escape.window="$store.confirm.close()" x-show="$store.confirm.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100">

        <div class="ui-confirm-art" aria-hidden="true">
            <span class="ui-confirm-icon"><x-icon class="w-6 h-6" name="triangle-alert" /></span>
        </div>

        <h3 class="text-base font-extrabold text-gray-900 text-center mb-2" x-text="$store.confirm.title"></h3>
        <p class="text-[13px] text-gray-500 text-center leading-relaxed mb-6" x-text="$store.confirm.message"></p>

        <div class="flex gap-3">
            <button class="ui-btn ui-btn-soft ui-btn-md flex-1" type="button" x-on:click="$store.confirm.close()">إلغاء</button>
            <button class="ui-btn ui-btn-danger-solid ui-btn-md flex-1" type="button" x-on:click="$store.confirm.accept()"
                x-text="$store.confirm.label"></button>
        </div>
    </div>
</div>
