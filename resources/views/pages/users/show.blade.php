@extends('layouts.app')

@section('title', $user->name)
@section('page-title', $user->name)
@section('page-subtitle', 'تفاصيل المستخدم')

@section('content')
    <x-ui.page-header icon="user-round" :subtitle="$user->role ?? 'بدون دور محدد'" :title="$user->name">
        <x-slot:actions>
            <x-ui.btn icon="arrow-right" size="sm" variant="soft" :href="route('users.index')">رجوع للقائمة</x-ui.btn>
            <x-ui.btn icon="pencil" size="sm" :href="route('users.edit', $user)">تعديل البيانات</x-ui.btn>

            <form action="{{ route('users.destroy', $user) }}" method="POST" x-data>
                @csrf
                @method('DELETE')
                <x-ui.btn icon="trash-2" size="sm" variant="danger"
                    x-on:click="$store.confirm.ask({
                        title: 'حذف المستخدم',
                        message: {{ Js::from('سيتم حذف حساب «' . $user->name . '» وإلغاء وصوله إلى لوحة التحكم.') }},
                        label: 'حذف الحساب',
                        onOk: () => $root.requestSubmit()
                    })">حذف</x-ui.btn>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-4 mb-5">
                    <x-ui.avatar :name="$user->name" size="w-14 h-14 text-base" />
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-800 truncate">{{ $user->name }}</p>
                        <p class="text-[12px] text-gray-400 truncate" dir="ltr">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 mb-4">
                    <span class="text-[12px] text-gray-400">الحالة</span>
                    <x-ui.badge :status="$user->status->label()" dot />
                </div>

                <x-ui.detail-row label="رقم الهاتف" :value="$user->phone ?? '—'" />
                <x-ui.detail-row label="تاريخ الإضافة" :value="$user->created_at?->format('Y-m-d')" />
                <x-ui.detail-row label="آخر تعديل" :value="$user->updated_at?->format('Y-m-d')" />
                <x-ui.detail-row label="كلمة المرور" :value="$user->password ? 'مضبوطة' : 'غير مضبوطة'" />
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4 lg:space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="briefcase" />
                    </span>
                    <h2 class="text-sm font-bold text-gray-800">بيانات الوظيفة</h2>
                </div>

                <x-ui.detail-row label="الدور الوظيفي" :value="$user->role ?? '—'" />
                <x-ui.detail-row label="القسم" :value="$user->department ?? '—'" />
                <x-ui.detail-row label="الفرع" :value="$user->branch ?? '—'" />
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="shield-check" />
                    </span>
                    <h2 class="text-sm font-bold text-gray-800">الوصول للنظام</h2>
                </div>

                <p class="text-[13px] text-gray-600 leading-relaxed">
                    لا يحتوي النظام على تسجيل دخول حتى الآن، لذلك يُدار حساب المستخدم من داخل لوحة التحكم فقط.
                    عند تفعيل تسجيل الدخول سيُستخدم البريد الإلكتروني مع كلمة المرور المحفوظة.
                </p>
            </div>
        </div>
    </div>
@endsection
