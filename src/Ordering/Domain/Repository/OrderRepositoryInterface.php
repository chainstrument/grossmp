<?php

namespace App\Ordering\Domain\Repository;

use App\Entity\Company;
use App\Ordering\Domain\Entity\Order;

/**
 * Domain-facing contract for Order persistence — see
 * App\Ordering\Infrastructure\Persistence\Doctrine\DoctrineOrderRepository
 * for the concrete implementation, wired in config/services.yaml.
 */
interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    /**
     * The company's current cart, if it has one open.
     */
    public function findDraftForCompany(Company $company): ?Order;

    public function add(Order $order): void;
}
