<?php

namespace App\Tests\Unit\Catalog;

use App\Catalog\Domain\Entity\PriceTier;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\Service\PriceCalculator;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Identity\Domain\Entity\Company;
use PHPUnit\Framework\TestCase;

/**
 * Pure domain test: no Symfony kernel, no database. This is the point of
 * keeping the pricing rule as a plain PHP domain service.
 */
class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator();
    }

    public function testFallsBackToBasePriceWithNoMatchingTier(): void
    {
        $product = $this->aProduct(basePrice: Money::fromEuros(10));

        $price = $this->calculator->effectiveUnitPrice($product, 1, null);

        self::assertTrue($price->equals(Money::fromEuros(10)));
    }

    public function testAppliesGenericTierWhenQuantityThresholdIsReached(): void
    {
        $product = $this->aProduct(basePrice: Money::fromEuros(10));
        $product->addPriceTier(new PriceTier(10, Money::fromEuros(8)));

        self::assertTrue($this->calculator->effectiveUnitPrice($product, 5, null)->equals(Money::fromEuros(10)), 'below threshold: base price');
        self::assertTrue($this->calculator->effectiveUnitPrice($product, 10, null)->equals(Money::fromEuros(8)), 'at threshold: tier price');
        self::assertTrue($this->calculator->effectiveUnitPrice($product, 99, null)->equals(Money::fromEuros(8)), 'above threshold: still tier price');
    }

    public function testPicksTheHighestSatisfiedThreshold(): void
    {
        $product = $this->aProduct(basePrice: Money::fromEuros(10));
        $product->addPriceTier(new PriceTier(10, Money::fromEuros(8)));
        $product->addPriceTier(new PriceTier(50, Money::fromEuros(6)));

        self::assertTrue($this->calculator->effectiveUnitPrice($product, 20, null)->equals(Money::fromEuros(8)));
        self::assertTrue($this->calculator->effectiveUnitPrice($product, 50, null)->equals(Money::fromEuros(6)));
    }

    public function testCompanySpecificTierWinsOverGenericTierRegardlessOfThreshold(): void
    {
        $company = new Company();
        $product = $this->aProduct(basePrice: Money::fromEuros(10));
        $product->addPriceTier(new PriceTier(10, Money::fromEuros(8)));
        // Negotiated rate, reachable from fewer units than the generic tier above.
        $product->addPriceTier(new PriceTier(5, Money::fromEuros(7), $company));

        self::assertTrue($this->calculator->effectiveUnitPrice($product, 5, $company)->equals(Money::fromEuros(7)));
        // Same quantity, no company: generic tier does not apply yet at 5 units, base price applies.
        self::assertTrue($this->calculator->effectiveUnitPrice($product, 5, null)->equals(Money::fromEuros(10)));
    }

    public function testCompanySpecificTierDoesNotApplyToAnotherCompany(): void
    {
        $companyA = new Company();
        $companyB = new Company();
        $product = $this->aProduct(basePrice: Money::fromEuros(10));
        $product->addPriceTier(new PriceTier(1, Money::fromEuros(7), $companyA));

        self::assertTrue($this->calculator->effectiveUnitPrice($product, 1, $companyB)->equals(Money::fromEuros(10)));
    }

    private function aProduct(Money $basePrice): Product
    {
        return new Product(new Sku('TEST-001'), 'Produit de test', ProductCategory::FOOD, $basePrice);
    }
}
