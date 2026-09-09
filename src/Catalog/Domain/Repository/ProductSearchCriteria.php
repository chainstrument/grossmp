<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Enum\ProductCategory;

/**
 * Immutable query object for Product::search()/countMatching(). Keeps the
 * repository interface stable even as new filters get added later.
 */
final class ProductSearchCriteria
{
    private function __construct(
        public readonly ?string $searchTerm = null,
        public readonly ?ProductCategory $category = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page must be 1 or greater.');
        }

        if ($this->perPage < 1) {
            throw new \InvalidArgumentException('perPage must be 1 or greater.');
        }
    }

    public static function create(
        ?string $searchTerm = null,
        ?ProductCategory $category = null,
        int $page = 1,
        int $perPage = 20,
    ): self {
        return new self($searchTerm, $category, $page, $perPage);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
