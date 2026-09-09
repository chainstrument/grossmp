<?php

namespace App\Catalog\Application;

use App\Catalog\Domain\Entity\Product;

/**
 * Read-only result of a catalogue search: the current page of products plus
 * enough information to render pagination controls.
 */
final class CatalogPage
{
    /**
     * @param Product[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $totalCount,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        return max(1, (int) ceil($this->totalCount / $this->perPage));
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }
}
