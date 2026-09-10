<?php

namespace App\Ordering\Domain\Entity;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\ValueObject\Money;
use App\Entity\Company;
use App\Entity\User;
use App\Ordering\Domain\Enum\OrderStatus;
use App\Ordering\Domain\Exception\CartNotEditableException;
use App\Ordering\Infrastructure\Persistence\Doctrine\DoctrineOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Aggregate root of the Ordering bounded context.
 *
 * There is no separate "Cart" concept: a cart *is* an Order in DRAFT status.
 * It becomes an immutable order once it moves past DRAFT (EPIC 5's workflow).
 */
#[ORM\Entity(repositoryClass: DoctrineOrderRepository::class)]
#[ORM\Table(name: 'customer_order')] // "order" is a reserved word on most SQL engines
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy;

    #[ORM\Column(length: 20, enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, OrderLine>
     */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(Company $company, ?User $createdBy = null)
    {
        $this->company = $company;
        $this->createdBy = $createdBy;
        $this->status = OrderStatus::DRAFT;
        $this->createdAt = new \DateTimeImmutable();
        $this->lines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function isDraft(): bool
    {
        return OrderStatus::DRAFT === $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, OrderLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function findLineForProduct(Product $product): ?OrderLine
    {
        foreach ($this->lines as $line) {
            if ($this->isSameProduct($line->getProduct(), $product)) {
                return $line;
            }
        }

        return null;
    }

    /**
     * Entity equality by identity: same object, or same persisted id. Two
     * *unpersisted* products (id still null) are never considered the same,
     * even if both ids happen to be null.
     */
    private function isSameProduct(Product $a, Product $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return null !== $a->getId() && $a->getId() === $b->getId();
    }

    public function addLine(OrderLine $line): void
    {
        $this->assertEditable();

        if ($this->lines->contains($line)) {
            return;
        }

        $this->lines->add($line);
        $line->attachTo($this);
    }

    public function removeLine(OrderLine $line): void
    {
        $this->assertEditable();

        $this->lines->removeElement($line);
    }

    /**
     * Sum of all line totals. Assumes every line shares the product base
     * currency (EUR for now — see Money's currency guard for the day this
     * project needs to support several).
     */
    public function total(): Money
    {
        $total = Money::zero();
        foreach ($this->lines as $line) {
            $total = $total->plus($line->lineTotal());
        }

        return $total;
    }

    private function assertEditable(): void
    {
        if (!$this->isDraft()) {
            throw CartNotEditableException::forOrder($this);
        }
    }
}
