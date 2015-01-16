<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for UnitOfWork.
 */
class PersisterFactoryMock extends \Doctrine\ORM\Persisters\PersisterFactory
{
    /**
     * @var array
     */
    private $_entityPersistersMock = array();

    /**
     * @var array
     */
    private $_collectionPersistersMock = array();

    /**
     * {@inheritdoc}
     */
    public function getOrCreateEntityPersister($entityName)
    {
        return isset($this->_entityPersistersMock[$entityName])
            ? $this->_entityPersistersMock[$entityName]
            : parent::getOrCreateEntityPersister($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrCreateCollectionPersister(array $association)
    {
        $role = isset($association['cache'])
            ? $association['sourceEntity'] . '::' . $association['fieldName']
            : $association['type'];

        return isset($this->_collectionPersistersMock[$role])
            ? $this->_collectionPersistersMock[$role]
            : parent::getOrCreateCollectionPersister($association);
    }

    /* MOCK API */

    /**
     * Sets a (mock) persister for an entity class that will be returned when
     * getOrCreateEntityPersister() is invoked for that class.
     *
     * @param string                                          $entityName
     * @param \Doctrine\ORM\Persisters\Entity\EntityPersister $persister
     *
     * @return void
     */
    public function setEntityPersister($entityName, $persister)
    {
        $this->_entityPersistersMock[$entityName] = $persister;
    }

    /**
     * Sets a (mock) persister for a collection that will be returned when
     * getOrCreateCollectionPersister() is invoked for that class.
     *
     * @param array                                                   $association
     * @param \Doctrine\ORM\Persisters\Collection\CollectionPersister $persister
     *
     * @return void
     */
    public function setCollectionPersister($association, $persister)
    {
        $role = isset($association['cache'])
            ? $association['sourceEntity'] . '::' . $association['fieldName']
            : $association['type'];

        $this->_collectionPersistersMock[$role] = $persister;
    }
}
