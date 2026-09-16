<?php

namespace App\Ordering\Domain\Repository;

use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Entity\OrderStatusHistory;

interface OrderStatusHistoryRepositoryInterface
{
    public function add(OrderStatusHistory $entry): void;

    /**
     * @return OrderStatusHistory[] oldest first
     */
    public function findByOrder(Order $order): array;
}
