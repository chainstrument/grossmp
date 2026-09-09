<?php

namespace App\Catalog\Application;

use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\Service\PriceCalculatorInterface;
use App\Catalog\Domain\ValueObject\Money;
use App\Entity\Company;

/**
 * Application service for use case #16: the effective unit price of a
 * product for a given client and quantity.
 *
 * Thin orchestration only: fetching + not-found handling is here, the actual
 * pricing rule lives in the domain (PriceCalculatorInterface).
 */
final class ProductPriceResolver
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly PriceCalculatorInterface $priceCalculator,
    ) {
    }

    public function resolveUnitPrice(int $productId, int $quantity, ?Company $company): Money
    {
        $product = $this->products->findById($productId);
        if (null === $product) {
            throw ProductNotFoundException::withId($productId);
        }

        return $this->priceCalculator->effectiveUnitPrice($product, $quantity, $company);
    }
}
