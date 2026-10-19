<?php

namespace App\Domain\Maintenance\Exceptions;

use RuntimeException;

class MaintenanceException extends RuntimeException
{
    public static function packageClosed(string $number): self
    {
        return new self("عقد الصيانة {$number} مُقفل — استُهلك رصيده بالكامل.");
    }

    public static function packageBalance(string $number, int $remaining, int $requested): self
    {
        return new self("رصيد عقد الصيانة {$number} {$remaining} شمعة فقط، والمطلوب {$requested}.");
    }

    public static function packageInUse(string $number): self
    {
        return new self("لا يمكن حذف عقد الصيانة {$number} لأن زيارات سُجّلت عليه — ألغِ الزيارات أولًا.");
    }
}
