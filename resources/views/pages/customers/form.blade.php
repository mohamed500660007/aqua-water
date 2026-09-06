@php
    $customer = $customer ?? null;

    $storedPhones = $customer?->phones->pluck('phone')->all() ?? [];
    $phoneRows = array_values(old('phones', $storedPhones ?: ['']));
    $primaryIndex = (int) old('primary_phone', max(0, array_search(true, $customer?->phones->pluck('is_primary')->all() ?? [], true)));

    $registeredAt = old('registered_at', $customer?->registered_at?->format('Y-m-d') ?? today()->toDateString());
@endphp

<form action="{{ $action }}" method="POST"
    x-data="customerForm({ phones: @js($phoneRows), primary: {{ $primaryIndex }} })"
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
                <x-ui.field col="sm:col-span-2" label="الاسم بالكامل" name="name" placeholder="مثال: أحمد سيد عبد الرحمن"
                    :value="$customer?->name" client-error="errors.name" required x-on:blur="check($el)" x-on:input="check($el)" />

                <x-ui.field label="كود العميل" name="code" placeholder="يُولَّد تلقائيًا إذا تُرك فارغًا"
                    :value="$customer?->code" client-error="errors.code" hint="يجب أن يكون فريدًا داخل النظام"
                    x-on:blur="check($el)" x-on:input="check($el)" />

                <x-ui.field label="الرقم القومي" name="national_id" placeholder="14 رقمًا" :value="$customer?->national_id"
                    client-error="errors.national_id" inputmode="numeric" x-on:blur="check($el)" x-on:input="check($el)" />

                <x-ui.field label="الحالة" name="status" type="select" :options="$statuses"
                    :value="$customer?->status?->value ?? $defaultStatus" placeholder="اختر الحالة" required />

                <x-ui.date-picker label="تاريخ التسجيل" name="registered_at" :value="$registeredAt" required />
            </div>
        </section>

        {{-- الموقع --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="map-pin" />
                </span>
                <h2 class="text-sm font-bold text-gray-800">العنوان</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui.field label="المحافظة" name="governorate" placeholder="مثال: الدقهلية" :value="$customer?->governorate"
                    list="governorateOptions" />
                <x-ui.field label="المركز" name="center" placeholder="مثال: المنصورة" :value="$customer?->center" list="centersList" />
                <x-ui.field label="القرية" name="village" placeholder="مثال: ميت مزاح" :value="$customer?->village" list="villagesList" />
            </div>

            <x-ui.field col="col-span-1 mt-4" label="العنوان التفصيلي" name="detailed_address" type="textarea"
                rows="3" placeholder="اكتب العنوان بالتفصيل كما تراه مناسبًا (اسم الشارع، رقم العقار، علامة مميزة...)"
                :value="$customer?->detailed_address" />

            <datalist id="governorateOptions">
                @foreach ($governorates as $governorate)
                    <option value="{{ $governorate }}"></option>
                @endforeach
            </datalist>
            <datalist id="centersList">
                @foreach ($centers as $center)
                    <option value="{{ $center }}"></option>
                @endforeach
            </datalist>
            <datalist id="villagesList">
                @foreach ($villages as $village)
                    <option value="{{ $village }}"></option>
                @endforeach
            </datalist>
        </section>

        {{-- أرقام الهاتف --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <x-icon class="w-[18px] h-[18px]" name="phone" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">أرقام الهاتف</h2>
                        <p class="text-[11px] text-gray-400">حدّد الرقم الأساسي المستخدم في التواصل</p>
                    </div>
                </div>
                <x-ui.btn icon="plus" size="sm" variant="soft" x-on:click="addPhone()">إضافة رقم</x-ui.btn>
            </div>

            <div class="space-y-2.5">
                <template x-bind:key="row.uid" x-for="(row, index) in phones">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <label
                                class="shrink-0 flex items-center gap-2 h-11 px-3 rounded-xl border text-[11px] font-bold cursor-pointer transition-colors"
                                x-bind:class="primary === index ? 'border-brand-green bg-brand-green/20 text-brand-dark' :
                                    'border-gray-200 text-gray-500 hover:bg-gray-50'">
                                <input class="w-3.5 h-3.5 accent-[#D4FF5B]" name="primary_phone" type="radio"
                                    x-bind:value="index" x-model.number="primary" />
                                أساسي
                            </label>

                            <input
                                class="flex-1 min-w-0 h-11 px-3.5 rounded-xl bg-white border text-[13px] text-gray-800 focus:ring-2 outline-none transition placeholder:text-gray-300"
                                dir="ltr" inputmode="tel" name="phones[]" placeholder="01012345678"
                                x-bind:class="row.error ? 'border-red-300 focus:ring-red-200 focus:border-red-400' :
                                    'border-gray-200 focus:ring-brand-green/50 focus:border-brand-green'"
                                x-model="row.number" x-on:blur="checkPhones()" x-on:input="checkPhones()" />

                            <button
                                class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                                type="button" x-bind:disabled="phones.length === 1" x-on:click="removePhone(index)">
                                <svg class="w-[17px] h-[17px]" fill="none" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M19 6l-1 14H6L5 6" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px] font-semibold text-red-500 mt-1.5 pr-[92px]" x-cloak x-show="row.error"
                            x-text="row.error"></p>
                    </div>
                </template>
            </div>

            <p class="text-[11px] text-gray-400 mt-3">تُحفظ الأرقام بالصيغة المحلية الموحّدة (مثال: 01012345678) حتى لا
                يتكرر الرقم نفسه بصيغ مختلفة.</p>
        </section>

        {{-- ملاحظات --}}
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <x-icon class="w-[18px] h-[18px]" name="notebook-pen" />
                </span>
                <h2 class="text-sm font-bold text-gray-800">ملاحظات</h2>
            </div>

            <x-ui.field col="col-span-1" label="ملاحظات إضافية" name="notes" type="textarea" rows="4"
                placeholder="أي تفاصيل إضافية عن العميل..." :value="$customer?->notes" />
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
            <x-ui.btn class="sm:w-40" variant="outline" :href="$customer ? route('customers.show', $customer) : route('customers.index')">إلغاء</x-ui.btn>
            <x-ui.btn class="sm:w-48" icon="check" type="submit">{{ $submitLabel }}</x-ui.btn>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        // نموذج العميل: تحقق فوري في الواجهة يعكس نفس قواعد الـ Form Request على الخادم
        function customerForm(config) {
            const PHONE_PATTERN = /^0\d{7,10}$/;
            const ARABIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';

            function digits(value) {
                return String(value || '')
                    .replace(/[٠-٩]/g, d => ARABIC_DIGITS.indexOf(d))
                    .replace(/\D/g, '');
            }

            function normalizePhone(value) {
                let out = digits(value);
                if (!out) return '';
                for (const prefix of ['0020', '20']) {
                    if (out.startsWith(prefix) && out.length > prefix.length + 7) {
                        out = out.slice(prefix.length);
                        break;
                    }
                }
                return out.startsWith('0') ? out : '0' + out;
            }

            let uid = 0;

            return {
                errors: {},
                primary: config.primary || 0,
                phones: (config.phones.length ? config.phones : ['']).map(number => ({uid: uid++, number: number, error: ''})),

                rules: {
                    name(value) {
                        if (!value.trim()) return 'اسم العميل مطلوب.';
                        if (value.trim().length > 150) return 'اسم العميل طويل جدًا.';
                        return '';
                    },
                    code(value) {
                        return value.trim().length > 32 ? 'كود العميل يجب ألا يزيد عن 32 حرفًا.' : '';
                    },
                    national_id(value) {
                        if (!value.trim()) return '';
                        return digits(value).length === 14 ? '' : 'الرقم القومي يجب أن يتكون من 14 رقمًا.';
                    }
                },

                check(el) {
                    const rule = this.rules[el.name];
                    if (rule) this.errors[el.name] = rule(el.value);
                },

                checkPhones() {
                    const seen = {};
                    let valid = true;

                    this.phones.forEach(row => {
                        const number = normalizePhone(row.number);

                        if (!number) {
                            row.error = 'رقم الهاتف مطلوب.';
                        } else if (!PHONE_PATTERN.test(number)) {
                            row.error = 'رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.';
                        } else if (seen[number]) {
                            row.error = 'لا يمكن تكرار نفس رقم الهاتف للعميل.';
                        } else {
                            row.error = '';
                            seen[number] = true;
                        }

                        if (row.error) valid = false;
                    });

                    return valid;
                },

                addPhone() {
                    this.phones.push({uid: uid++, number: '', error: ''});
                },

                removePhone(index) {
                    if (this.phones.length === 1) return;
                    this.phones.splice(index, 1);
                    if (this.primary >= this.phones.length) this.primary = this.phones.length - 1;
                },

                validateAll() {
                    this.$el.querySelectorAll('input[name], textarea[name]').forEach(el => this.check(el));

                    const phonesValid = this.checkPhones();
                    const fieldsValid = Object.values(this.errors).every(message => !message);

                    return phonesValid && fieldsValid;
                }
            };
        }
    </script>
@endpush
