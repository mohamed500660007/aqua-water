<?php

namespace App\Domain\Inventory\Enums;

/**
 * اتجاه تسوية العهدة — يحدد إشارة الكمية، فالمستخدم يُدخل الكمية موجبة دائمًا.
 */
enum CustodySettlementType: string
{
    case Positive = 'positive';
    case Negative = 'negative';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'تسوية بالموجب',
            self::Negative => 'تسوية بالسالب',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Positive => 'green',
            self::Negative => 'red',
        };
    }

    /** حركة العهدة المقابلة: الموجب يزيد رصيد الموظف والسالب ينقصه */
    public function movementType(): StockMovementType
    {
        return match ($this) {
            self::Positive => StockMovementType::In,
            self::Negative => StockMovementType::Out,
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
