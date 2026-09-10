<?php

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\Entity\Order;

final class CartNotEditableException extends \RuntimeException
{
    public static function forOrder(Order $order): self
    {
        return new self(sprintf(
            'Order #%s cannot be modified: it is no longer in draft status (status: %s).',
            $order->getId() ?? '(new)',
            $order->getStatus()->value
        ));
    }
}
