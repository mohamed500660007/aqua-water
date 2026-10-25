<?php

namespace App\Support\Spreadsheets;

use RuntimeException;

/** مشكلة في الملف نفسه (لا في بيانات صف بعينه) تمنع قراءته من الأساس */
class SpreadsheetImportException extends RuntimeException
{
    public static function unreadable(): self
    {
        return new self('تعذّرت قراءة الملف — تأكد أنه ملف إكسيل (.xlsx) سليم.');
    }

    public static function tooManyRows(int $max): self
    {
        return new self("الملف يحتوي على أكثر من {$max} صف — قسّمه إلى أكثر من ملف.");
    }

    /** @param  list<string>  $columns */
    public static function invalidTemplate(array $columns): self
    {
        return new self('الملف لا يطابق القالب — الأعمدة التالية غير موجودة: '.implode('، ', $columns).'. حمّل القالب من جديد واملأه.');
    }

    public static function empty(): self
    {
        return new self('الملف لا يحتوي على أي بيانات — املأ الصفوف بدءًا من الصف الثاني.');
    }
}
