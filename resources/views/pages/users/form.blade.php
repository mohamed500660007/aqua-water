@php
    $user = $user ?? null;
@endphp

<form action="{{ $action }}" method="POST" x-data="userForm()"
    x-on:submit="if (! validateAll()) $event.preventDefault()">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
            <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر حفظ البيانات — راجع الحقول التالية:</p>
            <ul class="list-disc pr-5 space-y-1">
                @foreach ($errors->all() as $message)
                    <li class="text-[12px] text-red-500">{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-4 lg:space-y-5">
        {{-- البيانات الأساسية --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="user-round" />
                </span>
                <h2 class="text-sm font-bold text-gray-800">البيانات الأساسية</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.field col="sm:col-span-2" label="الاسم بالكامل" name="name" placeholder="مثال: خالد منصور"
                    :value="$user?->name" client-error="errors.name" required x-on:blur="check($el)" x-on:input="check($el)" />

                <x-ui.field label="البريد الإلكتروني" name="email" type="email" placeholder="name@aquawater.eg"
                    :value="$user?->email" client-error="errors.email" required dir="ltr" x-on:blur="check($el)"
                    x-on:input="check($el)" />

                <x-ui.field label="رقم الهاتف" name="phone" placeholder="01xxxxxxxxx" :value="$user?->phone"
                    client-error="errors.phone" inputmode="tel" dir="ltr" x-on:blur="check($el)" x-on:input="check($el)" />
            </div>
        </section>

        {{-- الوظيفة --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="briefcase" />
                </span>
                <h2 class="text-sm font-bold text-gray-800">بيانات الوظيفة</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.field label="الدور الوظيفي" name="role" type="select" :options="$roles" :value="$user?->role"
                    placeholder="اختر الدور" />
                <x-ui.field label="القسم" name="department" type="select" :options="$departments" :value="$user?->department"
                    placeholder="اختر القسم" />
                <x-ui.field label="الفرع" name="branch" type="select" :options="$branches" :value="$user?->branch"
                    placeholder="اختر الفرع" />
                <x-ui.field label="الحالة" name="status" type="select" :options="$statuses"
                    :value="$user?->status?->value ?? $defaultStatus" placeholder="اختر الحالة" required />
            </div>
        </section>

        {{-- كلمة المرور --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="key-round" />
                </span>
                <div>
                    <h2 class="text-sm font-bold text-gray-800">كلمة المرور</h2>
                    <p class="text-[11px] text-gray-400">اختيارية — تُستخدم عند تفعيل تسجيل الدخول للنظام</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.field col="sm:col-span-1" label="كلمة المرور" name="password" type="password"
                    placeholder="8 أحرف على الأقل" client-error="errors.password" autocomplete="new-password"
                    :hint="$user ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية' : 'يمكن تركها فارغة وضبطها لاحقًا'"
                    x-on:blur="check($el)" x-on:input="check($el)" />
            </div>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
            <x-ui.btn class="sm:w-40" variant="outline" :href="$user ? route('users.show', $user) : route('users.index')">إلغاء</x-ui.btn>
            <x-ui.btn class="sm:w-48" icon="check" type="submit">{{ $submitLabel }}</x-ui.btn>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        // نموذج المستخدم: تحقق فوري في الواجهة يعكس نفس قواعد الـ Form Request على الخادم
        function userForm() {
            const PHONE_PATTERN = /^0\d{7,10}$/;
            const ARABIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';

            function normalizePhone(value) {
                let out = String(value || '')
                    .replace(/[٠-٩]/g, d => ARABIC_DIGITS.indexOf(d))
                    .replace(/\D/g, '');

                if (!out) return '';

                for (const prefix of ['0020', '20']) {
                    if (out.startsWith(prefix) && out.length > prefix.length + 7) {
                        out = out.slice(prefix.length);
                        break;
                    }
                }

                return out.startsWith('0') ? out : '0' + out;
            }

            return {
                errors: {},

                rules: {
                    name(value) {
                        if (!value.trim()) return 'اسم المستخدم مطلوب.';
                        return value.trim().length > 150 ? 'اسم المستخدم طويل جدًا.' : '';
                    },
                    email(value) {
                        if (!value.trim()) return 'البريد الإلكتروني مطلوب.';
                        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim()) ? '' : 'صيغة البريد الإلكتروني غير صحيحة.';
                    },
                    phone(value) {
                        if (!value.trim()) return '';
                        return PHONE_PATTERN.test(normalizePhone(value)) ? '' :
                            'رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.';
                    },
                    password(value) {
                        if (!value) return '';
                        return value.length < 8 ? 'كلمة المرور يجب ألا تقل عن 8 أحرف.' : '';
                    }
                },

                check(el) {
                    const rule = this.rules[el.name];
                    if (rule) this.errors[el.name] = rule(el.value);
                },

                validateAll() {
                    this.$el.querySelectorAll('input[name]').forEach(el => this.check(el));

                    return Object.values(this.errors).every(message => !message);
                }
            };
        }
    </script>
@endpush
