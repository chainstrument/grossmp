<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\Repository\ProductSearchCriteria;
use App\Catalog\Domain\ValueObject\Sku;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class DoctrineProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findById(int $id): ?Product
    {
        return $this->find($id);
    }

    public function findByReference(Sku $reference): ?Product
    {
        return $this->findOneBy(['reference' => $reference->value()]);
    }

    public function add(Product $product): void
    {
        $this->getEntityManager()->persist($product);
    }

    public function search(ProductSearchCriteria $criteria): array
    {
        return $this->queryForCriteria($criteria)
            ->setFirstResult($criteria->offset())
            ->setMaxResults($criteria->perPage)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countMatching(ProductSearchCriteria $criteria): int
    {
        return (int) $this->queryForCriteria($criteria)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function queryForCriteria(ProductSearchCriteria $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if (null !== $criteria->searchTerm && '' !== $criteria->searchTerm) {
            $qb->andWhere('p.name LIKE :term OR p.reference LIKE :term')
                ->setParameter('term', '%'.$criteria->searchTerm.'%');
        }

        if (null !== $criteria->category) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $criteria->category);
        }

        return $qb;
    }
}
