<?php

namespace App\Domain\Sales\Enums;

enum ContractType: string
{
    case Cash = 'cash';
    case Installment = 'installment';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::Installment => 'تقسيط',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Cash => 'green',
            self::Installment => 'purple',
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
