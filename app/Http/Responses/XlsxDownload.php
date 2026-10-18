<?php

namespace App\Http\Responses;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** تنزيل شيت إكسيل مباشرة دون حفظه على القرص */
final class XlsxDownload
{
    public static function make(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
