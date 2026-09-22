<?php

namespace App\Ordering\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Ordering\Domain\Enum\OrderStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * An audit trail entry for one Order transition. Written exclusively by
 * App\Ordering\Infrastructure\Workflow\OrderWorkflowSubscriber as a side
 * effect of the "order" workflow completing a transition — never created
 * directly by application code.
 */
#[ORM\Entity]
class OrderStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(length: 20, enumType: OrderStatus::class)]
    private OrderStatus $fromStatus;

    #[ORM\Column(length: 20, enumType: OrderStatus::class)]
    private OrderStatus $toStatus;

    #[ORM\Column(length: 40)]
    private string $transition;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $performedBy;

    #[ORM\Column]
    private \DateTimeImmutable $performedAt;

    public function __construct(
        Order $order,
        OrderStatus $fromStatus,
        OrderStatus $toStatus,
        string $transition,
        ?User $performedBy,
    ) {
        $this->order = $order;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->transition = $transition;
        $this->performedBy = $performedBy;
        $this->performedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getFromStatus(): OrderStatus
    {
        return $this->fromStatus;
    }

    public function getToStatus(): OrderStatus
    {
        return $this->toStatus;
    }

    public function getTransition(): string
    {
        return $this->transition;
    }

    public function getPerformedBy(): ?User
    {
        return $this->performedBy;
    }

    public function getPerformedAt(): \DateTimeImmutable
    {
        return $this->performedAt;
    }
}
