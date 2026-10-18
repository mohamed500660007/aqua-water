<?php

namespace App\Support\Spreadsheets;

/**
 * تعريف عمود واحد في شيت استيراد — نفس التعريف يبني القالب ويقرأ الملف المرفوع.
 */
final readonly class SheetColumn
{
    /** نص صريح: يمنع الإكسيل من حذف الصفر الأول أو تحويل الرقم الطويل لصيغة علمية */
    public const TEXT = 'text';

    public const DATE = 'date';

    /**
     * @param  list<string>  $options  قائمة منسدلة في الخلية
     * @param  bool  $strict  الصارمة ترفض أي قيمة خارج القائمة، وغيرها تقترح فقط
     */
    public function __construct(
        public string $key,
        public string $title,
        public bool $required = false,
        public int $width = 16,
        public ?string $format = null,
        public ?string $note = null,
        public array $options = [],
        public bool $strict = true,
    ) {}
}
