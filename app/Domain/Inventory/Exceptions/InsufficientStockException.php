<?php

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function for(string $product, int $available, int $requested): self
    {
        return new self("الكمية المتاحة من «{$product}» هي {$available} فقط، والمطلوب {$requested}.");
    }
}
