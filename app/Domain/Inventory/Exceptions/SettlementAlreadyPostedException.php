<?php

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

class SettlementAlreadyPostedException extends RuntimeException
{
    public static function for(string $number): self
    {
        return new self("التسوية {$number} مُرحّلة بالفعل ولا يمكن ترحيلها أو تعديلها أو حذفها.");
    }
}
