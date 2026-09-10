<?php

namespace App\Tests\Unit\Catalog;

use App\Catalog\Domain\ValueObject\Sku;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SkuTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsValidValues(string $value, string $expected): void
    {
        self::assertSame($expected, (new Sku($value))->value());
    }

    public static function validValues(): iterable
    {
        yield 'lowercase gets uppercased' => ['abc', 'ABC'];
        yield 'minimum length (3)' => ['ABC', 'ABC'];
        yield 'with hyphens' => ['SAU-TOM-500', 'SAU-TOM-500'];
        yield 'surrounding whitespace is trimmed' => [' ABC ', 'ABC'];
        yield 'maximum length (32)' => [str_repeat('A', 32), str_repeat('A', 32)];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValues(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Sku($value);
    }

    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'too short (1 char)' => ['A'];
        yield 'too short (2 chars)' => ['AB'];
        yield 'too long (33 chars)' => [str_repeat('A', 33)];
        yield 'starts with a hyphen' => ['-ABC'];
        yield 'ends with a hyphen' => ['ABC-'];
        yield 'contains a space' => ['AB C'];
    }

    public function testTwoSkusWithTheSameValueAreEqual(): void
    {
        self::assertTrue((new Sku('abc'))->equals(new Sku('ABC')));
    }
}
