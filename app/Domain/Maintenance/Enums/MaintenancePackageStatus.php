<?php

namespace App\Domain\Maintenance\Enums;

enum MaintenancePackageStatus: string
{
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'ساري',
            self::Closed => 'مُقفل — استُهلك بالكامل',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Closed => 'gray',
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
