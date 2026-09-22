<?php

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\Money;
use App\Identity\Domain\Entity\Company;
use Doctrine\ORM\Mapping as ORM;

/**
 * A degressive price break: from `minQuantity` units onward, the unit price
 * for a product becomes `unitPrice`. When `company` is set, the tier only
 * applies to that client; otherwise it applies to everyone.
 *
 * Owned by Product (its aggregate root) — always created/removed through
 * Product::addPriceTier()/removePriceTier(), never persisted on its own.
 */
#[ORM\Entity]
class PriceTier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'priceTiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column]
    private int $minQuantity;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'unit_price_')]
    private Money $unitPrice;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Company $company;

    public function __construct(int $minQuantity, Money $unitPrice, ?Company $company = null)
    {
        if ($minQuantity < 1) {
            throw new \InvalidArgumentException('A price tier must apply from at least 1 unit.');
        }

        $this->minQuantity = $minQuantity;
        $this->unitPrice = $unitPrice;
        $this->company = $company;
    }

    /**
     * @internal called by Product::addPriceTier() to keep both sides of the
     * relation in sync — do not call directly
     */
    public function attachTo(Product $product): void
    {
        $this->product = $product;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getMinQuantity(): int
    {
        return $this->minQuantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * True if this tier applies for the given quantity and (optional) company.
     * A tier without a company applies to anyone; one with a company only
     * applies to that exact company.
     */
    public function appliesTo(int $quantity, ?Company $company): bool
    {
        if ($quantity < $this->minQuantity) {
            return false;
        }

        if (null === $this->company) {
            return true;
        }

        return null !== $company && $this->isSameCompany($this->company, $company);
    }

    /**
     * Entity equality by identity: same object, or same persisted id. Two
     * *unpersisted* companies (id still null) are never considered the same,
     * even if both ids happen to be null.
     */
    private function isSameCompany(Company $a, Company $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return null !== $a->getId() && $a->getId() === $b->getId();
    }
}
