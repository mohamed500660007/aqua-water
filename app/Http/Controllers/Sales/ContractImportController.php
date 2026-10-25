<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Sales\Import\ContractImporter;
use App\Domain\Sales\Import\ContractReferences;
use App\Domain\Sales\Import\ContractSpreadsheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\SpreadsheetImportRequest;
use App\Http\Responses\XlsxDownload;
use App\Support\Spreadsheets\SpreadsheetImportException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ContractImportController extends Controller
{
    public function create(): View
    {
        return view('pages.imports.show', [
            'title' => 'استيراد العقود القديمة من إكسيل',
            'subtitle' => 'ترحيل عقود مسجلة ورقيًا أو في نظام قديم — بدون أي خصم من المخزون',
            'backUrl' => route('contracts.index'),
            'backLabel' => 'رجوع للعقود',
            'templateUrl' => route('contracts.import.template'),
            'storeUrl' => route('contracts.import.store'),
            'templateHint' => 'قالب بقوائم منسدلة للمنتجات والموظفين والمخازن وخطط الصيانة كما هي مسجلة في النظام الآن — حمّله من جديد لو أضفت منتجًا أو موظفًا.',
            'maxRows' => ContractSpreadsheet::MAX_ROWS,
            'noun' => 'عقد',
            'tips' => [
                'العميل لازم يكون مسجلًا: اكتب كوده (CUS-00012) أو رقم هاتفه — سجّل العملاء أولًا من استيراد العملاء.',
                'عقد البيع يحتاج منتجًا واحدًا على الأقل (حتى 3)، وعقد الصيانة يحتاج سعر العقد فقط.',
                'سعر القطعة الفارغ = سعر المنتج الحالي؛ اكتبه لو كان سعر البيع وقتها مختلفًا.',
                '«المدفوع حتى الآن» يشمل المقدم وكل الأقساط المحصّلة، والمتبقي يُقسَّم على عدد الأقساط المتبقية.',
                'العقود المستوردة لا تخصم أي كمية من المخزن أو عهدة الموظفين، حتى لو عُدّلت لاحقًا.',
                'لو في صف واحد فيه خطأ لن يُستورد أي عقد، فلا يتكرر أحد عند إعادة رفع الملف.',
            ],
        ]);
    }

    /** القالب يُبنى بالقوائم الحالية (منتجات/موظفون/مخازن/خطط) وقت التحميل */
    public function template(ContractSpreadsheet $spreadsheet): Response
    {
        return XlsxDownload::make($spreadsheet->template(new ContractReferences), 'aqua-water-contracts-template.xlsx');
    }

    public function store(SpreadsheetImportRequest $request, ContractImporter $importer): RedirectResponse
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
            ->route('contracts.index')
            ->with('success', "تم استيراد {$result->created} عقد بنجاح.");
    }
}
