<?php

namespace App\Identity\Application;

use App\Identity\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application service for the "edit my account" use case — the form binds
 * directly onto the User entity, so there's nothing left to orchestrate
 * beyond persisting the change. Same "one call = one transactional unit"
 * spirit as App\Ordering\Application\CartManager.
 */
final class AccountEditor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(User $user): void
    {
        $this->entityManager->flush();
    }
}
