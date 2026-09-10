<?php

namespace App\Ordering\Application;

use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\Service\PriceCalculatorInterface;
use App\Entity\Company;
use App\Entity\User;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Entity\OrderLine;
use App\Ordering\Domain\Exception\InsufficientStockException;
use App\Ordering\Domain\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Application service for use case #20: manage the current draft cart
 * (add/update/remove lines, keep the total consistent).
 *
 * One call = one transactional unit of work: each public method flushes on
 * success so callers (controllers) don't have to know about the EntityManager.
 */
final class CartManager
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ProductRepositoryInterface $products,
        private readonly PriceCalculatorInterface $priceCalculator,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getOrCreateDraftCart(Company $company, ?User $createdBy = null): Order
    {
        $cart = $this->orders->findDraftForCompany($company);
        if (null !== $cart) {
            return $cart;
        }

        $cart = new Order($company, $createdBy);
        $this->orders->add($cart);
        $this->entityManager->flush();

        return $cart;
    }

    /**
     * Adds $quantity units of a product to the cart. If the product is
     * already in the cart, the quantities are merged and the unit price is
     * re-resolved for the new total quantity (a bigger order can unlock a
     * better price tier).
     *
     * @throws ProductNotFoundException
     * @throws InsufficientStockException
     */
    public function addProduct(Order $cart, int $productId, int $quantity): OrderLine
    {
        $product = $this->products->findById($productId);
        if (null === $product) {
            throw ProductNotFoundException::withId($productId);
        }

        $existingLine = $cart->findLineForProduct($product);
        $newQuantity = $quantity + ($existingLine?->getQuantity() ?? 0);
        $unitPrice = $this->priceCalculator->effectiveUnitPrice($product, $newQuantity, $cart->getCompany());

        // Validate against a throwaway line first: a rejected change must
        // never leave the cart's in-memory state half-mutated (e.g. a new
        // line added to the aggregate, or an existing one quantity-bumped,
        // right before the exception is thrown and the caller re-renders it).
        $this->assertSufficientStock(new OrderLine($product, $newQuantity, $unitPrice));

        if (null !== $existingLine) {
            $existingLine->changeQuantity($newQuantity);
            $existingLine->changeUnitPrice($unitPrice);
            $line = $existingLine;
        } else {
            $line = new OrderLine($product, $newQuantity, $unitPrice);
            $cart->addLine($line);
        }

        $this->entityManager->flush();

        return $line;
    }

    /**
     * @throws InsufficientStockException
     */
    public function changeLineQuantity(Order $cart, int $lineId, int $quantity): void
    {
        $line = $this->findLineOrFail($cart, $lineId);

        $unitPrice = $this->priceCalculator->effectiveUnitPrice($line->getProduct(), $quantity, $cart->getCompany());

        // Same "validate a throwaway line first" precaution as addProduct().
        $this->assertSufficientStock(new OrderLine($line->getProduct(), $quantity, $unitPrice));

        $line->changeQuantity($quantity);
        $line->changeUnitPrice($unitPrice);

        $this->entityManager->flush();
    }

    public function removeLine(Order $cart, int $lineId): void
    {
        $cart->removeLine($this->findLineOrFail($cart, $lineId));
        $this->entityManager->flush();
    }

    private function findLineOrFail(Order $cart, int $lineId): OrderLine
    {
        foreach ($cart->getLines() as $line) {
            if ($line->getId() === $lineId) {
                return $line;
            }
        }

        throw new \InvalidArgumentException(sprintf('Line #%d does not belong to this cart.', $lineId));
    }

    private function assertSufficientStock(OrderLine $line): void
    {
        $violations = $this->validator->validate($line);
        if (count($violations) > 0) {
            throw InsufficientStockException::forLine($line, (string) $violations->get(0)->getMessage());
        }
    }
}
