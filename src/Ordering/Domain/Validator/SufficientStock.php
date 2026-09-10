<?php

namespace App\Ordering\Domain\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Class-level constraint on OrderLine: the requested quantity must not
 * exceed the product's current stock.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SufficientStock extends Constraint
{
    public string $message = 'Stock insuffisant pour "{{ product }}" : {{ available }} disponible(s), {{ requested }} demandé(s).';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
