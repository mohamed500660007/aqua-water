@extends('layouts.app')

@section('title', 'المنتجات')
@section('page-title', 'المنتجات')
@section('page-subtitle', 'كتالوج المنتجات والفلاتر')

@php
    $hasFilters = collect(request()->only(['q', 'category', 'kind', 'status']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="package" subtitle="كتالوج المنتجات والفلاتر" title="المنتجات">
        <x-slot:actions>
            {{-- تُفتح في تبويب مستقل لأنها صفحة طباعة بلا هيكل التطبيق، وتحمل نفس فلاتر القائمة --}}
            <x-ui.btn icon="printer" size="sm" variant="dark" document :href="route('products.report', request()->only(['q', 'category', 'kind', 'status', 'sort', 'dir']))">تقرير المنتجات</x-ui.btn>
            <x-ui.btn icon="package-plus" size="sm" variant="soft" :href="route('inventory.stock.create')">إضافة إلى
                المخزن</x-ui.btn>
            <x-ui.btn icon="plus" size="sm" :href="route('products.create')">منتج جديد</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('products.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث باسم المنتج أو الكود..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            @if ($categories)
                <x-ui.select icon="layers" name="category" :options="$categories" placeholder="كل التصنيفات"
                    :selected="request('category')" x-on:change="navigateForm($el.form)" />
            @endif
            <x-ui.select icon="shapes" name="kind" :options="$kinds" placeholder="كل الأنواع"
                :selected="request('kind')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="circle-dot" name="status" :options="$statuses" placeholder="كل الحالات"
                :selected="request('status')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('products.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[820px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.sort-th column="name">المنتج</x-ui.sort-th>
                        <x-ui.sort-th column="category">التصنيف</x-ui.sort-th>
                        <x-ui.sort-th column="price">سعر البيع</x-ui.sort-th>
                        <x-ui.th>التكلفة</x-ui.th>
                        <x-ui.th>الرصيد</x-ui.th>
                        <x-ui.sort-th column="status">الحالة</x-ui.sort-th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($products as $product)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="min-w-0 block" href="{{ route('products.show', $product) }}" wire:navigate>
                                    <p class="text-[13px] font-bold text-gray-800 truncate">{{ $product->name }}</p>
                                    <p class="text-[11px] text-gray-400" dir="ltr">{{ $product->sku }}</p>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">{{ $product->category ?? '—' }}
                                <span class="block text-[10px] text-gray-400">{{ $product->kind->label() }}</span></td>
                            <td class="px-4 py-3">
                                <span class="text-[13px] font-bold text-gray-800">
                                    {{ number_format((float) $product->price) }}</span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">
                                {{ $product->cost ? number_format((float) $product->cost) . ' ج.م' : '—' }}</td>
                            <td class="px-4 py-3 text-[13px] font-bold text-gray-700">
                                {{ number_format((int) $product->quantity) }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$product->status->label()" dot /></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="عرض" :href="route('products.show', $product)" />
                                    <x-ui.icon-btn icon="pencil" label="تعديل" :href="route('products.edit', $product)" />
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف المنتج',
                                                message: {{ Js::from('سيتم حذف «' . $product->name . '» من الكتالوج. العقود وحركات المخزون السابقة تبقى كما هي.') }},
                                                label: 'حذف',
                                                onOk: () => $root.requestSubmit()
                                            })" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($products->isEmpty())
            <x-ui.empty-state icon="package-x" :title="$hasFilters ? 'لا توجد منتجات مطابقة' : 'لا توجد منتجات بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'أضف أول منتج إلى الكتالوج لتتمكن من بيعه وإدارة مخزونه.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('products.index')">إعادة ضبط
                            الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="plus" size="sm" :href="route('products.create')">إضافة منتج</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$products" />
    </div>
@endsection
