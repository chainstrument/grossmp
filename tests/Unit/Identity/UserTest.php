<?php

namespace App\Tests\Unit\Identity;

use App\Identity\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Pure domain test: no Symfony kernel, no database.
 */
class UserTest extends TestCase
{
    public function testSetRolesRejectsAnUnknownRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new User())->setRoles(['ROLE_NOT_A_REAL_ROLE']);
    }

    public function testSetRolesAcceptsKnownRoles(): void
    {
        $user = (new User())->setRoles([User::ROLE_BUYER]);

        self::assertContains(User::ROLE_BUYER, $user->getRoles());
    }

    public function testSetEmailRejectsAnInvalidAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new User())->setEmail('not-an-email');
    }

    public function testSetEmailAcceptsAValidAddress(): void
    {
        $user = (new User())->setEmail('buyer@grossmp.local');

        self::assertSame('buyer@grossmp.local', $user->getEmail());
    }

    public function testEveryUserImplicitlyHasRoleUser(): void
    {
        $user = new User();

        self::assertContains('ROLE_USER', $user->getRoles());
    }
}
