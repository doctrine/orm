<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Persister\Entity\NonStrictReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\Tests\Models\Cache\Country;
use ReflectionProperty;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteCachedEntityPersisterTest extends AbstractEntityPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManagerInterface $em, EntityPersister $persister, Region $region, ClassMetadata $metadata)
    {
        return new NonStrictReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
    }

    public function testTransactionRollBackShouldClearQueue() : void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($entity);
        $persister->delete($entity);

        self::assertCount(2, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testInsertTransactionCommitShouldPutCache() : void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id' => 1]);
        $entry     = new EntityCacheEntry(Country::class, ['id' => 1, 'name' => 'Foo']);
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('put')
            ->with($this->equalTo($key), $this->equalTo($entry));

        $this->entityPersister->expects($this->once())
            ->method('insert')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->insert($entity);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testUpdateTransactionCommitShouldPutCache() : void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id' => 1]);
        $entry     = new EntityCacheEntry(Country::class, ['id' => 1, 'name' => 'Foo']);
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('put')
            ->with($this->equalTo($key), $this->equalTo($entry));

        $this->entityPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($entity);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testDeleteTransactionCommitShouldEvictCache() : void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id' => 1]);
        $property  = new ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->entityPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->delete($entity);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }
}
