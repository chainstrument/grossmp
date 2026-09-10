<?php

namespace App\Ordering\Infrastructure\Persistence\Doctrine;

use App\Entity\Company;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Enum\OrderStatus;
use App\Ordering\Domain\Repository\OrderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class DoctrineOrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findById(int $id): ?Order
    {
        return $this->find($id);
    }

    public function findDraftForCompany(Company $company): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.company = :company')
            ->andWhere('o.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', OrderStatus::DRAFT)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function add(Order $order): void
    {
        $this->getEntityManager()->persist($order);
    }
}
