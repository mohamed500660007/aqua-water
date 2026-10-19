@extends('layouts.app')

@section('title', 'المرتبات والعمولات')
@section('page-title', 'المرتبات والعمولات')
@section('page-subtitle', 'كشف شهري لكل موظف ومتابعة صرف المرتبات')

@section('content')
    <x-ui.page-header icon="banknote" title="كشف المرتبات والعمولات"
        subtitle="الصيانة تُحسب بعدد العملاء مرة واحدة، وليس بعدد الشمعات">
        <x-slot:actions>
            {{-- تُفتح في تبويب مستقل لأنها صفحة طباعة بلا هيكل التطبيق، وتحمل نفس فلاتر الصفحة --}}
            <x-ui.btn icon="printer" size="sm" variant="dark" document :href="route('payroll.report', array_filter(['from' => $month->toDateString(), 'to' => $month->endOfMonth()->toDateString(), 'q' => request('q'), 'role' => request('role')]))">تقرير المرتبات</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form method="GET" action="{{ route('payroll.index') }}"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[minmax(240px,1fr)_220px_180px_auto] gap-3 p-4 border-b border-gray-100">
            <x-ui.field label="ابحث عن موظف" name="q" :value="request('q')" placeholder="الاسم أو البريد أو رقم الهاتف" />
            <x-ui.field label="الوظيفة" name="role" type="select" :options="$roles" :value="request('role')" placeholder="كل الوظائف" />
            <x-ui.field label="الشهر" name="month" type="month" :value="$month->format('Y-m')" />
            <div class="flex items-end gap-2">
                <x-ui.btn class="flex-1" type="submit" icon="search">بحث</x-ui.btn>
                @if (request()->filled('q') || request()->filled('role'))
                    <x-ui.btn variant="soft" icon="rotate-ccw" :href="route('payroll.index', ['month' => $month->format('Y-m')])">مسح</x-ui.btn>
                @endif
            </div>
        </form>

        <div class="hidden lg:grid grid-cols-[minmax(180px,1.4fr)_110px_repeat(4,90px)_130px_120px_40px] gap-3 px-5 py-3 bg-gray-50 text-[11px] font-bold text-gray-500">
            <span>الموظف</span><span>الكشف</span><span class="text-center">الأقساط</span>
            <span class="text-center">التركيب</span><span class="text-center">التسويق</span><span class="text-center">الصيانة</span>
            <span class="text-left">صافي المستحق</span><span>الصرف</span><span></span>
        </div>

        <div class="divide-y divide-gray-100" x-data="{ openEmployee: null }">
            @forelse ($rows as $row)
                @php
                    $values = $row['values'];
                    $payroll = $row['payroll'];
                    $paymentStatus = $payroll?->paymentStatus();
                @endphp

                <div x-data="payrollRow({{ Js::from($values) }})">
                    <button type="button"
                        class="w-full grid grid-cols-2 lg:grid-cols-[minmax(180px,1.4fr)_110px_repeat(4,90px)_130px_120px_40px] gap-3 items-center px-5 py-4 text-right hover:bg-gray-50 transition-colors"
                        x-on:click="openEmployee = openEmployee === {{ $row['user']->id }} ? null : {{ $row['user']->id }}">
                        <span class="col-span-2 lg:col-span-1 flex items-center gap-3 min-w-0">
                            <x-ui.avatar :name="$row['user']->name" />
                            <span class="min-w-0">
                                <span class="block text-[13px] font-bold text-gray-800 truncate">{{ $row['user']->name }}</span>
                                <span class="block text-[11px] text-gray-400 truncate">{{ $row['user']->role ?? 'بدون وظيفة محددة' }}
                                    @if ($row['user']->department) — {{ $row['user']->department }} @endif</span>
                            </span>
                        </span>
                        <span>
                            @if ($payroll)
                                <x-ui.badge status="معتمد" tone="green" dot />
                            @else
                                <x-ui.badge status="غير معتمد" tone="amber" dot />
                            @endif
                        </span>
                        <span class="text-center text-[12px] text-gray-600"><span class="lg:hidden text-gray-400">الأقساط: </span>{{ $values['installment_count'] }}</span>
                        <span class="text-center text-[12px] text-gray-600"><span class="lg:hidden text-gray-400">التركيب: </span>{{ $values['installed_device_count'] }}</span>
                        <span class="text-center text-[12px] text-gray-600"><span class="lg:hidden text-gray-400">التسويق: </span>{{ $values['marketed_device_count'] }}</span>
                        <span class="text-center text-[12px] text-gray-600"><span class="lg:hidden text-gray-400">الصيانة: </span>{{ $values['maintenance_count'] }}</span>
                        <span class="text-left text-[14px] font-black text-green-700">{{ number_format($row['total'], 2) }} ج.م</span>
                        <span>
                            @if ($paymentStatus)
                                <x-ui.badge :tone="$paymentStatus['tone']" dot>{{ $paymentStatus['label'] }}</x-ui.badge>
                            @else
                                <span class="text-[11px] text-gray-300">—</span>
                            @endif
                        </span>
                        <span class="hidden lg:flex justify-end text-gray-400">
                            <x-icon class="w-4 h-4 transition-transform" name="chevron-down"
                                x-bind:class="openEmployee === {{ $row['user']->id }} && 'rotate-180'" />
                        </span>
                    </button>

                    <div x-cloak x-show="openEmployee === {{ $row['user']->id }}" class="px-5 pb-5 bg-gray-50/70 border-t border-gray-100">
                        <form method="POST" action="{{ route('payroll.store', $row['user']) }}">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                            <div class="flex items-center justify-between gap-3 py-4">
                                <div><p class="text-[13px] font-bold text-gray-700">تعديل كشف {{ $row['user']->name }}</p>
                                    <p class="text-[11px] text-gray-400">
                                        تحصيل الشهر {{ number_format($values['installment_total'], 2) }} ج.م —
                                        مبيعات الشهر {{ number_format($values['device_sales_total'], 2) }} ج.م</p></div>
                                <p class="text-[13px] font-bold text-green-700"><span x-text="money(total)"></span> ج.م</p>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <x-ui.field label="المرتب الثابت" name="base_salary" type="number" min="0" step="0.01" x-model.number="base_salary" />
                                <x-ui.field label="عدد الأقساط" name="installment_count" type="number" min="0" step="1" x-model.number="installment_count" />
                                <x-ui.field label="عمولة القسط" name="installment_commission" type="number" min="0" step="0.01" x-model.number="installment_commission" />
                                <x-ui.field label="أجهزة تم تركيبها" name="installed_device_count" type="number" min="0" step="1" x-model.number="installed_device_count" />
                                <x-ui.field label="عمولة التركيب" name="installation_commission" type="number" min="0" step="0.01" x-model.number="installation_commission" />
                                <x-ui.field label="أجهزة تم تسويقها" name="marketed_device_count" type="number" min="0" step="1" x-model.number="marketed_device_count" />
                                <x-ui.field label="عمولة التسويق" name="marketing_commission" type="number" min="0" step="0.01" x-model.number="marketing_commission" />
                                <x-ui.field label="عمليات الصيانة" name="maintenance_count" type="number" min="0" step="1" x-model.number="maintenance_count" />
                                <x-ui.field label="إجمالي عمولة الصيانة" name="maintenance_commission" type="number" min="0" step="0.01" x-model.number="maintenance_commission" />
                                <x-ui.field label="أيام الغياب" name="absence_days" type="number" min="0" max="31" step="1" x-model.number="absence_days" />
                                <x-ui.field label="خصم بصمة / غياب" name="attendance_deduction" type="number" min="0" step="0.01" x-model.number="attendance_deduction" />
                                <x-ui.field label="خصم عادي" name="regular_deduction" type="number" min="0" step="0.01" x-model.number="regular_deduction" />
                                <x-ui.field label="سبب الخصم العادي" name="deduction_reason" :value="$values['deduction_reason']" placeholder="مطلوب عند وجود خصم" />
                                <x-ui.field label="مكافأة" name="bonus" type="number" min="0" step="0.01" x-model.number="bonus" />
                                <x-ui.field col="col-span-2 md:col-span-2" label="ملاحظات" name="note" :value="$values['note']" />
                            </div>
                            <div class="mt-4 flex flex-wrap justify-end gap-2">
                                @if ($payroll)
                                    <x-ui.btn icon="printer" variant="soft" document :href="route('payroll.payslip', $payroll)">طباعة القسيمة</x-ui.btn>
                                @endif
                                <x-ui.btn type="submit" icon="save">{{ $payroll ? 'حفظ التعديل' : 'اعتماد الكشف' }}</x-ui.btn>
                            </div>
                        </form>

                        {{-- صرف المرتب: متاح بعد اعتماد الكشف، ويقبل دفعات جزئية --}}
                        @if ($payroll)
                            <div class="mt-5 pt-4 border-t border-gray-200">
                                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                    <p class="text-[13px] font-bold text-gray-700">صرف المرتب</p>
                                    <p class="text-[12px] text-gray-500">
                                        صُرف <span class="font-bold text-green-700">{{ number_format($payroll->paidAmount(), 2) }}</span>
                                        — متبقي <span class="font-bold text-amber-600">{{ number_format($payroll->remainingAmount(), 2) }}</span> ج.م
                                    </p>
                                </div>

                                @if ($payroll->payments->isNotEmpty())
                                    <ul class="mb-3 divide-y divide-gray-100 rounded-xl border border-gray-100 bg-white">
                                        @foreach ($payroll->payments as $payment)
                                            <li class="flex items-center gap-3 px-3 py-2">
                                                <span class="text-[12px] text-gray-500" dir="ltr">{{ $payment->paid_at->format('Y-m-d') }}</span>
                                                <span class="flex-1 text-[12px] text-gray-400 truncate">{{ $payment->note }}</span>
                                                <span class="text-[13px] font-bold text-gray-800">{{ number_format((float) $payment->amount, 2) }} ج.م</span>
                                                <form action="{{ route('payroll.payments.destroy', $payment) }}" method="POST" x-data>
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-ui.icon-btn icon="trash-2" label="حذف الدفعة" tone="danger"
                                                        x-on:click="$store.confirm.ask({
                                                            title: 'حذف دفعة الصرف',
                                                            message: 'سيعود المبلغ إلى المتبقي للموظف.',
                                                            label: 'حذف',
                                                            onOk: () => $root.requestSubmit()
                                                        })" />
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($payroll->remainingAmount() > 0)
                                    <form method="POST" action="{{ route('payroll.payments.store', $payroll) }}"
                                        class="grid grid-cols-2 md:grid-cols-[160px_160px_1fr_auto] gap-3 items-end">
                                        @csrf
                                        <x-ui.field label="المبلغ" name="amount" type="number" min="0.01" step="0.01"
                                            :value="$payroll->remainingAmount()" />
                                        <x-ui.field label="تاريخ الصرف" name="paid_at" type="date" :value="today()->toDateString()" />
                                        <x-ui.field label="ملاحظة" name="note" placeholder="اختياري" />
                                        <x-ui.btn type="submit" icon="hand-coins">تسجيل الصرف</x-ui.btn>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <x-ui.empty-state icon="search-x" title="لا يوجد موظفون مطابقون" message="جرّب تغيير البحث أو الوظيفة المختارة." />
            @endforelse
        </div>
        <x-ui.paginator :paginator="$employees" />
    </div>
@endsection

@push('scripts')
    <script>
        // نفس معادلة PayrollService::total() — للمعاينة الفورية فقط، والحفظ يعيد الحساب على الخادم
        function payrollRow(values) {
            return { ...values,
                get total() { return Number(this.base_salary || 0) + Number(this.installment_count || 0) * Number(this.installment_commission || 0) + Number(this.installed_device_count || 0) * Number(this.installation_commission || 0) + Number(this.marketed_device_count || 0) * Number(this.marketing_commission || 0) + Number(this.maintenance_commission || 0) + Number(this.bonus || 0) - Number(this.regular_deduction || 0) - Number(this.attendance_deduction || 0); },
                money(value) { return new Intl.NumberFormat('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value); }
            };
        }
    </script>
@endpush
