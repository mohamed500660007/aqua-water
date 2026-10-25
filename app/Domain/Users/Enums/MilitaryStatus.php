<?php

namespace App\Domain\Users\Enums;

/**
 * الموقف من التجنيد — يخص الذكور فقط.
 */
enum MilitaryStatus: string
{
    case Completed = 'completed';
    case Exempted = 'exempted';
    case Postponed = 'postponed';
    case NotYet = 'not_yet';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'أدّى الخدمة',
            self::Exempted => 'معفى',
            self::Postponed => 'مؤجَّل',
            self::NotYet => 'لم يحن موعده',
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
