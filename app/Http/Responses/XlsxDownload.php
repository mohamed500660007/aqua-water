<?php

namespace App\Http\Responses;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * تنزيل شيت إكسيل مباشرة دون حفظه على القرص. الملف يُبنى في الذاكرة ويُرسل كاملًا مرة واحدة
 * بدل البث (streaming)، لأن بيئات التشغيل المُدارة مثل Wasmer Edge تقطع الاستجابات المبثوثة.
 */
final class XlsxDownload
{
    public static function make(Spreadsheet $spreadsheet, string $fileName): Response
    {
        $buffer = fopen('php://memory', 'w+');
        (new Xlsx($spreadsheet))->save($buffer);
        rewind($buffer);
        $content = stream_get_contents($buffer);
        fclose($buffer);

        return new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName),
            'Content-Length' => (string) strlen($content),
        ]);
    }
}
