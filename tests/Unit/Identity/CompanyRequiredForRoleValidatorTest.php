<?php

namespace App\Tests\Unit\Identity;

use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Validator\CompanyRequiredForRole;
use App\Identity\Domain\Validator\CompanyRequiredForRoleValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Symfony's ConstraintValidatorTestCase runs the validator in isolation
 * (mocked ExecutionContext) — no kernel, no database.
 */
class CompanyRequiredForRoleValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): CompanyRequiredForRoleValidator
    {
        return new CompanyRequiredForRoleValidator();
    }

    public function testNoViolationForAStaffUserWithoutCompany(): void
    {
        $user = (new User())->setRoles([User::ROLE_VALIDATOR]);

        $this->validator->validate($user, new CompanyRequiredForRole());

        $this->assertNoViolation();
    }

    public function testNoViolationForABuyerWithCompany(): void
    {
        $user = (new User())->setRoles([User::ROLE_BUYER])->setCompany(new Company());

        $this->validator->validate($user, new CompanyRequiredForRole());

        $this->assertNoViolation();
    }

    public function testViolationForABuyerWithoutCompany(): void
    {
        $user = (new User())->setRoles([User::ROLE_BUYER]);

        $constraint = new CompanyRequiredForRole();
        $this->validator->validate($user, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ role }}', User::ROLE_BUYER)
            ->atPath('property.path.company')
            ->assertRaised()
        ;
    }

    public function testViolationForACompanyAdminWithoutCompany(): void
    {
        $user = (new User())->setRoles([User::ROLE_COMPANY_ADMIN]);

        $constraint = new CompanyRequiredForRole();
        $this->validator->validate($user, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ role }}', User::ROLE_COMPANY_ADMIN)
            ->atPath('property.path.company')
            ->assertRaised()
        ;
    }
}
