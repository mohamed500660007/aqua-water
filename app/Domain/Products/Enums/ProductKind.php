<?php

namespace App\Domain\Products\Enums;

/**
 * نوع المنتج كما تحتاجه التقارير: عدد الأجهزة المركّبة، والشمع الوارد والمنصرف.
 */
enum ProductKind: string
{
    case Device = 'device';
    case Cartridge = 'cartridge';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Device => 'جهاز',
            self::Cartridge => 'شمعة',
            self::Other => 'قطع غيار وأخرى',
        };
    }

    /** خيارات القوائم المنسدلة: القيمة المخزّنة => النص الظاهر */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $options, self $kind) => $options + [$kind->value => $kind->label()],
            [],
        );
    }
}
