<?php

declare(strict_types=1);

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

/**
 * An unit of work mock that prevents lazy-loading of proxies
 */
class NonProxyLoadingUnitOfWork extends UnitOfWork
{
    /** @var array<class-string, NonLoadingPersister> */
    private array $entityPersisters = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getEntityPersister(string $entityName): NonLoadingPersister
    {
        return $this->entityPersisters[$entityName]
            ??= new NonLoadingPersister($this->entityManager->getClassMetadata($entityName));
    }
}
