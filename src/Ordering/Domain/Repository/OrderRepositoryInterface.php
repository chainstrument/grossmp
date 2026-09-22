<?php

namespace App\Ordering\Domain\Repository;

use App\Identity\Domain\Entity\Company;
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

    /**
     * A company's past orders ("mes commandes"), most recent first — drafts
     * excluded, a draft is just the current cart, not an order yet.
     *
     * @return Order[]
     */
    public function findSubmittedForCompany(Company $company): array;

    /**
     * Every order needing staff attention (anything past draft), oldest
     * first so the queue is processed in order.
     *
     * @return Order[]
     */
    public function findAllSubmitted(): array;

    public function add(Order $order): void;
}
