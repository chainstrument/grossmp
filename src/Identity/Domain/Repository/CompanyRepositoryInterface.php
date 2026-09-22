<?php

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\Company;

/**
 * Domain-facing contract for Company persistence — see
 * App\Identity\Infrastructure\Persistence\Doctrine\DoctrineCompanyRepository
 * for the concrete implementation, wired in config/services.yaml.
 */
interface CompanyRepositoryInterface
{
    public function findById(int $id): ?Company;

    /**
     * @return Company[]
     */
    public function findAll(): array;

    public function add(Company $company): void;
}
