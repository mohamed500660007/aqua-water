<?php

namespace App\Domain\Customers\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Inactive => 'غير نشط',
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
