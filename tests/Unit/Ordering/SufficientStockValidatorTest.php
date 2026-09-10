<?php

namespace App\Tests\Unit\Ordering;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Ordering\Domain\Entity\OrderLine;
use App\Ordering\Domain\Validator\SufficientStock;
use App\Ordering\Domain\Validator\SufficientStockValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Symfony's ConstraintValidatorTestCase runs the validator in isolation
 * (mocked ExecutionContext) — no kernel, no database.
 */
class SufficientStockValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SufficientStockValidator
    {
        return new SufficientStockValidator();
    }

    public function testNoViolationWhenStockIsSufficient(): void
    {
        $product = $this->aProduct(stock: 10);
        $line = new OrderLine($product, 5, Money::fromEuros(1));

        $this->validator->validate($line, new SufficientStock());

        $this->assertNoViolation();
    }

    public function testViolationWhenQuantityExceedsStock(): void
    {
        $product = $this->aProduct(stock: 3);
        $line = new OrderLine($product, 5, Money::fromEuros(1));

        $constraint = new SufficientStock();
        $this->validator->validate($line, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ product }}', $product->getName())
            ->setParameter('{{ available }}', '3')
            ->setParameter('{{ requested }}', '5')
            ->atPath('property.path.quantity')
            ->assertRaised()
        ;
    }

    private function aProduct(int $stock): Product
    {
        $product = new Product(new Sku('TEST-STOCK'), 'Produit testé', ProductCategory::FOOD, Money::fromEuros(1));
        $product->adjustStock($stock);

        return $product;
    }
}
