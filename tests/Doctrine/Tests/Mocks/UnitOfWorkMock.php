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
    private $_mockDataChangeSets = [];

    /**
     * @var array|null
     */
    private $_persisterMock;

    /**
     * {@inheritdoc}
     */
    public function getEntityPersister($entityName)
    {
        return isset($this->_persisterMock[$entityName])
            ? $this->_persisterMock[$entityName]
            : parent::getEntityPersister($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function & getEntityChangeSet($entity)
    {
        $oid = spl_object_hash($entity);

        if (isset($this->_mockDataChangeSets[$oid])) {
            return $this->_mockDataChangeSets[$oid];
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
        $this->_persisterMock[$entityName] = $persister;
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalEntityData($entity, array $originalData)
    {
        $this->_originalEntityData[spl_object_hash($entity)] = $originalData;
    }
}
