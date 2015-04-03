<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Collections\Criteria;

/**
 * EntityPersister implementation used for mocking during tests.
 */
class EntityPersisterMock extends \Doctrine\ORM\Persisters\Entity\BasicEntityPersister
{
    /**
     * @var array
     */
    private $inserts = array();

    /**
     * @var array
     */
    private $updates = array();

    /**
     * @var array
     */
    private $deletes = array();

    /**
     * @var int
     */
    private $identityColumnValueCounter = 0;

    /**
     * @var int|null
     */
    private $mockIdGeneratorType;

    /**
     * @var array
     */
    private $postInsertIds = array();

    /**
     * @var bool
     */
    private $existsCalled = false;

    /**
     * @param object $entity
     *
     * @return mixed
     */
    public function addInsert($entity)
    {
        $this->inserts[] = $entity;
        if ( ! is_null($this->mockIdGeneratorType) && $this->mockIdGeneratorType == \Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY
                || $this->class->isIdGeneratorIdentity()) {
            $id = $this->identityColumnValueCounter++;
            $this->postInsertIds[] = array(
                'generatedId' => $id,
                'entity' => $entity,
            );
            return $id;
        }
        return null;
    }

    /**
     * @return array
     */
    public function executeInserts()
    {
        return $this->postInsertIds;
    }

    /**
     * @param int $genType
     *
     * @return void
     */
    public function setMockIdGeneratorType($genType)
    {
        $this->mockIdGeneratorType = $genType;
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        $this->updates[] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($entity, Criteria $extraConditions = null)
    {
        $this->existsCalled = true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        $this->deletes[] = $entity;
    }

    /**
     * @return array
     */
    public function getInserts()
    {
        return $this->inserts;
    }

    /**
     * @return array
     */
    public function getUpdates()
    {
        return $this->updates;
    }

    /**
     * @return array
     */
    public function getDeletes()
    {
        return $this->deletes;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->existsCalled = false;
        $this->identityColumnValueCounter = 0;
        $this->inserts = array();
        $this->updates = array();
        $this->deletes = array();
    }

    /**
     * @return bool
     */
    public function isExistsCalled()
    {
        return $this->existsCalled;
    }
}
