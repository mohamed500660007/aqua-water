<?php

namespace App\Domain\Users\Enums;

enum Gender: string
{
    // نفس القيم التي يعيدها App\Support\NationalId::parse()
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'ذكر',
            self::Female => 'أنثى',
        };
    }

    /** خيارات القوائم المنسدلة: القيمة المخزّنة => النص الظاهر */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $options, self $gender) => $options + [$gender->value => $gender->label()],
            [],
        );
    }
}
