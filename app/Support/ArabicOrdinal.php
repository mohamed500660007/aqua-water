<?php

namespace App\Support;

/**
 * الرقم الترتيبي بالعربية بصيغة المذكر (القسط الأول، الثاني عشر، الحادي والعشرون...).
 * ما بعد المائة يُكتب رقمًا لأن الأقساط لا تتجاوزها عمليًا.
 */
final class ArabicOrdinal
{
    private const UNITS = [
        1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس',
        6 => 'السادس', 7 => 'السابع', 8 => 'الثامن', 9 => 'التاسع', 10 => 'العاشر',
    ];

    /** صيغة الآحاد داخل الأعداد المركّبة: «الحادي» عشر/والعشرون بدل «الأول» */
    private const COMPOUND_UNITS = [1 => 'الحادي'] + self::UNITS;

    private const TENS = [
        2 => 'العشرون', 3 => 'الثلاثون', 4 => 'الأربعون', 5 => 'الخمسون',
        6 => 'الستون', 7 => 'السبعون', 8 => 'الثمانون', 9 => 'التسعون',
    ];

    private const FEMININE_UNITS = [
        1 => 'الأولى', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة', 5 => 'الخامسة',
        6 => 'السادسة', 7 => 'السابعة', 8 => 'الثامنة', 9 => 'التاسعة', 10 => 'العاشرة',
    ];

    /** صيغة المؤنث حتى العاشرة (الشمعة الأولى، الثانية...) — ما بعدها يُكتب رقمًا */
    public static function feminine(int $number): string
    {
        return self::FEMININE_UNITS[$number] ?? (string) $number;
    }

    public static function of(int $number): string
    {
        if ($number >= 1 && $number <= 10) {
            return self::UNITS[$number];
        }

        if ($number >= 11 && $number <= 19) {
            return self::COMPOUND_UNITS[$number - 10].' عشر';
        }

        if ($number >= 20 && $number <= 99) {
            $tens = self::TENS[intdiv($number, 10)];
            $unit = $number % 10;

            return $unit === 0 ? $tens : self::COMPOUND_UNITS[$unit].' و'.$tens;
        }

        return $number === 100 ? 'المائة' : (string) $number;
    }
}
