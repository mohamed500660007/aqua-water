<?php

namespace App\Domain\Inventory\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'وارد',
            self::Out => 'منصرف',
            self::Adjustment => 'تسوية جرد',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::In => 'green',
            self::Out => 'red',
            self::Adjustment => 'amber',
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
