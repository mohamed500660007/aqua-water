<?php

namespace App\Domain\Maintenance\Exceptions;

use RuntimeException;

class MaintenanceException extends RuntimeException
{
    public static function alreadyDone(): self
    {
        return new self('الشمعة دي اتغيرت خلاص — مش موجودة في ملف الصيانة.');
    }
}
