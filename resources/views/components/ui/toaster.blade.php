{{-- حاوية رسائل التنبيه — تُستدعى عبر $store.toast.success/error/info. جزيرة داكنة بنفس لغة شريط التنقل. --}}
<div class="fixed top-4 left-1/2 -translate-x-1/2 sm:left-auto sm:right-6 sm:translate-x-0 z-[80] w-[calc(100%-2rem)] sm:w-[360px] space-y-2 pointer-events-none"
    x-cloak x-data>
    <template x-bind:key="t.id" x-for="t in $store.toast.items">
        <div class="ui-toast pointer-events-auto" role="status"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:enter-start="opacity-0 -translate-y-4 scale-90"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100">

            <span class="ui-toast-icon" x-bind:class="'is-' + t.type">
                {{-- أيقونات مرسومة مباشرة بنفس أسلوب Lucide (خطوط بسماكة 2 ونهايات دائرية) --}}
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24" x-show="t.type === 'success'">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24" x-show="t.type === 'error'">
                    <path d="M12 8v4" />
                    <path d="M12 16h.01" />
                </svg>
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2.5" viewBox="0 0 24 24" x-show="t.type === 'info'">
                    <path d="M12 16v-4" />
                    <path d="M12 8h.01" />
                </svg>
            </span>

            <p class="flex-1 text-[13px] font-semibold leading-snug pt-2" x-text="t.message"></p>

            <button class="ui-toast-close" type="button" aria-label="إغلاق التنبيه" x-on:click="$store.toast.remove(t.id)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
