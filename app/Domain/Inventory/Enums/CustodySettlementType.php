<?php

namespace App\Domain\Inventory\Enums;

/**
 * اتجاه تسوية العهدة — يُستنتج من فروق الجرد ولا يختاره المستخدم:
 * الكمية الفعلية أقل من العهدة = سالب (عجز)، أكثر منها = موجب (زيادة)، والمستند قد يجمع الاثنين.
 */
enum CustodySettlementType: string
{
    case Positive = 'positive';
    case Negative = 'negative';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'تسوية بالموجب',
            self::Negative => 'تسوية بالسالب',
            self::Mixed => 'تسوية موجبة وسالبة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Positive => 'green',
            self::Negative => 'red',
            self::Mixed => 'amber',
        };
    }

    /** اتجاه سطر واحد من فرقه — null لو الكمية الفعلية مطابقة للعهدة */
    public static function forDifference(int $difference): ?self
    {
        return match (true) {
            $difference > 0 => self::Positive,
            $difference < 0 => self::Negative,
            default => null,
        };
    }

    /**
     * اتجاه المستند كله من فروق سطوره — null لو كل الكميات مطابقة فلا شيء يُسوّى.
     *
     * @param  iterable<int>  $differences
     */
    public static function forDifferences(iterable $differences): ?self
    {
        $directions = collect($differences)->map(fn (int $difference) => self::forDifference($difference))->filter()->unique();

        return match ($directions->count()) {
            0 => null,
            1 => $directions->first(),
            default => self::Mixed,
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
