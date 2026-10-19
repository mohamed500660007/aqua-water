@php
    $user = $user ?? null;
    $date = fn (string $field) => old($field, $user?->{$field}?->format('Y-m-d'));
@endphp

<form action="{{ $action }}" method="POST"
    x-data="userForm({ gender: @js(old('gender', $user?->gender?->value)), nationalId: @js(old('national_id', $user?->national_id)) })"
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

                <x-ui.field label="رقم هاتف إضافي" name="secondary_phone" placeholder="اختياري" :value="$user?->secondary_phone"
                    client-error="errors.secondary_phone" inputmode="tel" dir="ltr" x-on:blur="check($el)" x-on:input="check($el)" />
            </div>
        </section>

        {{-- الرقم القومي: مصدر البيانات المستخرجة، بدون تكرار تفاصيل البطاقة --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="id-card" />
                </span>
                <div>
                    <h2 class="text-sm font-bold text-gray-800">الرقم القومي</h2>
                    <p class="text-[11px] text-gray-400">يُستخرج منه تاريخ الميلاد والنوع ومحل الميلاد تلقائيًا، ويمكن مراجعتها في البيانات الشخصية</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-ui.field label="الرقم القومي" name="national_id" placeholder="14 رقمًا" :value="$user?->national_id"
                        client-error="errors.national_id" inputmode="numeric" dir="ltr" maxlength="14"
                        x-on:blur="check($el)" x-on:input="check($el); decodeNationalId($el.value)" />

                    {{-- ما قُرئ من الرقم القومي أثناء الكتابة --}}
                    <div class="mt-2 flex flex-wrap items-center gap-2 rounded-xl bg-amber-50/70 border border-amber-100 px-3 py-2 text-[12px]"
                        x-cloak x-show="decoded">
                        <x-icon class="w-4 h-4 text-amber-600" name="scan-line" />
                        <span class="text-gray-500">من الرقم القومي:</span>
                        <span class="font-bold text-gray-800" x-text="decoded && decoded.genderLabel"></span>
                        <span class="text-gray-300">•</span>
                        <span class="font-bold text-gray-800" dir="ltr" x-text="decoded && decoded.birthDate"></span>
                        <span class="text-gray-500" x-text="decoded && ('(' + decoded.age + ' سنة)')"></span>
                        <span class="text-gray-300">•</span>
                        <span class="font-bold text-gray-800" x-text="decoded && decoded.governorate"></span>
                    </div>
                </div>

                {{-- تفاصيل بطاقة قديمة غير معروضة في النموذج المبسّط؛ نحافظ عليها عند تعديل مستخدم قائم. --}}
                @if ($user)
                    <input name="national_id_issued_at" type="hidden" value="{{ $date('national_id_issued_at') }}">
                    <input name="national_id_expires_at" type="hidden" value="{{ $date('national_id_expires_at') }}">
                    <input name="national_id_issuer" type="hidden" value="{{ old('national_id_issuer', $user->national_id_issuer) }}">
                    <input name="card_address" type="hidden" value="{{ old('card_address', $user->card_address) }}">
                    <input name="card_occupation" type="hidden" value="{{ old('card_occupation', $user->card_occupation) }}">
                    <input name="religion" type="hidden" value="{{ old('religion', $user->religion) }}">
                @endif

            </div>
        </section>

        {{-- البيانات الشخصية --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="contact-round" />
                </span>
                <h2 class="text-sm font-bold text-gray-800">البيانات الشخصية</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.field label="تاريخ الميلاد" name="birth_date" type="date" :value="$date('birth_date')"
                    hint="يُستخرج من الرقم القومي إن تُرك فارغًا" />
                <x-ui.field label="النوع" name="gender" type="select" :options="$genders" :value="$user?->gender?->value"
                    placeholder="اختر النوع" x-on:change="gender = $event.target.value" />
                <x-ui.field label="محل الميلاد" name="birth_place" placeholder="المحافظة" :value="$user?->birth_place"
                    list="egyptGovernoratesList" />
                <x-ui.field label="الجنسية" name="nationality" placeholder="مصري" :value="$user?->nationality ?? 'مصري'" />
                <x-ui.field label="الحالة الاجتماعية" name="marital_status" type="select" :options="$maritalStatuses"
                    :value="$user?->marital_status?->value" placeholder="اختر الحالة" />
                <x-ui.field label="المؤهل الدراسي" name="qualification" placeholder="مثال: دبلوم صنايع" :value="$user?->qualification"
                    list="qualificationsList" />
                <div x-show="gender !== 'female'">
                    <x-ui.field col="" label="الموقف من التجنيد" name="military_status" type="select" :options="$militaryStatuses"
                        :value="$user?->military_status?->value" placeholder="اختر الموقف" />
                </div>
            </div>
        </section>

        {{-- السكن الحالي --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="map-pin" />
                </span>
                <div>
                    <h2 class="text-sm font-bold text-gray-800">السكن الحالي</h2>
                    <p class="text-[11px] text-gray-400">مكان الإقامة الفعلي إن اختلف عن عنوان البطاقة</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.field label="المحافظة" name="governorate" placeholder="مثال: الدقهلية" :value="$user?->governorate"
                    list="egyptGovernoratesList" />
                <x-ui.field label="المركز / المدينة" name="city" placeholder="مثال: المنصورة" :value="$user?->city" />
                <x-ui.field col="sm:col-span-2" label="العنوان التفصيلي" name="address" type="textarea" rows="2"
                    placeholder="الشارع، رقم العقار، علامة مميزة..." :value="$user?->address" />
            </div>
        </section>

        {{-- الطوارئ --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-red-50 text-red-500 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="siren" />
                </span>
                <h2 class="text-sm font-bold text-gray-800">جهة اتصال للطوارئ</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui.field label="الاسم" name="emergency_contact_name" placeholder="اسم الشخص" :value="$user?->emergency_contact_name" />
                <x-ui.field label="صلة القرابة" name="emergency_contact_relation" placeholder="مثال: الأخ"
                    :value="$user?->emergency_contact_relation" list="relationsList" />
                <x-ui.field label="رقم الهاتف" name="emergency_contact_phone" placeholder="01xxxxxxxxx"
                    :value="$user?->emergency_contact_phone" client-error="errors.emergency_contact_phone" inputmode="tel"
                    dir="ltr" x-on:blur="check($el)" x-on:input="check($el)" />
            </div>

            <datalist id="egyptGovernoratesList">
                @foreach ($egyptGovernorates as $governorate)
                    <option value="{{ $governorate }}"></option>
                @endforeach
            </datalist>
            <datalist id="qualificationsList">
                @foreach ($qualifications as $qualification)
                    <option value="{{ $qualification }}"></option>
                @endforeach
            </datalist>
            <datalist id="relationsList">
                @foreach ($relations as $relation)
                    <option value="{{ $relation }}"></option>
                @endforeach
            </datalist>
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
                <x-ui.field label="تاريخ التعيين" name="hire_date" type="date" :value="$date('hire_date')" />
            </div>
        </section>

        <details class="group bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="banknote" />
                </span>
                <div>
                    <h2 class="text-sm font-bold text-gray-800">إعدادات المرتب والعمولات</h2>
                    <p class="text-[11px] text-gray-400">اختيارية — افتحها فقط عند الحاجة</p>
                </div>
                </div>
                <x-icon class="w-5 h-5 text-gray-400 transition-transform group-open:rotate-180" name="chevron-down" />
            </summary>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.field label="المرتب الثابت" name="base_salary" type="number" min="0" step="0.01"
                    :value="$user?->base_salary ?? 0" />
                <x-ui.field label="عمولة القسط الواحد" name="installment_commission" type="number" min="0" step="0.01"
                    :value="$user?->installment_commission ?? 10" hint="الافتراضي للمحصل: 10 جنيهات" />
                <x-ui.field label="عمولة تركيب الجهاز" name="installation_commission" type="number" min="0" step="0.01"
                    :value="$user?->installation_commission ?? $user?->device_commission ?? 0" />
                <x-ui.field label="عمولة تسويق الجهاز" name="marketing_commission" type="number" min="0" step="0.01"
                    :value="$user?->marketing_commission ?? 0" />
                <x-ui.field label="عمولة الصيانة لكل شمعة" name="maintenance_commission" type="number" min="0" step="0.01"
                    :value="$user?->maintenance_commission ?? 0" hint="تُقترح تلقائيًا في الصيانة القطاعي: العمولة × عدد الشمعات" />
            </div>
        </details>

        {{-- ملاحظات --}}
        <details class="group bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="notebook-pen" />
                </span>
                <div>
                    <h2 class="text-sm font-bold text-gray-800">ملاحظات إضافية</h2>
                    <p class="text-[11px] text-gray-400">اختيارية</p>
                </div>
                </div>
                <x-icon class="w-5 h-5 text-gray-400 transition-transform group-open:rotate-180" name="chevron-down" />
            </summary>

            <div class="mt-4">
                <x-ui.field col="" label="ملاحظات إضافية" name="notes" type="textarea" rows="3"
                    placeholder="أي تفاصيل أخرى عن الموظف..." :value="$user?->notes" />
            </div>
        </details>

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
        function userForm(config) {
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

            const GOVERNORATE_CODES = {'01': 'القاهرة', '02': 'الإسكندرية', '03': 'بورسعيد', '04': 'السويس', '11': 'دمياط',
                '12': 'الدقهلية', '13': 'الشرقية', '14': 'القليوبية', '15': 'كفر الشيخ', '16': 'الغربية', '17': 'المنوفية',
                '18': 'البحيرة', '19': 'الإسماعيلية', '21': 'الجيزة', '22': 'بني سويف', '23': 'الفيوم', '24': 'المنيا',
                '25': 'أسيوط', '26': 'سوهاج', '27': 'قنا', '28': 'أسوان', '29': 'الأقصر', '31': 'البحر الأحمر',
                '32': 'الوادي الجديد', '33': 'مطروح', '34': 'شمال سيناء', '35': 'جنوب سيناء', '88': 'خارج الجمهورية'};

            function digits(value) {
                return String(value || '').replace(/[٠-٩]/g, d => ARABIC_DIGITS.indexOf(d)).replace(/\D/g, '');
            }

            // نفس منطق App\Support\NationalId::parse() على الخادم
            function parseNationalId(value) {
                const match = digits(value).match(/^([23])(\d{2})(\d{2})(\d{2})(\d{2})\d{3}(\d)\d$/);
                if (!match) return null;

                const [, century, yy, mm, dd, gov, genderDigit] = match;
                const year = (century === '2' ? 1900 : 2000) + Number(yy);
                const birth = new Date(Date.UTC(year, Number(mm) - 1, Number(dd)));
                const validDate = birth.getUTCFullYear() === year && birth.getUTCMonth() === Number(mm) - 1 && birth.getUTCDate() === Number(dd);
                if (!validDate || !GOVERNORATE_CODES[gov] || birth > new Date()) return null;

                const today = new Date();
                let age = today.getFullYear() - year;
                if (today.getMonth() + 1 < Number(mm) || (today.getMonth() + 1 === Number(mm) && today.getDate() < Number(dd))) age--;

                const gender = Number(genderDigit) % 2 === 1 ? 'male' : 'female';

                return {
                    birthDate: `${year}-${mm}-${dd}`,
                    gender: gender,
                    genderLabel: gender === 'male' ? 'ذكر' : 'أنثى',
                    governorate: GOVERNORATE_CODES[gov],
                    age: age
                };
            }

            return {
                errors: {},
                gender: config.gender || '',
                decoded: parseNationalId(config.nationalId),

                /** يملأ تاريخ الميلاد والنوع ومحل الميلاد من الرقم القومي — دون الكتابة فوق قيمة أدخلها المستخدم */
                decodeNationalId(value) {
                    this.decoded = parseNationalId(value);
                    if (!this.decoded) return;

                    const field = name => this.$root.querySelector(`[name="${name}"]`);
                    const fill = (name, value) => {
                        const el = field(name);
                        if (el && !el.value) {
                            el.value = value;
                            el.dispatchEvent(new Event('change', {bubbles: true}));
                            el.dispatchEvent(new Event('input', {bubbles: true}));
                        }
                    };

                    fill('birth_date', this.decoded.birthDate);
                    fill('birth_place', this.decoded.governorate);
                    fill('gender', this.decoded.gender);
                    this.gender = field('gender')?.value || this.gender;
                },

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
                    secondary_phone(value) {
                        if (!value.trim()) return '';
                        return PHONE_PATTERN.test(normalizePhone(value)) ? '' :
                            'رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.';
                    },
                    emergency_contact_phone(value) {
                        if (!value.trim()) return '';
                        return PHONE_PATTERN.test(normalizePhone(value)) ? '' :
                            'رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.';
                    },
                    national_id(value) {
                        if (!value.trim()) return '';
                        if (digits(value).length !== 14) return 'الرقم القومي يجب أن يتكون من 14 رقمًا.';
                        return parseNationalId(value) ? '' : 'الرقم القومي غير صالح — تاريخ الميلاد أو كود المحافظة بداخله غير صحيح.';
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
