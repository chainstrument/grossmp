<?php

namespace App\Catalog\Domain\Service;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\ValueObject\Money;
use App\Entity\Company;

interface PriceCalculatorInterface
{
    /**
     * The effective *unit* price of $product for $quantity units bought by
     * $company (null = anonymous/no negotiated rate).
     */
    public function effectiveUnitPrice(Product $product, int $quantity, ?Company $company): Money;
}
