<?php

namespace App\Domain\Inventory\Enums;

/**
 * المسودة لا تمس أي رصيد؛ الترحيل وحده يُطبّق أثر التسوية على العهدة.
 */
enum CustodySettlementStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Posted => 'مُرحّلة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Posted => 'blue',
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
