<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

/**
 * EntityPersister implementation used for mocking during tests.
 */
class EntityPersisterMock extends BasicEntityPersister
{
    /**
     * @var array
     */
    private $inserts = [];

    /**
     * @var array
     */
    private $updates = [];

    /**
     * @var array
     */
    private $deletes = [];

    /**
     * @var int
     */
    private $identityColumnValueCounter = 0;

    /**
     * @var string|null
     */
    private $mockIdGeneratorType;

    /**
     * @var bool
     */
    private $existsCalled = false;

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
    public function insert($entity)
    {
        $this->inserts[] = $entity;

        if ($this->mockIdGeneratorType === GeneratorType::IDENTITY
            || $this->class->getProperty($this->class->getSingleIdentifierFieldName())->getIdentifierGeneratorType() === GeneratorType::IDENTITY) {
            $id = $this->identityColumnValueCounter++;

            return [
                $this->class->getSingleIdentifierFieldName() => $id,
            ];
        }

        return [];
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
        $this->inserts = [];
        $this->updates = [];
        $this->deletes = [];
    }

    /**
     * @return bool
     */
    public function isExistsCalled()
    {
        return $this->existsCalled;
    }
}
