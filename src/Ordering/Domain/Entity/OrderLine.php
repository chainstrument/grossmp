<?php

namespace App\Ordering\Domain\Entity;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\ValueObject\Money;
use App\Ordering\Domain\Validator\SufficientStock;
use Doctrine\ORM\Mapping as ORM;

/**
 * Owned by Order (its aggregate root) — always created/removed through
 * Order::addLine()/removeLine(), never persisted on its own.
 *
 * unitPrice is a *snapshot* taken when the line is added (or its quantity
 * changed): the negotiated price at that moment, not a live recomputation.
 * This is deliberate — a cart shouldn't silently reprice itself if a tariff
 * changes while the buyer is still shopping.
 */
#[ORM\Entity]
#[SufficientStock]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column]
    private int $quantity;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'unit_price_')]
    private Money $unitPrice;

    public function __construct(Product $product, int $quantity, Money $unitPrice)
    {
        $this->product = $product;
        $this->unitPrice = $unitPrice;
        $this->changeQuantity($quantity);
    }

    /**
     * @internal called by Order::addLine() to keep both sides of the relation
     * in sync — do not call directly
     */
    public function attachTo(Order $order): void
    {
        $this->order = $order;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function changeQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Order line quantity must be at least 1.');
        }

        $this->quantity = $quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function changeUnitPrice(Money $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice->multipliedBy($this->quantity);
    }
}
