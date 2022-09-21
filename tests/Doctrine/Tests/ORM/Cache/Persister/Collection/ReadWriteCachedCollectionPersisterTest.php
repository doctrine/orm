<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Persister\Collection\AbstractCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\Tests\Models\Cache\State;
use ReflectionProperty;

/** @group DDC-2183 */
class ReadWriteCachedCollectionPersisterTest extends CollectionPersisterTestCase
{
    protected function createPersister(EntityManagerInterface $em, CollectionPersister $persister, Region $region, array $mapping): AbstractCollectionPersister
    {
        return new ReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
    }

    protected function createRegion(): Region
    {
        return $this->createMock(ConcurrentRegion::class);
    }

    public function testDeleteShouldLockItem(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->delete($collection);
    }

    public function testUpdateShouldLockItem(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($collection);
    }

    public function testUpdateTransactionRollBackShouldEvictItem(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->region->expects(self::once())
            ->method('evict')
            ->with(self::equalTo($key))
            ->willReturn(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($collection);
        $persister->afterTransactionRolledBack();
    }

    public function testDeleteTransactionRollBackShouldEvictItem(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->region->expects(self::once())
            ->method('evict')
            ->with(self::equalTo($key))
            ->willReturn(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->delete($collection);
        $persister->afterTransactionRolledBack();
    }

    public function testTransactionRollBackDeleteShouldClearQueue(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);
        $property   = new ReflectionProperty(ReadWriteCachedCollectionPersister::class, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->region->expects(self::once())
            ->method('evict')
            ->with(self::equalTo($key))
            ->willReturn(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->delete($collection);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testTransactionRollBackUpdateShouldClearQueue(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);
        $property   = new ReflectionProperty(ReadWriteCachedCollectionPersister::class, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->region->expects(self::once())
            ->method('evict')
            ->with(self::equalTo($key))
            ->willReturn(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($collection);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testTransactionRollCommitDeleteShouldClearQueue(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);
        $property   = new ReflectionProperty(ReadWriteCachedCollectionPersister::class, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->region->expects(self::once())
            ->method('evict')
            ->with(self::equalTo($key))
            ->willReturn(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->delete($collection);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testTransactionRollCommitUpdateShouldClearQueue(): void
    {
        $entity     = new State('Foo');
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);
        $property   = new ReflectionProperty(ReadWriteCachedCollectionPersister::class, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->willReturn($lock);

        $this->region->expects(self::once())
            ->method('evict')
            ->with(self::equalTo($key))
            ->willReturn(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($collection);

        self::assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        self::assertCount(0, $property->getValue($persister));
    }

    public function testDeleteLockFailureShouldIgnoreQueue(): void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);
        $property   = new ReflectionProperty(ReadWriteCachedCollectionPersister::class, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->will(self::returnValue(null));

        $this->collectionPersister->expects(self::once())
            ->method('delete')
            ->with(self::identicalTo($collection));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->delete($collection);
        self::assertCount(0, $property->getValue($persister));
    }

    public function testUpdateLockFailureShouldIgnoreQueue(): void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);
        $property   = new ReflectionProperty(ReadWriteCachedCollectionPersister::class, 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($key))
            ->will(self::returnValue(null));

        $this->collectionPersister->expects(self::once())
            ->method('update')
            ->with(self::identicalTo($collection));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $persister->update($collection);
        self::assertCount(0, $property->getValue($persister));
    }
}
