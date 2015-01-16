<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for UnitOfWork.
 */
class UnitOfWorkMock extends \Doctrine\ORM\UnitOfWork
{
    /**
     * @var array
     */
    private $_mockDataChangeSets = array();

    /**
     * {@inheritdoc}
     */
    public function getEntityChangeSet($entity)
    {
        $oid = spl_object_hash($entity);

        return isset($this->_mockDataChangeSets[$oid])
            ? $this->_mockDataChangeSets[$oid]
            : parent::getEntityChangeSet($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalEntityData($entity, array $originalData)
    {
        $this->_originalEntityData[spl_object_hash($entity)] = $originalData;
    }
}
