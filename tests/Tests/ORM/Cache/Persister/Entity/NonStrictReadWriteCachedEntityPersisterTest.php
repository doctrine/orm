<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Persister\Entity\AbstractEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\NonStrictReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\Tests\Models\Cache\Country;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;

#[Group('DDC-2183')]
class NonStrictReadWriteCachedEntityPersisterTest extends EntityPersisterTestCase
{
    protected function createPersister(EntityManagerInterface $em, EntityPersister $persister, Region $region, ClassMetadata $metadata): AbstractEntityPersister
    {
        return new NonStrictReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
    }

    public function testTransactionRollBackShouldClearQueue(): void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($entity);
        $persister->delete($entity);

        self::assertCount(2, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testInsertTransactionCommitShouldPutCache(): void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id' => 1]);
        $entry     = new EntityCacheEntry(Country::class, ['id' => 1, 'name' => 'Foo']);
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $this->region->expects(self::once())
            ->method('put')
            ->with(self::equalTo($key), self::equalTo($entry));

        $this->entityPersister->expects(self::once())
            ->method('addInsert')
            ->with(self::equalTo($entity));

        $this->entityPersister->expects(self::once())
            ->method('getInserts')
            ->willReturn([$entity]);

        $this->entityPersister->expects(self::once())
            ->method('executeInserts');

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->addInsert($entity);
        $persister->executeInserts();

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testUpdateTransactionCommitShouldPutCache(): void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id' => 1]);
        $entry     = new EntityCacheEntry(Country::class, ['id' => 1, 'name' => 'Foo']);
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $this->region->expects(self::once())
            ->method('put')
            ->with(self::equalTo($key), self::equalTo($entry));

        $this->entityPersister->expects(self::once())
            ->method('update')
            ->with(self::equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($entity);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testDeleteTransactionCommitShouldEvictCache(): void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id' => 1]);
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $this->region->expects(self::once())
            ->method('evict')
            ->with(self::equalTo($key));

        $this->entityPersister->expects(self::once())
            ->method('delete')
            ->with(self::equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->delete($entity);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }
}
