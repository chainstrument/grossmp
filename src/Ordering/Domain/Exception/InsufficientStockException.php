<?php

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\Entity\OrderLine;

final class InsufficientStockException extends \RuntimeException
{
    public static function forLine(OrderLine $line, string $reason): self
    {
        return new self($reason);
    }
}
