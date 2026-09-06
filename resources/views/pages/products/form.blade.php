@php($product = $product ?? null)

<form action="{{ $action }}" method="POST" x-data="productForm()"
    x-on:submit="if (! validateAll()) $event.preventDefault()">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5">
            <p class="text-[13px] font-bold text-red-600 mb-1.5">تعذّر حفظ المنتج — راجع الحقول التالية:</p>
            <ul class="list-disc pr-5 space-y-1">
                @foreach ($errors->all() as $message)
                    <li class="text-[12px] text-red-500">{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center gap-2.5 mb-4">
            <span class="w-9 h-9 rounded-xl bg-brand-green/25 text-green-700 flex items-center justify-center">
                <x-icon class="w-[18px] h-[18px]" name="package" />
            </span>
            <h2 class="text-sm font-bold text-gray-800">بيانات المنتج</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.field col="sm:col-span-2" label="اسم المنتج" name="name" placeholder="مثال: فلتر أكوا برو 7 مراحل"
                :value="$product?->name" client-error="errors.name" required x-on:blur="check($el)" x-on:input="check($el)" />

            <x-ui.field label="كود المنتج (SKU)" name="sku" placeholder="AQ-PRO-7" :value="$product?->sku"
                client-error="errors.sku" dir="ltr" required x-on:blur="check($el)" x-on:input="check($el)" />

            <x-ui.field label="التصنيف" name="category" placeholder="مثال: فلاتر منزلية" :value="$product?->category"
                list="categoryOptions" />

            <x-ui.field label="سعر البيع (ج.م)" name="price" type="number" min="0" step="0.01" placeholder="0"
                :value="$product?->price" client-error="errors.price" required x-on:blur="check($el)" x-on:input="check($el)" />

            <x-ui.field label="سعر التكلفة (ج.م)" name="cost" type="number" min="0" step="0.01" placeholder="0"
                :value="$product?->cost" client-error="errors.cost" x-on:blur="check($el)" x-on:input="check($el)" />

            @if ($product)
                <x-ui.field label="الضمان" name="warranty" placeholder="مثال: سنتان" :value="$product->warranty" />

                <x-ui.field label="الحالة" name="status" type="select" :options="$statuses"
                    :value="$product->status->value" placeholder="اختر الحالة" required />
            @endif
        </div>

        <datalist id="categoryOptions">
            @foreach ($categories as $category)
                <option value="{{ $category }}"></option>
            @endforeach
        </datalist>
    </section>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 mt-4 lg:mt-5">
        <x-ui.btn class="sm:w-40" variant="outline" :href="$product ? route('products.show', $product) : route('products.index')">إلغاء</x-ui.btn>
        <x-ui.btn class="sm:w-48" icon="check" type="submit">{{ $submitLabel }}</x-ui.btn>
    </div>
</form>

@push('scripts')
    <script>
        // نموذج المنتج: تحقق فوري يعكس قواعد الـ Form Request
        function productForm() {
            return {
                errors: {},

                rules: {
                    name(value) {
                        return value.trim() ? '' : 'اسم المنتج مطلوب.';
                    },
                    sku(value) {
                        if (!value.trim()) return 'كود المنتج مطلوب.';
                        return value.trim().length > 32 ? 'كود المنتج طويل جدًا.' : '';
                    },
                    price(value) {
                        if (value === '') return 'سعر البيع مطلوب.';
                        return Number(value) >= 0 ? '' : 'سعر البيع لا يمكن أن يكون سالبًا.';
                    },
                    cost(value) {
                        if (value === '') return '';
                        return Number(value) >= 0 ? '' : 'سعر التكلفة لا يمكن أن يكون سالبًا.';
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
