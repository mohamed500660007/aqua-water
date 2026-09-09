<?php

namespace App\Domain\Maintenance\Enums;

enum WarrantyStatus: string
{
    case InWarranty = 'in_warranty';
    case OutOfWarranty = 'out_of_warranty';

    public function label(): string
    {
        return match ($this) {
            self::InWarranty => 'داخل الضمان',
            self::OutOfWarranty => 'خارج الضمان',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::InWarranty => 'green',
            self::OutOfWarranty => 'amber',
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
