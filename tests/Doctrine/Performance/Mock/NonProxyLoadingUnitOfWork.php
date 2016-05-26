<?php

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\ORM\Performance\PersisterMock;

/**
 * An unit of work mock that prevents lazy-loading of proxies
 */
class NonProxyLoadingUnitOfWork extends UnitOfWork
{
    /**
     * @var PersisterMock
     */
    private $entityPersister;

    public function __construct()
    {
        $this->entityPersister = new NonLoadingPersister();
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityPersister($entityName)
    {
        return $this->entityPersister;
    }
}
