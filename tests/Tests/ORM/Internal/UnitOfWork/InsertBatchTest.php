<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Internal\UnitOfWork;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Internal\UnitOfWork\InsertBatch;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(InsertBatch::class)]
final class InsertBatchTest extends TestCase
{
    private EntityManagerInterface&Stub $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $entityAMetadata = new ClassMetadata(EntityA::class);
        $entityBMetadata = new ClassMetadata(EntityB::class);
        $entityCMetadata = new ClassMetadata(EntityC::class);

        $entityAMetadata->idGenerator = new AssignedGenerator();
        $entityBMetadata->idGenerator = new AssignedGenerator();
        $entityCMetadata->idGenerator = new IdentityGenerator();

        $this->entityManager->method('getClassMetadata')
            ->willReturnMap([
                [EntityA::class, $entityAMetadata],
                [EntityB::class, $entityBMetadata],
                [EntityC::class, $entityCMetadata],
            ]);
    }

    public function testWillProduceEmptyBatchOnNoGivenEntities(): void
    {
        self::assertEmpty(InsertBatch::batchByEntityType($this->entityManager, []));
    }

    public function testWillBatchSameEntityOperationsInSingleBatch(): void
    {
        $batches = InsertBatch::batchByEntityType(
            $this->entityManager,
            [
                new EntityA(),
                new EntityA(),
                new EntityA(),
            ],
        );

        self::assertCount(1, $batches);
        self::assertCount(3, $batches[0]->entities);
    }

    public function testWillBatchInterleavedEntityOperationsInGroups(): void
    {
        $batches = InsertBatch::batchByEntityType(
            $this->entityManager,
            [
                new EntityA(),
                new EntityA(),
                new EntityB(),
                new EntityB(),
                new EntityA(),
                new EntityA(),
            ],
        );

        self::assertCount(3, $batches);
        self::assertCount(2, $batches[0]->entities);
        self::assertCount(2, $batches[1]->entities);
        self::assertCount(2, $batches[2]->entities);
    }

    public function testWillNotBatchOperationsForAGeneratedIdentifierEntity(): void
    {
        $batches = InsertBatch::batchByEntityType(
            $this->entityManager,
            [
                new EntityC(),
                new EntityC(),
                new EntityC(),
            ],
        );

        self::assertCount(3, $batches);
        self::assertCount(1, $batches[0]->entities);
        self::assertCount(1, $batches[1]->entities);
        self::assertCount(1, $batches[2]->entities);
    }
}

class EntityA
{
}

class EntityB
{
}

class EntityC
{
}
