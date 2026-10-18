<?php

namespace App\Support\Spreadsheets;

use DateTimeImmutable;

/**
 * تنظيف قيم نصية مكتوبة يدويًا في الشيت قبل التحقق منها.
 */
final class SheetValue
{
    private const DATE_FORMATS = ['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y'];

    private const ARABIC_DIGITS = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];

    /**
     * تاريخ بأي صيغة شائعة (وبالأرقام العربية) إلى Y-m-d. غير المفهوم يُعاد كما هو ليرفضه التحقق برسالة واضحة.
     */
    public static function date(string $value): string
    {
        $value = self::digits(trim($value));

        foreach (self::DATE_FORMATS as $format) {
            // DateTimeImmutable لا يرمي استثناء عند عدم التطابق، والمقارنة العكسية تمنع قبول 2025-02-31
            $date = DateTimeImmutable::createFromFormat("!{$format}", $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    /** مبلغ مكتوب بفواصل الآلاف أو أرقام عربية */
    public static function number(string $value): string
    {
        return str_replace([',', '٬'], '', self::digits(trim($value)));
    }

    public static function digits(string $value): string
    {
        return strtr($value, self::ARABIC_DIGITS);
    }
}
