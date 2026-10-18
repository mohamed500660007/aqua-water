<?php

namespace App\Http\Controllers\Customers;

use App\Domain\Customers\Import\CustomerImporter;
use App\Domain\Customers\Import\CustomerSpreadsheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\SpreadsheetImportRequest;
use App\Http\Responses\XlsxDownload;
use App\Support\Spreadsheets\SpreadsheetImportException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportController extends Controller
{
    public function create(): View
    {
        return view('pages.imports.show', [
            'title' => 'استيراد العملاء من إكسيل',
            'subtitle' => 'حمّل القالب، املأه ببيانات العملاء، ثم ارفعه مرة واحدة',
            'backUrl' => route('customers.index'),
            'backLabel' => 'رجوع للعملاء',
            'templateUrl' => route('customers.import.template'),
            'storeUrl' => route('customers.import.store'),
            'templateHint' => 'قالب فارغ بالأعمدة الصحيحة وقوائم منسدلة للحالة ونوع العميل والمحافظة — الأعمدة الخضراء إجبارية.',
            'maxRows' => CustomerSpreadsheet::MAX_ROWS,
            'noun' => 'عميل',
            'tips' => [
                'عميل واحد في كل صف، بدءًا من الصف الثاني، ولا تغيّر عناوين الأعمدة.',
                'اسم العميل والهاتف الأساسي إجباريان، وباقي الأعمدة اختيارية.',
                'الهواتف بالصفر في أولها مثل 01012345678، والرقم القومي 14 رقمًا.',
                'الحالة الفارغة تُسجَّل «نشط»، ونوع العميل الفارغ «داخلي»، والتاريخ الفارغ تاريخ اليوم.',
                'لو في صف واحد فيه خطأ لن يُستورد أي عميل، فلا يتكرر أحد عند إعادة رفع الملف.',
            ],
        ]);
    }

    /** قالب فارغ بالأعمدة والقوائم المنسدلة لتملأه الموظفة ثم ترفعه */
    public function template(CustomerSpreadsheet $spreadsheet): StreamedResponse
    {
        return XlsxDownload::make($spreadsheet->template(), 'aqua-water-customers-template.xlsx');
    }

    public function store(SpreadsheetImportRequest $request, CustomerImporter $importer): RedirectResponse
    {
        try {
            $result = $importer->import($request->file('file')->getRealPath());
        } catch (SpreadsheetImportException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        if ($result->failed()) {
            return back()->with('importErrors', $result->errors)->with('importRows', $result->rows);
        }

        return redirect()
            ->route('customers.index')
            ->with('success', "تم استيراد {$result->created} عميل بنجاح.");
    }
}
