<?php

namespace App\Ordering\Application;

use App\Identity\Domain\Entity\Company;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Entity\OrderStatusHistory;
use App\Ordering\Domain\Repository\OrderRepositoryInterface;
use App\Ordering\Domain\Repository\OrderStatusHistoryRepositoryInterface;

/**
 * Application service for use case #28: order tracking (list + detail +
 * history). Thin orchestration only — no business rules of its own, it just
 * exposes the read operations App\Ordering\UI\Controller\OrderController
 * needs, so the UI never depends on a Domain repository interface directly.
 */
final class OrderFinder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderStatusHistoryRepositoryInterface $history,
    ) {
    }

    public function findById(int $id): ?Order
    {
        return $this->orders->findById($id);
    }

    /**
     * @return Order[]
     */
    public function findSubmittedForCompany(Company $company): array
    {
        return $this->orders->findSubmittedForCompany($company);
    }

    /**
     * @return Order[]
     */
    public function findAllSubmitted(): array
    {
        return $this->orders->findAllSubmitted();
    }

    /**
     * @return OrderStatusHistory[] oldest first
     */
    public function findHistory(Order $order): array
    {
        return $this->history->findByOrder($order);
    }
}
