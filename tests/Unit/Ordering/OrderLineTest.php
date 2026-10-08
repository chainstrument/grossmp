<?php

namespace App\Tests\Unit\Ordering;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Ordering\Domain\Entity\OrderLine;
use PHPUnit\Framework\TestCase;

/**
 * Pure domain test: no Symfony kernel, no database.
 */
class OrderLineTest extends TestCase
{
    public function testRejectsAQuantityOf500OrMore(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OrderLine($this->aProduct(), 500, Money::fromEuros(1));
    }

    public function testAcceptsAQuantityJustUnder500(): void
    {
        $line = new OrderLine($this->aProduct(), 499, Money::fromEuros(1));

        self::assertSame(499, $line->getQuantity());
    }

    private function aProduct(): Product
    {
        return new Product(new Sku('TEST-LINE-QTY'), 'Produit testé', ProductCategory::FOOD, Money::fromEuros(1));
    }
}
