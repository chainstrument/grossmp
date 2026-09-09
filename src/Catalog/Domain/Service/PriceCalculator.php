<?php

namespace App\Catalog\Domain\Service;

use App\Catalog\Domain\Entity\PriceTier;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\ValueObject\Money;
use App\Entity\Company;

/**
 * Resolves the price-tier rules of a Product into a single effective unit price.
 *
 * Rule: a tier negotiated for a specific company always wins over a generic
 * one, regardless of quantity thresholds. Within each group (company-specific,
 * then generic), the tier with the highest minQuantity satisfied by the
 * requested quantity applies. With no matching tier at all, the product's
 * base price applies.
 */
final class PriceCalculator implements PriceCalculatorInterface
{
    public function effectiveUnitPrice(Product $product, int $quantity, ?Company $company): Money
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        $companyTier = $this->bestMatchingTier($product, $quantity, $company, forCompanyOnly: true);
        if (null !== $companyTier) {
            return $companyTier->getUnitPrice();
        }

        $genericTier = $this->bestMatchingTier($product, $quantity, null, forCompanyOnly: false);
        if (null !== $genericTier) {
            return $genericTier->getUnitPrice();
        }

        return $product->getBasePrice();
    }

    private function bestMatchingTier(Product $product, int $quantity, ?Company $company, bool $forCompanyOnly): ?PriceTier
    {
        $best = null;

        foreach ($product->getPriceTiers() as $tier) {
            if ($forCompanyOnly && null === $tier->getCompany()) {
                continue;
            }

            if (!$forCompanyOnly && null !== $tier->getCompany()) {
                continue;
            }

            if (!$tier->appliesTo($quantity, $company)) {
                continue;
            }

            if (null === $best || $tier->getMinQuantity() > $best->getMinQuantity()) {
                $best = $tier;
            }
        }

        return $best;
    }
}
