<?php

namespace App\Catalog\Application;

use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\Repository\ProductSearchCriteria;

/**
 * Application service for use case #17: browse/search the catalogue.
 *
 * Thin orchestration only — no business rules of its own, it just turns a
 * search request into a paginated result via the domain repository.
 */
final class ProductCatalogFinder
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    public function search(ProductSearchCriteria $criteria): CatalogPage
    {
        return new CatalogPage(
            items: $this->products->search($criteria),
            totalCount: $this->products->countMatching($criteria),
            page: $criteria->page,
            perPage: $criteria->perPage,
        );
    }
}
