@extends('layouts.app')

@section('title', 'الموظفون')
@section('page-title', 'الموظفون')
@section('page-subtitle', 'إدارة مستخدمي لوحة التحكم')

@php
    $hasFilters = collect(request()->only(['q', 'status', 'role', 'department', 'branch']))->filter()->isNotEmpty();
@endphp

@section('content')
    <x-ui.page-header icon="user-cog" subtitle="مستخدمو لوحة التحكم" title="الموظفون">
        <x-slot:actions>
            <x-ui.btn icon="user-plus" size="sm" :href="route('users.create')">مستخدم جديد</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 lg:gap-4 mb-4 lg:mb-6">
        <x-ui.kpi hint="في كل الفروع" icon="users" label="إجمالي المستخدمين" tone="blue" unit="مستخدم"
            :value="number_format($stats['total'])" />
        <x-ui.kpi hint="على رأس العمل" icon="user-check" label="نشطون" tone="green" unit="مستخدم"
            :value="number_format($stats['active'])" />
        <x-ui.kpi hint="خارج العمل مؤقتًا" icon="calendar" label="في إجازة" tone="amber" unit="مستخدم"
            :value="number_format($stats['on_leave'])" />
        <x-ui.kpi hint="بدون وصول للنظام" icon="user-x" label="موقوفون" tone="red" unit="مستخدم"
            :value="number_format($stats['suspended'])" />
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- البحث والفلاتر تتم على الخادم مع الحفاظ على الترتيب الحالي --}}
        <form class="p-4 flex flex-col lg:flex-row lg:items-center gap-2.5 border-b border-gray-100"
            action="{{ route('users.index') }}" method="GET" x-data="{ q: @js(request('q', '')) }" x-on:submit.prevent="navigateForm($el)">
            <input name="sort" type="hidden" value="{{ request('sort') }}" />
            <input name="dir" type="hidden" value="{{ request('dir') }}" />

            <x-ui.search-input name="q" placeholder="ابحث بالاسم أو البريد أو رقم الهاتف..." value="{{ request('q') }}"
                x-model="q" x-on:input.debounce.450ms="navigateForm($el.form)" />

            <x-ui.select icon="briefcase" name="role" :options="$roles" placeholder="كل الأدوار" :selected="request('role')"
                x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="layers" name="department" :options="$departments" placeholder="كل الأقسام"
                :selected="request('department')" x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="map-pin" name="branch" :options="$branches" placeholder="كل الفروع" :selected="request('branch')"
                x-on:change="navigateForm($el.form)" />
            <x-ui.select icon="circle-dot" name="status" :options="$statuses" placeholder="كل الحالات"
                :selected="request('status')" x-on:change="navigateForm($el.form)" />

            <x-ui.btn icon="search" size="md" type="submit">بحث</x-ui.btn>

            @if ($hasFilters)
                <a class="shrink-0 h-11 px-3.5 rounded-xl text-[12px] font-bold text-gray-500 hover:text-brand-dark hover:bg-gray-50 border border-gray-200 transition-colors flex items-center gap-1.5"
                    href="{{ route('users.index') }}" wire:navigate>
                    <x-icon class="w-3.5 h-3.5" name="rotate-ccw" />
                    إعادة ضبط
                </a>
            @endif
        </form>

        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full min-w-[880px]">
                <thead class="bg-gray-50/70 border-b border-gray-100">
                    <tr>
                        <x-ui.sort-th column="name">المستخدم</x-ui.sort-th>
                        <x-ui.sort-th column="role">الدور</x-ui.sort-th>
                        <x-ui.th>القسم</x-ui.th>
                        <x-ui.th>الفرع</x-ui.th>
                        <x-ui.th>الهاتف</x-ui.th>
                        <x-ui.sort-th column="status">الحالة</x-ui.sort-th>
                        <x-ui.sort-th column="created_at">تاريخ الإضافة</x-ui.sort-th>
                        <x-ui.th align="left">إجراءات</x-ui.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($users as $user)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <a class="flex items-center gap-3 min-w-0" href="{{ route('users.show', $user) }}" wire:navigate>
                                    <x-ui.avatar :name="$user->name" size="w-10 h-10" />
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-gray-800 truncate">{{ $user->name }}</p>
                                        <p class="text-[11px] text-gray-400 truncate max-w-[180px]" dir="ltr">
                                            {{ $user->email }}</p>
                                    </div>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @if ($user->role)
                                    <span
                                        class="text-[11px] font-bold text-gray-600 bg-gray-100 px-2.5 py-1 rounded-full whitespace-nowrap">{{ $user->role }}</span>
                                @else
                                    <span class="text-[12px] text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">{{ $user->department ?? '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-gray-600">{{ $user->branch ?? '—' }}</td>
                            <td class="px-4 py-3 text-[12px] font-semibold text-gray-700" dir="ltr">
                                {{ $user->phone ?? '—' }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$user->status->label()" dot /></td>
                            <td class="px-4 py-3 text-[12px] text-gray-500" dir="ltr">
                                {{ $user->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.icon-btn icon="eye" label="عرض" :href="route('users.show', $user)" />
                                    <x-ui.icon-btn icon="pencil" label="تعديل" :href="route('users.edit', $user)" />
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.icon-btn icon="trash-2" label="حذف" tone="danger"
                                            x-on:click="$store.confirm.ask({
                                                title: 'حذف المستخدم',
                                                message: {{ Js::from('سيتم حذف حساب «' . $user->name . '» وإلغاء وصوله إلى لوحة التحكم.') }},
                                                label: 'حذف الحساب',
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

        @if ($users->isEmpty())
            <x-ui.empty-state icon="user-x" :title="$hasFilters ? 'لا يوجد مستخدمون مطابقون' : 'لا يوجد مستخدمون بعد'"
                :message="$hasFilters
                    ? 'جرّب تعديل كلمات البحث أو إعادة ضبط الفلاتر.'
                    : 'ابدأ بإضافة أول مستخدم للوحة التحكم.'">
                <x-slot:action>
                    @if ($hasFilters)
                        <x-ui.btn icon="rotate-ccw" size="sm" variant="soft" :href="route('users.index')">إعادة ضبط
                            الفلاتر</x-ui.btn>
                    @else
                        <x-ui.btn icon="user-plus" size="sm" :href="route('users.create')">إضافة مستخدم</x-ui.btn>
                    @endif
                </x-slot:action>
            </x-ui.empty-state>
        @endif

        <x-ui.paginator :paginator="$users" />
    </div>
@endsection
