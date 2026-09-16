<?php

namespace App\Ordering\Infrastructure\Persistence\Doctrine;

use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Entity\OrderStatusHistory;
use App\Ordering\Domain\Repository\OrderStatusHistoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderStatusHistory>
 */
class DoctrineOrderStatusHistoryRepository extends ServiceEntityRepository implements OrderStatusHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderStatusHistory::class);
    }

    public function add(OrderStatusHistory $entry): void
    {
        $this->getEntityManager()->persist($entry);
    }

    public function findByOrder(Order $order): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.order = :order')
            ->setParameter('order', $order)
            ->orderBy('h.performedAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
