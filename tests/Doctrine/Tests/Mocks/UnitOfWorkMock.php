<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\UnitOfWork;

/**
 * Mock class for UnitOfWork.
 */
class UnitOfWorkMock extends UnitOfWork
{
    /**
     * @var array
     */
    private $mockDataChangeSets = [];

    /**
     * @var array|null
     */
    private $persisterMock;

    /**
     * {@inheritdoc}
     */
    public function getEntityPersister($entityName)
    {
        return isset($this->persisterMock[$entityName])
            ? $this->persisterMock[$entityName]
            : parent::getEntityPersister($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function & getEntityChangeSet($entity)
    {
        $oid = spl_object_hash($entity);

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
     *
     * @param string                                               $entityName
     * @param \Doctrine\ORM\Persisters\Entity\BasicEntityPersister $persister
     *
     * @return void
     */
    public function setEntityPersister($entityName, $persister)
    {
        $this->persisterMock[$entityName] = $persister;
    }
}
