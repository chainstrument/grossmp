<?php

namespace App\Ordering\Application;

use App\Catalog\Domain\Entity\Product;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shape-level validation only (a product was picked, quantity is a positive
 * number) — the business rule "is there enough stock" lives on OrderLine via
 * the SufficientStock constraint, checked once the line actually exists.
 *
 * Holds the resolved Product entity (not just its id) because it is built
 * from an EntityType field — see App\Ordering\UI\Form\AddToCartType.
 */
class AddToCartRequest
{
    #[Assert\NotNull(message: 'Choisissez un produit.')]
    public ?Product $product = null;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 1, message: 'La quantité minimum est 1.')]
    public ?int $quantity = 1;
}
