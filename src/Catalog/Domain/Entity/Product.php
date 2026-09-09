<?php

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Aggregate root of the Catalog bounded context.
 *
 * Owns its PriceTier entities: they only make sense attached to a Product and
 * are always modified through this class, never persisted/removed on their own.
 */
#[ORM\Entity(repositoryClass: DoctrineProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    private string $reference;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: ProductCategory::class)]
    private ProductCategory $category;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'base_price_')]
    private Money $basePrice;

    #[ORM\Column]
    private int $stockQuantity = 0;

    /**
     * @var Collection<int, PriceTier>
     */
    #[ORM\OneToMany(targetEntity: PriceTier::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    private Collection $priceTiers;

    public function __construct(Sku $reference, string $name, ProductCategory $category, Money $basePrice)
    {
        $this->reference = $reference->value();
        $this->rename($name);
        $this->category = $category;
        $this->basePrice = $basePrice;
        $this->priceTiers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): Sku
    {
        return new Sku($this->reference);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('A product name cannot be empty.');
        }

        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCategory(): ProductCategory
    {
        return $this->category;
    }

    public function changeCategory(ProductCategory $category): void
    {
        $this->category = $category;
    }

    public function getBasePrice(): Money
    {
        return $this->basePrice;
    }

    public function changeBasePrice(Money $basePrice): void
    {
        $this->basePrice = $basePrice;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function isInStock(int $quantity = 1): bool
    {
        return $this->stockQuantity >= $quantity;
    }

    /**
     * Adjusts the stock level. Use a negative delta to consume stock (e.g. on
     * order preparation), a positive one to restock.
     */
    public function adjustStock(int $delta): void
    {
        $newQuantity = $this->stockQuantity + $delta;
        if ($newQuantity < 0) {
            throw new \DomainException(sprintf('Cannot adjust stock of "%s" by %d: only %d unit(s) available.', $this->reference, $delta, $this->stockQuantity));
        }

        $this->stockQuantity = $newQuantity;
    }

    /**
     * @return Collection<int, PriceTier>
     */
    public function getPriceTiers(): Collection
    {
        return $this->priceTiers;
    }

    public function addPriceTier(PriceTier $priceTier): void
    {
        if ($this->priceTiers->contains($priceTier)) {
            return;
        }

        $this->priceTiers->add($priceTier);
        $priceTier->attachTo($this);
    }

    public function removePriceTier(PriceTier $priceTier): void
    {
        $this->priceTiers->removeElement($priceTier);
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s', $this->reference, $this->name);
    }
}
