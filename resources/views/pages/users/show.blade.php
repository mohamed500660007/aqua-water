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

                @if ($user->nationalIdNeedsRenewal())
                    <div class="mb-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[12px] text-amber-800">
                        <x-icon class="w-4 h-4 mt-0.5 shrink-0" name="triangle-alert" />
                        <span>بطاقة الرقم القومي {{ $user->national_id_expires_at->isPast() ? 'منتهية منذ' : 'تنتهي في' }}
                            <b dir="ltr">{{ $user->national_id_expires_at->format('Y-m-d') }}</b> — تحتاج تجديدًا.</span>
                    </div>
                @endif

                <x-ui.detail-row label="رقم الهاتف"><span dir="ltr">{{ $user->phone ?? '—' }}</span></x-ui.detail-row>
                <x-ui.detail-row label="هاتف إضافي"><span dir="ltr">{{ $user->secondary_phone ?? '—' }}</span></x-ui.detail-row>
                <x-ui.detail-row label="الرقم القومي"><span dir="ltr">{{ $user->national_id ?? '—' }}</span></x-ui.detail-row>
                <x-ui.detail-row label="العمر" :value="$user->age() !== null ? $user->age() . ' سنة' : '—'" />
                <x-ui.detail-row label="تاريخ التعيين" :value="$user->hire_date?->format('Y-m-d') ?? '—'" />
                <x-ui.detail-row label="تاريخ الإضافة" :value="$user->created_at?->format('Y-m-d')" />
                <x-ui.detail-row label="آخر تعديل" :value="$user->updated_at?->format('Y-m-d')" />
                <x-ui.detail-row label="كلمة المرور" :value="$user->password ? 'مضبوطة' : 'غير مضبوطة'" />
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4 lg:space-y-5">
            @foreach ([
                ['بيانات بطاقة الرقم القومي', 'id-card', 'bg-amber-50 text-amber-600', [
                    ['الرقم القومي', $user->national_id, true],
                    ['تاريخ الإصدار', $user->national_id_issued_at?->format('Y-m-d'), true],
                    ['تاريخ الانتهاء', $user->national_id_expires_at?->format('Y-m-d'), true],
                    ['جهة الإصدار', $user->national_id_issuer],
                    ['المهنة المدوّنة', $user->card_occupation],
                    ['الديانة', $user->religion],
                    ['الحالة الاجتماعية', $user->marital_status?->label()],
                    ['العنوان المدوّن بالبطاقة', $user->card_address],
                ]],
                ['البيانات الشخصية', 'contact-round', 'bg-pink-50 text-pink-600', [
                    ['تاريخ الميلاد', $user->birth_date?->format('Y-m-d'), true],
                    ['العمر', $user->age() !== null ? $user->age() . ' سنة' : null],
                    ['النوع', $user->gender?->label()],
                    ['محل الميلاد', $user->birth_place],
                    ['الجنسية', $user->nationality],
                    ['المؤهل الدراسي', $user->qualification],
                    ['الموقف من التجنيد', $user->military_status?->label()],
                ]],
                ['السكن الحالي', 'map-pin', 'bg-blue-50 text-blue-500', [
                    ['المحافظة', $user->governorate],
                    ['المركز / المدينة', $user->city],
                    ['العنوان التفصيلي', $user->address],
                ]],
                ['جهة اتصال للطوارئ', 'siren', 'bg-red-50 text-red-500', [
                    ['الاسم', $user->emergency_contact_name],
                    ['صلة القرابة', $user->emergency_contact_relation],
                    ['رقم الهاتف', $user->emergency_contact_phone, true],
                ]],
            ] as [$title, $icon, $tone, $rows])
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="w-9 h-9 rounded-xl {{ $tone }} flex items-center justify-center">
                            <x-icon class="w-[18px] h-[18px]" :name="$icon" />
                        </span>
                        <h2 class="text-sm font-bold text-gray-800">{{ $title }}</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                        @foreach ($rows as $row)
                            <x-ui.detail-row :label="$row[0]">
                                @if ($row[2] ?? false)
                                    <span dir="ltr">{{ $row[1] ?? '—' }}</span>
                                @else
                                    {{ $row[1] ?? '—' }}
                                @endif
                            </x-ui.detail-row>
                        @endforeach
                    </div>
                </div>
            @endforeach

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
                <x-ui.detail-row label="تاريخ التعيين" :value="$user->hire_date?->format('Y-m-d') ?? '—'" />
                <x-ui.detail-row label="المرتب الثابت" :value="number_format((float) $user->base_salary, 2) . ' ج.م'" />
                <x-ui.detail-row label="عمولة القسط" :value="number_format((float) $user->installment_commission, 2) . ' ج.م'" />
                <x-ui.detail-row label="عمولة تركيب الجهاز" :value="number_format((float) $user->installation_commission, 2) . ' ج.م'" />
                <x-ui.detail-row label="عمولة تسويق الجهاز" :value="number_format((float) $user->marketing_commission, 2) . ' ج.م'" />
                <x-ui.detail-row label="عمولة عميل الصيانة" :value="number_format((float) $user->maintenance_commission, 2) . ' ج.م'" />
            </div>

            @if ($user->notes)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-gray-800 mb-2">
                        <x-icon class="w-4 h-4 text-gray-400" name="notebook-pen" /> ملاحظات
                    </h2>
                    <p class="text-[13px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $user->notes }}</p>
                </div>
            @endif

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
