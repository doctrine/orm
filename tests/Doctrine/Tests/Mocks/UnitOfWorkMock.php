<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use function spl_object_id;

/**
 * Mock class for UnitOfWork.
 */
class UnitOfWorkMock extends UnitOfWork
{
    /** @var array */
    private $mockDataChangeSets = [];

    /** @var array|null */
    private $persisterMock;

    /**
     * {@inheritdoc}
     */
    public function getEntityPersister(string $entityName) : EntityPersister
    {
        return $this->persisterMock[$entityName]
            ?? parent::getEntityPersister($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function & getEntityChangeSet(object $entity) : array
    {
        $oid = spl_object_id($entity);

        if (isset($this->mockDataChangeSets[$oid])) {
            return $this->mockDataChangeSets[$oid];
        }

        $data = parent::getEntityChangeSet($entity);

        return $data;
    }

    /* MOCK API */

    /**
     * Sets a (mock) persister for an entity class that will be returned when
     * getEntityPersister() is invoked for that class.
     */
    public function setEntityPersister(string $entityName, BasicEntityPersister $persister) : void
    {
        $this->persisterMock[$entityName] = $persister;
    }
}
