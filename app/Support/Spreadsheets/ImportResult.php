<?php

namespace App\Support\Spreadsheets;

/** نتيجة استيراد شيت: كل الصفوف نجحت (created) أو لا شيء وأخطاء كل صف */
final readonly class ImportResult
{
    /** @param  array<int, list<string>>  $errors  رقم الصف في الإكسيل => أخطاؤه */
    public function __construct(
        public int $created,
        public int $rows,
        public array $errors = [],
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}
