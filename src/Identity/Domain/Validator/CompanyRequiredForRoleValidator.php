<?php

namespace App\Identity\Domain\Validator;

use App\Identity\Domain\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CompanyRequiredForRoleValidator extends ConstraintValidator
{
    private const ROLES_REQUIRING_COMPANY = [User::ROLE_BUYER, User::ROLE_COMPANY_ADMIN];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CompanyRequiredForRole) {
            throw new UnexpectedTypeException($constraint, CompanyRequiredForRole::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof User) {
            throw new UnexpectedValueException($value, User::class);
        }

        if (null !== $value->getCompany()) {
            return;
        }

        $offendingRole = array_intersect($value->getRoles(), self::ROLES_REQUIRING_COMPANY);
        if ([] === $offendingRole) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ role }}', (string) reset($offendingRole))
            ->atPath('company')
            ->addViolation()
        ;
    }
}
