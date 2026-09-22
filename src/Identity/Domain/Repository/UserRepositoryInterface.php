<?php

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\User;

/**
 * Domain-facing contract for User persistence — see
 * App\Identity\Infrastructure\Persistence\Doctrine\DoctrineUserRepository
 * for the concrete implementation, wired in config/services.yaml.
 */
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function add(User $user): void;
}
