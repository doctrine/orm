<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

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

    /** @psalm-var list<array{generatedId: int, entity: object}> */
    private $postInsertIds = [];

    /** @var bool */
    private $existsCalled = false;

    public function addInsert($entity): void
    {
        $this->inserts[] = $entity;
        if ($this->mockIdGeneratorType !== ClassMetadata::GENERATOR_TYPE_IDENTITY && ! $this->class->isIdGeneratorIdentity()) {
            return;
        }

        $id                    = $this->identityColumnValueCounter++;
        $this->postInsertIds[] = [
            'generatedId' => $id,
            'entity' => $entity,
        ];
    }

    /** @psalm-return list<array{generatedId: int, entity: object}> */
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
    public function update($entity): void
    {
        $this->updates[] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($entity, ?Criteria $extraConditions = null): bool
    {
        $this->existsCalled = true;

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity): bool
    {
        $this->deletes[] = $entity;

        return true;
    }

    public function getInserts(): array
    {
        return $this->inserts;
    }

    public function getUpdates(): array
    {
        return $this->updates;
    }

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
