<?php

namespace App\Domain\Users\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::OnLeave => 'إجازة',
            self::Suspended => 'موقوف',
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
