{{-- حاوية رسائل التنبيه — تُستدعى عبر $store.toast.success/error/info --}}
<div class="fixed top-4 left-1/2 -translate-x-1/2 sm:left-auto sm:right-6 sm:translate-x-0 z-[80] w-[calc(100%-2rem)] sm:w-[360px] space-y-2 pointer-events-none"
    x-cloak x-data>
    <template x-bind:key="t.id" x-for="t in $store.toast.items">
        <div class="pointer-events-auto flex items-start gap-3 bg-white border border-gray-100 rounded-2xl shadow-[0_12px_36px_-12px_rgba(26,26,26,0.35)] p-3.5"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:enter-start="opacity-0 -translate-y-3 scale-95"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100">

            <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center"
                x-bind:class="{
                    'bg-brand-green/30 text-green-700': t.type === 'success',
                    'bg-red-50 text-red-500': t.type === 'error',
                    'bg-blue-50 text-blue-500': t.type === 'info'
                }">
                {{-- أيقونات مرسومة مباشرة بنفس أسلوب Lucide (خطوط بسماكة 2 ونهايات دائرية) --}}
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" x-show="t.type === 'success'">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" x-show="t.type === 'error'">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 8v4" />
                    <path d="M12 16h.01" />
                </svg>
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" x-show="t.type === 'info'">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 16v-4" />
                    <path d="M12 8h.01" />
                </svg>
            </span>

            <p class="flex-1 text-[13px] font-semibold text-gray-700 leading-snug pt-1.5" x-text="t.message"></p>

            <button class="w-7 h-7 shrink-0 rounded-lg text-gray-300 hover:text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                type="button" x-on:click="$store.toast.remove(t.id)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
