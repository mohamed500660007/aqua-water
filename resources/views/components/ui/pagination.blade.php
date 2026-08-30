{{-- ترقيم الصفحات — يعمل مع دالة dataTable --}}
<div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-gray-100" x-cloak
    x-show="total > 0">
    <p class="text-[11px] text-gray-400">
        عرض <span class="font-bold text-gray-600" x-text="from"></span>
        إلى <span class="font-bold text-gray-600" x-text="to"></span>
        من <span class="font-bold text-gray-600" x-text="total"></span> سجل
    </p>

    <div class="flex items-center gap-1">
        <button
            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-100 disabled:opacity-30 disabled:pointer-events-none transition-colors"
            type="button" x-bind:disabled="page === 1" x-on:click="go(page - 1)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2" viewBox="0 0 24 24">
                <path d="m9 18 6-6-6-6" />
            </svg>
        </button>

        <template x-bind:key="p" x-for="p in pageList">
            <button class="min-w-[32px] h-8 px-2 rounded-lg text-[12px] font-bold transition-colors" type="button"
                x-bind:class="p === page ? 'bg-brand-green text-brand-dark' : 'text-gray-500 hover:bg-gray-100'"
                x-on:click="go(p)" x-text="p"></button>
        </template>

        <button
            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-100 disabled:opacity-30 disabled:pointer-events-none transition-colors"
            type="button" x-bind:disabled="page === pages" x-on:click="go(page + 1)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2" viewBox="0 0 24 24">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>
    </div>
</div>
