<?php

namespace App\Ordering\Domain\Validator;

use App\Ordering\Domain\Entity\OrderLine;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SufficientStockValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SufficientStock) {
            throw new UnexpectedTypeException($constraint, SufficientStock::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof OrderLine) {
            throw new UnexpectedValueException($value, OrderLine::class);
        }

        $product = $value->getProduct();

        if ($product->isInStock($value->getQuantity())) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ product }}', $product->getName())
            ->setParameter('{{ available }}', (string) $product->getStockQuantity())
            ->setParameter('{{ requested }}', (string) $value->getQuantity())
            ->atPath('quantity')
            ->addViolation()
        ;
    }
}
