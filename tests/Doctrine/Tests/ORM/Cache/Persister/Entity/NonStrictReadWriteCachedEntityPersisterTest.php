<?php

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Persister\Entity\NonStrictReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\Tests\Models\Cache\Country;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteCachedEntityPersisterTest extends AbstractEntityPersisterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, EntityPersister $persister, Region $region, ClassMetadata $metadata)
    {
        return new NonStrictReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
    }

    public function testTransactionRollBackShouldClearQueue()
    {
        $entity    = new Country("Foo");
        $persister = $this->createPersisterDefault();
        $property  = new \ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $persister->update($entity);
        $persister->delete($entity);

        $this->assertCount(2, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testInsertTransactionCommitShouldPutCache()
    {
        $entity    = new Country("Foo");
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id'=>1]);
        $entry     = new EntityCacheEntry(Country::class, ['id'=>1, 'name'=>'Foo']);
        $property  = new \ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('put')
            ->with($this->equalTo($key), $this->equalTo($entry));

        $this->entityPersister->expects($this->once())
            ->method('addInsert')
            ->with($this->equalTo($entity));

        $this->entityPersister->expects($this->once())
            ->method('getInserts')
            ->will($this->returnValue([$entity]));

        $this->entityPersister->expects($this->once())
            ->method('executeInserts');

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $persister->addInsert($entity);
        $persister->executeInserts();

        $this->assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testUpdateTransactionCommitShouldPutCache()
    {
        $entity    = new Country("Foo");
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id'=>1]);
        $entry     = new EntityCacheEntry(Country::class, ['id'=>1, 'name'=>'Foo']);
        $property  = new \ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('put')
            ->with($this->equalTo($key), $this->equalTo($entry));

        $this->entityPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $persister->update($entity);

        $this->assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testDeleteTransactionCommitShouldEvictCache()
    {
        $entity    = new Country("Foo");
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::class, ['id'=>1]);
        $property  = new \ReflectionProperty($persister, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->entityPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $persister->delete($entity);

        $this->assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        $this->assertCount(0, $property->getValue($persister));
    }
}
