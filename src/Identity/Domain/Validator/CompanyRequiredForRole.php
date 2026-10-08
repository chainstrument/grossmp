<?php

namespace App\Identity\Domain\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Class-level constraint on User: ROLE_BUYER and ROLE_COMPANY_ADMIN only make
 * sense for a user acting on behalf of a client Company — staff (ROLE_VALIDATOR)
 * has no such requirement.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class CompanyRequiredForRole extends Constraint
{
    public string $message = 'Un utilisateur avec le rôle "{{ role }}" doit être rattaché à une société.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
