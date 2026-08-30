@props([
    'perPage' => 8,
    'filters' => '{}',
    'sortKey' => null,
    'emptyIcon' => 'search-x',
    'emptyTitle' => 'لا توجد نتائج مطابقة',
    'emptyMessage' => 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.',
    'minWidth' => 'min-w-[760px]',
])

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
    x-data="dataTable({ perPage: {{ $perPage }}, filters: {!! $filters !!}, sortKey: {{ $sortKey ? "'" . $sortKey . "'" : 'null' }} })">

    {{-- شريط الأدوات: بحث + فلاتر --}}
    @isset($toolbar)
        <div class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100">
            {{ $toolbar }}
            <button
                class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                type="button" x-cloak x-on:click="resetFilters()" x-show="hasFilters">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-linecap="round"
                    stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                    <path d="M3 3v5h5" />
                </svg>
                إعادة ضبط
            </button>
        </div>
    @endisset

    {{-- حالة التحميل --}}
    <div x-cloak x-show="loading">
        <x-ui.skeleton-table :cols="4" :rows="$perPage > 6 ? 6 : $perPage" />
    </div>

    <div x-show="!loading">
        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full {{ $minWidth }}">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>{{ $head }}</tr>
                </thead>
                <tbody class="divide-y divide-gray-50" x-ref="body">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        {{-- حالة الفراغ --}}
        <div x-cloak x-show="total === 0">
            <x-ui.empty-state :icon="$emptyIcon" :message="$emptyMessage" :title="$emptyTitle">
                <x-slot:action>
                    <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" x-on:click="resetFilters()">إعادة ضبط الفلاتر</x-ui.btn>
                </x-slot:action>
            </x-ui.empty-state>
        </div>
    </div>

    <x-ui.pagination />
</div>
