<?php

namespace App\Domain\Maintenance\Enums;

/**
 * قطاعي: شمعة أو أكثر بسعر لكل شمعة وعمولة لكل شمعة.
 * من الباكيج: تُستهلك من رصيد عقد الصيانة بلا أسعار (المبلغ مسجّل في عقد الصيانة) وبلا عمولة (عمولة العقد موحّدة).
 */
enum MaintenanceOperationType: string
{
    case PerItem = 'per_item';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::PerItem => 'صيانة قطاعي',
            self::Package => 'من عقد الصيانة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PerItem => 'blue',
            self::Package => 'purple',
        };
    }

    /** خيارات القوائم المنسدلة: القيمة المخزّنة => النص الظاهر */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $options, self $type) => $options + [$type->value => $type->label()],
            [],
        );
    }
}
