<?php

/*
| رسائل التحقق بالعربية — تشمل القواعد المستخدمة في نماذج لوحة التحكم.
*/

return [
    'accepted' => 'يجب قبول :attribute.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'date' => 'يجب أن يكون :attribute تاريخًا صحيحًا.',
    'date_format' => 'لا يتوافق :attribute مع الصيغة :format.',
    'different' => 'يجب أن يكون :attribute مختلفًا عن :other.',
    'digits' => 'يجب أن يتكون :attribute من :digits رقمًا.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max رقمًا.',
    'distinct' => 'قيمة :attribute مكررة.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'enum' => 'القيمة المختارة في :attribute غير صحيحة.',
    'exists' => 'القيمة المختارة في :attribute غير موجودة.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'filled' => 'حقل :attribute مطلوب.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المختارة في :attribute غير صحيحة.',
    'integer' => 'يجب أن يكون :attribute رقمًا صحيحًا.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصر.',
        'file' => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max حرفًا.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عنصر على الأقل.',
        'file' => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min حرفًا.',
    ],
    'not_in' => 'القيمة المختارة في :attribute غير صحيحة.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'present' => 'يجب إرسال حقل :attribute.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عنصر.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يكون :attribute :size حرفًا.',
    ],
    'string' => 'يجب أن يكون :attribute نصًا.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'url' => 'صيغة :attribute غير صحيحة.',

    'custom' => [],
    'attributes' => [],
];
