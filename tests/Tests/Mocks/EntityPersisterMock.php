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
    private array $inserts                  = [];
    private array $updates                  = [];
    private array $deletes                  = [];
    private int $identityColumnValueCounter = 0;
    private int|null $mockIdGeneratorType   = null;

    /** @phpstan-var list<array{generatedId: int, entity: object}> */
    private array $postInsertIds = [];

    private bool $existsCalled = false;

    public function addInsert(object $entity): void
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

    public function executeInserts(): void
    {
        foreach ($this->postInsertIds as $item) {
            $this->em->getUnitOfWork()->assignPostInsertId($item['entity'], $item['generatedId']);
        }
    }

    public function setMockIdGeneratorType(int $genType): void
    {
        $this->mockIdGeneratorType = $genType;
    }

    public function update(object $entity): void
    {
        $this->updates[] = $entity;
    }

    public function exists(object $entity, Criteria|null $extraConditions = null): bool
    {
        $this->existsCalled = true;

        return false;
    }

    public function delete(object $entity): bool
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
