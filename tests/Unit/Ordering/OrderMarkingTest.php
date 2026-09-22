<?php

namespace App\Tests\Unit\Ordering;

use App\Identity\Domain\Entity\Company;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * Pure domain test: no Symfony kernel, no workflow service. Just the
 * getMarking()/setMarking() bridge the "method" marking store relies on
 * (config/packages/workflow.yaml) to work with a typed OrderStatus enum.
 */
class OrderMarkingTest extends TestCase
{
    public function testMarkingReflectsTheCurrentStatus(): void
    {
        $order = new Order(new Company());

        self::assertSame(OrderStatus::DRAFT, $order->getMarking());
    }

    public function testSettingTheMarkingUpdatesTheStatus(): void
    {
        $order = new Order(new Company());

        $order->setMarking(OrderStatus::SUBMITTED);

        self::assertSame(OrderStatus::SUBMITTED, $order->getStatus());
        self::assertSame(OrderStatus::SUBMITTED, $order->getMarking());
    }
}
