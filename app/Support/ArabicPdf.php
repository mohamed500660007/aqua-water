<?php

namespace App\Support;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * مُولِّد PDF بدعم عربي صحيح — الخط والشكل، لكن الأهم أن ترتيب النص المنطقي
 * يبقى سليمًا داخل الملف (نسخ ولصق وبحث لا يُخرِج حروفًا معكوسة كما يحدث مع dompdf).
 */
class ArabicPdf
{
    /** @param  array{0: float, 1: float}  $format  [width_mm, height_mm] */
    public static function make(array $format = [100, 150]): Mpdf
    {
        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => $format,
            'default_font' => 'almarai',
            'fontDir' => [...$fontDirs, resource_path('fonts')],
            'fontdata' => $fontData + [
                'almarai' => [
                    'R' => 'Almarai-Regular.ttf',
                    'B' => 'Almarai-ExtraBold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'directionality' => 'rtl',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);
    }
}
