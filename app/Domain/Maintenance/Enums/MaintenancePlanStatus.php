<?php

namespace App\Domain\Maintenance\Enums;

enum MaintenancePlanStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'مفعّل',
            self::Inactive => 'معطّل',
        };
    }

    /** خيارات القوائم المنسدلة: القيمة المخزّنة => النص الظاهر */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $options, self $status) => $options + [$status->value => $status->label()],
            [],
        );
    }
}
