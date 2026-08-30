<?php

namespace App\Domain\Sales\Enums;

/**
 * مصدر صرف بضاعة العقد: من المخزن الرئيسي، أو مما يحمله مندوب المبيعات أو فني التركيب المعيَّن على العقد.
 */
enum StockSource: string
{
    case Warehouse = 'warehouse';
    case SalesRep = 'sales_rep';
    case Installer = 'installer';

    public function label(): string
    {
        return match ($this) {
            self::Warehouse => 'من المخزن الرئيسي',
            self::SalesRep => 'من مندوب المبيعات',
            self::Installer => 'من فني التركيب',
        };
    }

    /** خيارات القوائم المنسدلة: القيمة المخزّنة => النص الظاهر */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $options, self $source) => $options + [$source->value => $source->label()],
            [],
        );
    }
}
