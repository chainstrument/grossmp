<?php

namespace App\Identity\Application;

use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\Repository\CompanyRepositoryInterface;

/**
 * Application service for looking up companies — thin orchestration only, so
 * UI controllers outside the Identity context (e.g. the Catalog back-office
 * page) never depend on CompanyRepositoryInterface directly.
 */
final class CompanyFinder
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
    ) {
    }

    public function findById(int $id): ?Company
    {
        return $this->companies->findById($id);
    }

    /**
     * @return Company[]
     */
    public function findAll(): array
    {
        return $this->companies->findAll();
    }
}
