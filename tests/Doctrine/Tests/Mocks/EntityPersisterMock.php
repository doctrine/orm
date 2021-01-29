<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

use function is_null;

/**
 * EntityPersister implementation used for mocking during tests.
 */
class EntityPersisterMock extends BasicEntityPersister
{
    /** @var array */
    private $inserts = [];

    /** @var array */
    private $updates = [];

    /** @var array */
    private $deletes = [];

    /** @var int */
    private $identityColumnValueCounter = 0;

    /** @var int|null */
    private $mockIdGeneratorType;

    /** @var array */
    private $postInsertIds = [];

    /** @var bool */
    private $existsCalled = false;

    /**
     * @return mixed
     */
    public function addInsert($entity)
    {
        $this->inserts[] = $entity;
        if (
            ! is_null($this->mockIdGeneratorType) && $this->mockIdGeneratorType === ClassMetadata::GENERATOR_TYPE_IDENTITY
                || $this->class->isIdGeneratorIdentity()
        ) {
            $id                    = $this->identityColumnValueCounter++;
            $this->postInsertIds[] = [
                'generatedId' => $id,
                'entity' => $entity,
            ];

            return $id;
        }

        return null;
    }

    /**
     * @return array
     */
    public function executeInserts(): array
    {
        return $this->postInsertIds;
    }

    public function setMockIdGeneratorType(int $genType): void
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
    public function exists($entity, ?Criteria $extraConditions = null)
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
    public function getInserts(): array
    {
        return $this->inserts;
    }

    /**
     * @return array
     */
    public function getUpdates(): array
    {
        return $this->updates;
    }

    /**
     * @return array
     */
    public function getDeletes(): array
    {
        return $this->deletes;
    }

    public function reset(): void
    {
        $this->existsCalled               = false;
        $this->identityColumnValueCounter = 0;
        $this->inserts                    = [];
        $this->updates                    = [];
        $this->deletes                    = [];
    }

    public function isExistsCalled(): bool
    {
        return $this->existsCalled;
    }
}
