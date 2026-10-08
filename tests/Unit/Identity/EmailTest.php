<?php

namespace App\Tests\Unit\Identity;

use App\Identity\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testAcceptsValidValues(string $value, string $expected): void
    {
        self::assertSame($expected, (new Email($value))->value());
    }

    public static function validValues(): iterable
    {
        yield 'simple address' => ['user@example.com', 'user@example.com'];
        yield 'surrounding whitespace is trimmed' => [' user@example.com ', 'user@example.com'];
        yield 'subdomain' => ['user@mail.example.com', 'user@mail.example.com'];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValues(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email($value);
    }

    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => ['   '];
        yield 'no @' => ['user.example.com'];
        yield 'no domain' => ['user@'];
        yield 'no local part' => ['@example.com'];
    }

    public function testTwoEmailsWithTheSameValueAreEqual(): void
    {
        self::assertTrue((new Email('user@example.com'))->equals(new Email('user@example.com')));
    }
}
