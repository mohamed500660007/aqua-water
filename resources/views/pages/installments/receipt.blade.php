<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8" />
    <title>إيصال تحصيل — {{ $installment->contract->code }}</title>

    @php
        // نسخة مضغوطة تُستخدم تلقائيًا لو طول بيانات العميل (اسم/عنوان/ملاحظة) دفع
        // المحتوى لصفحة ثانية — القيمة الأولى للحجم العادي والثانية للمضغوط.
        $compact = $compact ?? false;
        $s = fn($normal, $tight) => $compact ? $tight : $normal;
    @endphp

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'almarai', sans-serif;
            color: #111;
            font-size: {{ $s(9.5, 8) }}px;
            line-height: {{ $s(1.4, 1.25) }};
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .sheet {
            padding: {{ $s(13, 9) }}px {{ $s(15, 11) }}px {{ $s(5, 4) }}px;
        }

        /* ===== شريط الترويسة المتدرّج ===== */
        .head {
            background: linear-gradient(120deg, #0c0f0d 0%, #14432f 55%, #1f7a4d 100%);
            border-radius: {{ $s(12, 9) }}px;
            padding: {{ $s(13, 8) }}px {{ $s(15, 11) }}px;
            margin-bottom: {{ $s(13, 7) }}px;
        }

        .seal {
            width: {{ $s(32, 24) }}px;
            height: {{ $s(32, 24) }}px;
            background: #d8f7c4;
            border-radius: 50%;
            text-align: center;
            line-height: {{ $s(32, 24) }}px;
            font-weight: 700;
            font-size: {{ $s(15, 11) }}px;
            color: #14432f;
        }

        .brand-name {
            font-weight: 700;
            font-size: {{ $s(17, 13) }}px;
            color: #ffffff;
            line-height: 1.2;
            letter-spacing: 0.3px;
        }

        .doc-title {
            font-size: {{ $s(8.5, 7) }}px;
            font-weight: 700;
            color: #d8f7c4;
            letter-spacing: 0.4px;
        }

        .doc-meta {
            font-size: {{ $s(7.5, 6.5) }}px;
            color: #b9d9c6;
            margin-top: 3px;
        }

        /* ===== كود العقد ===== */
        .code-strip {
            background: #eef7f1;
            border: 1px solid #cfe8d9;
            border-radius: {{ $s(8, 6) }}px;
            padding: {{ $s(8, 4) }}px {{ $s(12, 9) }}px;
            margin-bottom: {{ $s(12, 6) }}px;
            font-size: {{ $s(9, 7.5) }}px;
        }

        .code-strip b {
            font-size: {{ $s(10.5, 9) }}px;
            color: #14432f;
        }

        /* ===== حقول بخط منقّط ===== */
        .field-row {
            margin-bottom: {{ $s(12, 6) }}px;
        }

        .field-row td {
            padding: {{ $s(5, 2) }}px 0;
            vertical-align: bottom;
        }

        .field-row .label {
            width: 26%;
            font-weight: 700;
            color: #14432f;
            font-size: {{ $s(8, 6.5) }}px;
            white-space: nowrap;
        }

        .field-row .value {
            border-bottom: 1px dotted #a9a9a9;
            font-weight: 700;
            padding-bottom: {{ $s(3, 1.5) }}px;
            padding-right: 6px;
        }

        /* ===== صندوق المبلغ ===== */
        .amount-box {
            border: 1.4px solid #14432f;
            border-radius: {{ $s(10, 8) }}px;
            padding: {{ $s(11, 6) }}px {{ $s(14, 10) }}px;
            margin-bottom: {{ $s(12, 6) }}px;
        }

        .amount-label {
            font-size: {{ $s(7.5, 6.5) }}px;
            color: #6b6b6b;
            letter-spacing: 0.4px;
        }

        .amount-value {
            font-weight: 700;
            font-size: {{ $s(25, 19) }}px;
            color: #111;
            line-height: 1.3;
            margin-top: 2px;
        }

        .amount-value span {
            font-size: {{ $s(9.5, 8) }}px;
            font-weight: 700;
            color: #555;
        }

        .stamp {
            display: inline-block;
            border: 2px double #14432f;
            border-radius: 999px;
            color: #14432f;
            font-size: {{ $s(7.5, 6) }}px;
            font-weight: 700;
            line-height: {{ $s(1.6, 1.3) }};
            padding: {{ $s(7, 4) }}px {{ $s(10, 7) }}px;
            white-space: nowrap;
        }

        /* ===== الشرط ===== */
        .penalty {
            font-size: {{ $s(7, 6) }}px;
            color: #6b6b6b;
            line-height: {{ $s(1.7, 1.4) }};
            border-right: 2px solid #14432f;
            padding-right: 8px;
            margin-bottom: {{ $s(12, 6) }}px;
        }

        .penalty b {
            color: #111;
        }

        /* ===== شريط توقيعات ثلاثي ===== */
        .sign-strip {
            margin-bottom: {{ $s(10, 5) }}px;
        }

        .sign-strip td {
            background: #eef7f1;
            border: 1px solid #cfe8d9;
            font-size: {{ $s(7.5, 6.5) }}px;
            font-weight: 700;
            color: #14432f;
            text-align: center;
            padding: {{ $s(10, 5) }}px 4px {{ $s(5, 3) }}px;
            width: 33.33%;
        }

        .sign-strip .cell {
            border-top: 1px dotted #14432f;
            margin: {{ $s(5, 3) }}px 8px 0;
            padding-top: {{ $s(4, 2) }}px;
        }

        /* ===== شريط الدعم ===== */
        .support-strip {
            background: #14432f;
            color: #d8f7c4;
            border-radius: 999px;
            text-align: center;
            font-size: {{ $s(7.5, 6.5) }}px;
            font-weight: 700;
            padding: {{ $s(6, 4) }}px;
        }

        /* ===== خط القص ===== */
        .cut-label {
            text-align: center;
            font-size: {{ $s(7.5, 6.5) }}px;
            font-weight: 700;
            color: #14432f;
            border-top: 1.5px dashed #14432f;
            padding-top: {{ $s(5, 3) }}px;
            margin: {{ $s(13, 7) }}px 0 {{ $s(9, 5) }}px;
        }

        /* ===== الكعب ===== */
        .stub {
            border: 1.4px solid #14432f;
            border-radius: {{ $s(10, 8) }}px;
            padding: {{ $s(8, 5) }}px {{ $s(12, 9) }}px;
        }

        .stub-title {
            font-size: {{ $s(9, 7.5) }}px;
            font-weight: 700;
            color: #14432f;
            margin-bottom: {{ $s(6, 3) }}px;
        }

        .stub-title span {
            color: #6b6b6b;
            font-weight: 400;
            font-size: {{ $s(7.5, 6.5) }}px;
        }

        .stub-grid td {
            font-size: {{ $s(8, 6.5) }}px;
            padding: {{ $s(2, 1) }}px 0;
        }

        .stub-grid .label {
            color: #6b6b6b;
            width: 26%;
        }

        .stub-grid .value {
            font-weight: 700;
        }

        .stub-sign td {
            border-top: 1px dotted #999;
            padding-top: {{ $s(4, 2) }}px;
            margin-top: {{ $s(6, 3) }}px;
            font-size: {{ $s(7, 6) }}px;
            color: #6b6b6b;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="sheet">
        {{-- ===================== الأصل (نسخة العميل) ===================== --}}
        <div class="head">
            <table>
                <tr>
                    <td style="width: {{ $s(36, 28) }}px; vertical-align: middle;">
                        <div class="seal">أ</div>
                    </td>
                    <td style="vertical-align: middle; padding-right: {{ $s(9, 6) }}px;">
                        <div class="brand-name">أكوا ووتر</div>
                    </td>
                    <td style="text-align: left; vertical-align: middle;">
                        <div class="doc-title">إيصال تحصيل قسط</div>
                        <div class="doc-meta">
                            #{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }} ·
                            <span dir="ltr">{{ now()->format('Y-m-d') }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="code-strip">
            كود العقد: <b dir="ltr">{{ $installment->contract->code }}</b>
            &nbsp; — &nbsp;
            القسط: <b>{{ $installment->number }}</b>
        </div>

        <table class="field-row">
            <tr>
                <td class="label">العميل</td>
                <td class="value">{{ $installment->contract->customer?->name }}</td>
            </tr>
            <tr>
                <td class="label">الهاتف</td>
                <td class="value" dir="ltr">
                    {{ $installment->contract->customer?->phones->pluck('phone')->join('  /  ') ?: '—' }}</td>
            </tr>
            @php(
                $address = $installment->contract->customer?->detailed_address
                    ?: collect([
                        $installment->contract->customer?->governorate,
                        $installment->contract->customer?->center,
                        $installment->contract->customer?->village,
                    ])->filter()->join(' — ')
            )
            <tr>
                <td class="label">العنوان</td>
                <td class="value">{{ $address ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">تاريخ الاستحقاق</td>
                <td class="value" dir="ltr">{{ $installment->due_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="label">تاريخ التحصيل</td>
                <td class="value" dir="ltr">{{ $installment->paid_at->format('Y-m-d') }}</td>
            </tr>
            @if ($installment->collectedBy || $installment->salesRep)
                <tr>
                    <td class="label">المُحصِّل</td>
                    <td class="value">
                        {{ collect([$installment->collectedBy?->name, $installment->salesRep?->name])->filter()->join(' — ') }}
                    </td>
                </tr>
            @endif
        </table>

        <div class="amount-box">
            <table>
                <tr>
                    <td style="vertical-align: middle;">
                        <div class="amount-label">المبلغ المحصَّل</div>
                        <div class="amount-value">{{ number_format((float) $installment->paid_amount, 2) }}
                            <span>ج.م</span>
                        </div>
                    </td>
                    <td style="width: {{ $s(76, 58) }}px; vertical-align: middle; text-align: center;">
                        <div class="stamp">✓ تم<br>السداد</div>
                    </td>
                </tr>
            </table>
        </div>

        @if ($installment->note)
            <table class="field-row">
                <tr>
                    <td class="label">ملاحظة</td>
                    <td class="value">{{ \Illuminate\Support\Str::limit($installment->note, 110) }}</td>
                </tr>
            </table>
        @endif

        <div class="penalty">
            <b>شرط الغرامة:</b> عدم السداد عند الانتقال للتحصيل يُطبَّق عنه غرامة 30 جنيهًا أول مرة، و50 جنيهًا في
            المرة الثانية.
        </div>

        <table class="sign-strip">
            <tr>
                <td>
                    <div>رقم العقد</div>
                    <div class="cell" dir="ltr">{{ $installment->contract->code }}</div>
                </td>
                <td>
                    <div>المُحصِّل</div>
                    <div class="cell">{{ $installment->collectedBy?->name ?? '—' }}</div>
                </td>
                <td>
                    <div>المندوبة</div>
                    <div class="cell">{{ $installment->salesRep?->name ?? '—' }}</div>
                </td>
            </tr>
        </table>

        <div class="support-strip">للاستفسار والدعم الفني: 19345</div>

        {{-- ===================== خط القص ===================== --}}
        <div class="cut-label">✂ يُفصل هنا — كعب الشركة ✂</div>

        {{-- ===================== الكعب (نسخة الشركة) ===================== --}}
        <div class="stub">
            <div class="stub-title">أكوا ووتر <span>— نسخة الشركة #{{ str_pad($installment->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>

            <table class="stub-grid">
                <tr>
                    <td class="label">العميل</td>
                    <td class="value">{{ $installment->contract->customer?->name }}</td>
                </tr>
                <tr>
                    <td class="label">العقد</td>
                    <td class="value" dir="ltr">{{ $installment->contract->code }} — القسط {{ $installment->number }}
                    </td>
                </tr>
                <tr>
                    <td class="label">التاريخ</td>
                    <td class="value" dir="ltr">{{ $installment->paid_at->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td class="label">المبلغ</td>
                    <td class="value">{{ number_format((float) $installment->paid_amount, 2) }} ج.م</td>
                </tr>
            </table>

            <table class="stub-sign">
                <tr>
                    <td style="width: 50%;">توقيع العميل</td>
                    <td style="width: 50%;">توقيع المُحصِّل</td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
