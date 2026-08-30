<?php

namespace App\Support;

/**
 * تحويل الأرقام العربية إلى إنجليزية وإزالة أي فواصل — تُستخدم مع الهواتف والرقم القومي.
 */
class Digits
{
    private const ARABIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const ENGLISH = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public static function normalize(?string $value): string
    {
        $value = str_replace(self::ARABIC, self::ENGLISH, (string) $value);

        return preg_replace('/\D/', '', $value) ?? '';
    }
}
