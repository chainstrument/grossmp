<?php

namespace App\Catalog\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

/**
 * Immutable value object representing an amount of money.
 *
 * Amounts are stored as integer cents to avoid float rounding issues.
 * Two Money instances are only comparable/combinable if they share the same currency.
 */
#[ORM\Embeddable]
final class Money
{
    #[ORM\Column(name: 'amount', type: 'integer')]
    private readonly int $amountInCents;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private readonly string $currency;

    private function __construct(int $amountInCents, string $currency)
    {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException('A monetary amount cannot be negative.');
        }

        if (3 !== strlen($currency)) {
            throw new \InvalidArgumentException('Currency must be a 3-letter ISO 4217 code.');
        }

        $this->amountInCents = $amountInCents;
        $this->currency = strtoupper($currency);
    }

    public static function fromCents(int $amountInCents, string $currency = 'EUR'): self
    {
        return new self($amountInCents, $currency);
    }

    public static function fromEuros(float $amount, string $currency = 'EUR'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public static function zero(string $currency = 'EUR'): self
    {
        return new self(0, $currency);
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function toFloat(): float
    {
        return $this->amountInCents / 100;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function multipliedBy(int $quantity): self
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative.');
        }

        return new self($this->amountInCents * $quantity, $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents > $other->amountInCents;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInCents < $other->amountInCents;
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \LogicException(sprintf('Cannot compare amounts in different currencies (%s vs %s).', $this->currency, $other->currency));
        }
    }

    public function __toString(): string
    {
        return number_format($this->toFloat(), 2, ',', ' ').' '.$this->currency;
    }
}
