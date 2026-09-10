<?php

namespace App\Tests\Unit\Ordering;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Entity\Company;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Entity\OrderLine;
use App\Ordering\Domain\Enum\OrderStatus;
use App\Ordering\Domain\Exception\CartNotEditableException;
use PHPUnit\Framework\TestCase;

/**
 * Pure domain test: no Symfony kernel, no database.
 */
class OrderTest extends TestCase
{
    public function testNewOrderStartsAsDraftAndEmpty(): void
    {
        $order = new Order(new Company());

        self::assertTrue($order->isDraft());
        self::assertTrue($order->isEmpty());
        self::assertTrue($order->total()->equals(Money::zero()));
    }

    public function testAddingLinesUpdatesTheTotal(): void
    {
        $order = new Order(new Company());
        $order->addLine(new OrderLine($this->aProduct('SKU-P1'), 2, Money::fromEuros(10)));
        $order->addLine(new OrderLine($this->aProduct('SKU-P2'), 3, Money::fromEuros(5)));

        // (2 * 10) + (3 * 5) = 35
        self::assertTrue($order->total()->equals(Money::fromEuros(35)));
        self::assertFalse($order->isEmpty());
    }

    public function testRemovingALineUpdatesTheTotal(): void
    {
        $order = new Order(new Company());
        $line = new OrderLine($this->aProduct('SKU-P1'), 1, Money::fromEuros(10));
        $order->addLine($line);

        $order->removeLine($line);

        self::assertTrue($order->isEmpty());
        self::assertTrue($order->total()->equals(Money::zero()));
    }

    public function testCannotAddOrRemoveLinesOnceOutOfDraft(): void
    {
        $order = new Order(new Company());
        $line = new OrderLine($this->aProduct('SKU-P1'), 1, Money::fromEuros(10));
        $order->addLine($line);

        $this->forceStatus($order, OrderStatus::SUBMITTED);

        $this->expectException(CartNotEditableException::class);
        $order->addLine(new OrderLine($this->aProduct('SKU-P2'), 1, Money::fromEuros(5)));
    }

    public function testFindLineForProductReturnsTheMatchingLine(): void
    {
        $order = new Order(new Company());
        $product = $this->aProduct('SKU-P1');
        $line = new OrderLine($product, 1, Money::fromEuros(10));
        $order->addLine($line);

        self::assertSame($line, $order->findLineForProduct($product));
        self::assertNull($order->findLineForProduct($this->aProduct('SKU-P2')));
    }

    private function aProduct(string $reference): Product
    {
        return new Product(new Sku($reference), 'Produit '.$reference, ProductCategory::FOOD, Money::fromEuros(1));
    }

    /**
     * Status is private with no public transition method yet (that's EPIC 5's
     * job) — reach in via reflection purely to set up this test's scenario.
     */
    private function forceStatus(Order $order, OrderStatus $status): void
    {
        $property = new \ReflectionProperty(Order::class, 'status');
        $property->setValue($order, $status);
    }
}
