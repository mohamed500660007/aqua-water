<?php

namespace App\Domain\Customers\Enums;

/**
 * داخلي: اشترى الجهاز من الشركة. خارجي: جهازه من شركة أخرى ويتعامل معنا في الصيانة فقط.
 */
enum CustomerOrigin: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'داخلي',
            self::External => 'خارجي',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Internal => 'blue',
            self::External => 'orange',
        };
    }

    /** خيارات القوائم المنسدلة: القيمة المخزّنة => النص الظاهر */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $options, self $origin) => $options + [$origin->value => $origin->label()],
            [],
        );
    }
}
