<?php

namespace App\Catalog\Domain\ValueObject;

/**
 * Immutable value object for a product reference (SKU).
 *
 * Stored on Product as a plain scalar column; this wrapper only exists to keep
 * the validation rule in one place and to make "a raw string" and "a valid SKU"
 * two different types in the domain's method signatures.
 */
final class Sku
{
    private const PATTERN = '/^[A-Z0-9](?:[A-Z0-9-]{1,30}[A-Z0-9])?$/';

    private readonly string $value;

    public function __construct(string $value)
    {
        $value = strtoupper(trim($value));

        if (!preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException(sprintf('Invalid SKU "%s": expected 3 to 32 uppercase letters, digits or hyphens.', $value));
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
