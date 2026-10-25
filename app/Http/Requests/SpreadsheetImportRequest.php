<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** رفع شيت إكسيل للاستيراد — مشترك بين كل شاشات الاستيراد */
class SpreadsheetImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'ملف الإكسيل'];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'اختر ملف الإكسيل أولًا.',
            'file.mimes' => 'الملف يجب أن يكون إكسيل بصيغة .xlsx — استخدم القالب المرفق.',
            'file.max' => 'حجم الملف أكبر من 5 ميجابايت.',
        ];
    }
}
