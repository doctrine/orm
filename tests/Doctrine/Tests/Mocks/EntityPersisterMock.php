<?php

namespace Doctrine\Tests\Mocks;

/**
 * EntityPersister implementation used for mocking during tests.
 */
class EntityPersisterMock extends \Doctrine\ORM\Persisters\BasicEntityPersister
{
    private $inserts = array();
    private $updates = array();
    private $deletes = array();
    private $identityColumnValueCounter = 0;
    private $mockIdGeneratorType;
    private $postInsertIds = array();
    private $existsCalled = false;

    /**
     * @param <type> $entity
     * @return <type>
     * @override
     */
    public function insert($entity)
    {
        $this->inserts[] = $entity;
        if ( ! is_null($this->mockIdGeneratorType) && $this->mockIdGeneratorType == \Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY
                || $this->class->isIdGeneratorIdentity()) {
            $id = $this->identityColumnValueCounter++;
            $this->postInsertIds[$id] = $entity;
            return $id;
        }
        return null;
    }

    public function addInsert($entity)
    {
        $this->inserts[] = $entity;
        if ( ! is_null($this->mockIdGeneratorType) && $this->mockIdGeneratorType == \Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY
                || $this->class->isIdGeneratorIdentity()) {
            $id = $this->identityColumnValueCounter++;
            $this->postInsertIds[$id] = $entity;
            return $id;
        }
        return null;
    }

    public function executeInserts()
    {
        return $this->postInsertIds;
    }

    public function setMockIdGeneratorType($genType)
    {
        $this->mockIdGeneratorType = $genType;
    }

    public function update($entity)
    {
        $this->updates[] = $entity;
    }

    public function exists($entity, array $extraConditions = array())
    {
        $this->existsCalled = true;
    }

    public function delete($entity)
    {
        $this->deletes[] = $entity;
    }

    public function getInserts()
    {
        return $this->inserts;
    }

    public function getUpdates()
    {
        return $this->updates;
    }

    public function getDeletes()
    {
        return $this->deletes;
    }

    public function reset()
    {
        $this->existsCalled = false;
        $this->identityColumnValueCounter = 0;
        $this->inserts = array();
        $this->updates = array();
        $this->deletes = array();
    }

    public function isExistsCalled()
    {
        return $this->existsCalled;
    }
}