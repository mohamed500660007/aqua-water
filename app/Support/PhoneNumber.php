<?php

namespace App\Support;

/**
 * توحيد صيغة أرقام الهواتف المصرية قبل الحفظ أو البحث،
 * حتى لا يُعامل الرقم الواحد كأرقام مختلفة بسبب اختلاف الكتابة.
 */
class PhoneNumber
{
    /** الصيغة المعتمدة للتخزين: رقم محلي يبدأ بصفر (أرضي 8 أرقام حتى موبايل 11 رقمًا) */
    public const PATTERN = '/^0\d{7,10}$/';

    public static function normalize(?string $phone): ?string
    {
        $digits = Digits::normalize($phone);

        if ($digits === '') {
            return null;
        }

        // إزالة كود مصر الدولي بصيغه المختلفة (+20 / 0020 / 20) وإعادة الصفر المحلي
        foreach (['0020', '20'] as $prefix) {
            if (str_starts_with($digits, $prefix) && strlen($digits) > strlen($prefix) + 7) {
                $digits = substr($digits, strlen($prefix));
                break;
            }
        }

        return str_starts_with($digits, '0') ? $digits : '0'.$digits;
    }
}
