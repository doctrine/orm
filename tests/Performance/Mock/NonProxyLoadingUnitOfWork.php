<?php

declare(strict_types=1);

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\UnitOfWork;

/**
 * An unit of work mock that prevents lazy-loading of proxies
 */
class NonProxyLoadingUnitOfWork extends UnitOfWork
{
    private NonLoadingPersister $entityPersister;

    public function __construct()
    {
        $this->entityPersister = new NonLoadingPersister();
    }

    public function getEntityPersister(string $entityName): NonLoadingPersister
    {
        return $this->entityPersister;
    }
}
