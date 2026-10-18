<?php

namespace App\Domain\Sales\Enums;

/**
 * بيع جهاز (ببنود تُصرف من المخزون) أو عقد صيانة فقط (بسعر يُكتب يدويًا وبلا بنود).
 * النوعان يشتركان في نفس جدول الأقساط والتحصيل والتحويل للشؤون القانونية.
 */
enum ContractKind: string
{
    case Sale = 'sale';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'بيع جهاز',
            self::Maintenance => 'عقد صيانة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Sale => 'blue',
            self::Maintenance => 'teal',
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
