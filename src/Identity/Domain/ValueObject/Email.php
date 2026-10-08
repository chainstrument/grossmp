<?php

namespace App\Identity\Domain\ValueObject;

/**
 * Immutable value object for a user's email address.
 *
 * Stored on User as a plain scalar column; this wrapper only exists to keep
 * the validation rule in one place and to make "a raw string" and "a valid
 * email" two different types in the domain's method signatures — the same
 * role App\Catalog\Domain\ValueObject\Sku plays for Product.
 */
final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ('' === $value || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('Invalid email "%s".', $value));
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
