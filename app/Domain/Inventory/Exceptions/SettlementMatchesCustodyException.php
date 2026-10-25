<?php

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

/** الكميات الفعلية مطابقة لعهدة الموظف، فلا عجز ولا زيادة تستدعي تسوية */
class SettlementMatchesCustodyException extends RuntimeException
{
    public static function make(): self
    {
        return new self('الكميات الفعلية مطابقة لعهدة الموظف — لا يوجد عجز أو زيادة للتسوية.');
    }
}
