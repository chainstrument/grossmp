<?php

namespace App\Catalog\Domain\Exception;

final class ProductNotFoundException extends \RuntimeException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Product #%d was not found.', $id));
    }
}
