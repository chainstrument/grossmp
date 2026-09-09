<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\ValueObject\Sku;

/**
 * Domain-facing contract for Product persistence. The application layer only
 * ever depends on this interface, never on Doctrine — see
 * App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineProductRepository
 * for the concrete implementation, wired in config/services.yaml.
 */
interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findByReference(Sku $reference): ?Product;

    public function add(Product $product): void;

    /**
     * @return Product[]
     */
    public function search(ProductSearchCriteria $criteria): array;

    public function countMatching(ProductSearchCriteria $criteria): int;
}
