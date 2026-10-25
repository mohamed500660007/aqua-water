<?php

namespace App\Domain\Maintenance\Enums;

/**
 * قطاعي: شمعة واحدة بسعرها. جملة: كل شمعات العقد الباقية مرة واحدة، فيخرج العميل من ملف الصيانة.
 */
enum MaintenanceType: string
{
    case PerItem = 'per_item';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::PerItem => 'قطاعي',
            self::Package => 'جملة',
        };
    }

    /** خيارات الاختيار: القيمة المخزّنة => النص الظاهر */
    public static function options(): array
    {
        return array_column(array_map(fn (self $type) => [$type->value, $type->label()], self::cases()), 1, 0);
    }

    public function tone(): string
    {
        return match ($this) {
            self::PerItem => 'blue',
            self::Package => 'purple',
        };
    }
}
