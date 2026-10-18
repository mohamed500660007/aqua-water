{{-- إيصال تحصيل واحد (1024×460px تصميمًا، يُصغَّر عند الطباعة ليصفّ ثلاثة في ورقة A4):
     الإيصال الذي يُسلَّم للعميل + كعب جانبي يُقص ويبقى مع الإدارة. للأقساط غير المحصَّلة
     (دفتر الإيصالات) تُترك خانات التحصيل فارغة لتُكتب باليد. --}}
@php
    $contract = $installment->contract;
    $customer = $contract->customer;

    $receiptNo = sprintf('REC-%s-%04d', $installment->due_date->format('Y'), $installment->id);
    $amount = number_format((float) ($installment->paid_amount ?? $installment->amount), 2);
    $phone = $customer?->phones->firstWhere('is_primary', true)?->phone ?? $customer?->phones->first()?->phone;
    $product = $contract->items->pluck('product_name')->filter()->unique()->join('، ');
    $dueDate = $installment->due_date->format('Y-m-d');
    $collectedAt = $installment->paid_at;
    $collector = $installment->collectedBy?->name;
    $blank = '';
@endphp

<div class="sheet">
    {{-- ===== الإيصال (نسخة العميل) ===== --}}
    <div class="rc-main">
        <header class="rc-header">
            <div>
                <p class="rc-company">أكوا ووتر</p>
                <p class="rc-tagline">لأنظمة تنقية المياه</p>
            </div>

            <h1 class="rc-title">إيصال تحصيل قسط</h1>

            <table class="rc-meta">
                <tr>
                    <th>رقم الإيصال</th>
                    <td class="num" dir="ltr">{{ $receiptNo }}</td>
                </tr>
                <tr>
                    <th>تاريخ التحصيل</th>
                    <td class="num" dir="ltr">{{ $collectedAt?->format('Y-m-d') ?? '..... / ..... / ..........' }}</td>
                </tr>
            </table>
        </header>

        <div class="rc-rule"></div>

        <table class="rc-fields">
            <tr>
                <th>اسم العميل</th>
                <td colspan="3">{{ $customer?->name ?? '—' }}</td>
                <th>كود العميل</th>
                <td class="num" dir="ltr">{{ $customer?->code ?? '—' }}</td>
            </tr>
            <tr>
                <th>المحافظة</th>
                <td>{{ $customer?->governorate ?: '—' }}</td>
                <th>المركز</th>
                <td>{{ $customer?->center ?: '—' }}</td>
                <th>القرية</th>
                <td>{{ $customer?->village ?: '—' }}</td>
            </tr>
            <tr>
                <th>العنوان التفصيلي</th>
                <td colspan="5" title="{{ $customer?->detailed_address }}">{{ $customer?->detailed_address ?: '—' }}</td>
            </tr>
            <tr>
                <th>التليفون</th>
                <td class="num" dir="ltr" style="text-align: right">{{ $phone ?: '—' }}</td>
                <th>رقم العقد</th>
                <td class="num" dir="ltr" style="text-align: right">{{ $contract->code }}</td>
                <th>المنتج</th>
                <td title="{{ $product }}">{{ $product ?: '—' }}</td>
            </tr>
            <tr>
                <th>رقم القسط</th>
                <td class="num">{{ $installment->number }}{{ $contract->installments_count ? ' من ' . $contract->installments_count : '' }}</td>
                <th>تاريخ الاستحقاق</th>
                <td class="num" dir="ltr" style="text-align: right">{{ $dueDate }}</td>
                <th>اسم المحصّل</th>
                <td>{{ $collector ?? $blank }}</td>
            </tr>
            {{-- ملاحظة القسط المكتوبة عند التحصيل؛ إن لم توجد تبقى الخانة فارغة لتُكتب باليد --}}
            <tr>
                <th>ملاحظات</th>
                <td class="rc-note" colspan="5">{{ $installment->note }}</td>
            </tr>
        </table>

        <div class="rc-bottom">
            <div class="rc-amount">
                <span class="rc-amount-label">المبلغ المحصَّل</span>
                <span class="rc-amount-value num">{{ $amount }} <small>جنيه مصري</small></span>
            </div>

            <div class="rc-signatures">
                <div class="rc-signature"><span>توقيع المحصّل</span><span></span></div>
                <div class="rc-signature"><span>توقيع العميل</span><span></span></div>
                <div class="rc-signature"><span>ختم الشركة</span><span></span></div>
            </div>
        </div>

        <p class="rc-footnote">
            <span>هذا الإيصال لا يُعتد به إلا بعد التوقيع والختم — يُرجى الاحتفاظ به لحين انتهاء الأقساط.</span>
            <span>نسخة العميل</span>
        </p>
    </div>

    {{-- ===== خط القص ===== --}}
    <div class="rc-cut"><span>✂ يُقص هنا</span></div>

    {{-- ===== الكعب (نسخة الإدارة) ===== --}}
    <div class="rc-stub">
        <p class="rc-stub-title">كعب الإيصال — نسخة الإدارة</p>
        <p class="rc-stub-no num" dir="ltr">{{ $receiptNo }}</p>

        <dl>
            <div><dt>العميل</dt><dd title="{{ $customer?->name }}">{{ $customer?->name ?? '—' }}</dd></div>
            <div><dt>كود العميل</dt><dd class="num" dir="ltr">{{ $customer?->code ?? '—' }}</dd></div>
            <div><dt>رقم العقد</dt><dd class="num" dir="ltr">{{ $contract->code }}</dd></div>
            <div><dt>القسط / الاستحقاق</dt><dd class="num">{{ $installment->number }} — <span dir="ltr">{{ $dueDate }}</span></dd></div>
            <div><dt>القرية / المركز</dt><dd>{{ collect([$customer?->village, $customer?->center])->filter()->join(' — ') ?: '—' }}</dd></div>
            <div><dt>التليفون</dt><dd class="num" dir="ltr">{{ $phone ?: '—' }}</dd></div>
        </dl>

        <div class="rc-stub-amount">
            <span>المبلغ</span>
            <b class="num">{{ $amount }}</b> <span style="display: inline">ج.م</span>
        </div>

        <div class="mt-auto pt-2 space-y-2 text-[11px] font-bold">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5"><span @class(['checkbox', 'is-checked' => $collectedAt])></span> تم التحصيل</span>
                <span class="flex items-center gap-1.5"><span class="checkbox"></span> لم يتم</span>
            </div>
            <div class="flex items-end gap-2">
                <span class="whitespace-nowrap text-[10px]" style="color: var(--ink-soft)">التاريخ:</span>
                <span class="flex-1 num text-center" dir="ltr" style="border-bottom: 1.5px dotted var(--ink-soft)">{{ $collectedAt?->format('Y-m-d') }}&nbsp;</span>
            </div>
            <div class="flex items-end gap-2">
                <span class="whitespace-nowrap text-[10px]" style="color: var(--ink-soft)">توقيع المحصّل:</span>
                <span class="flex-1" style="border-bottom: 1.5px dotted var(--ink-soft)">&nbsp;</span>
            </div>
        </div>
    </div>
</div>
