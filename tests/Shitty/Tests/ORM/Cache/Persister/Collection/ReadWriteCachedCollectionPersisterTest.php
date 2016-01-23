<?php

namespace Shitty\Tests\ORM\Cache\Persister\Collection;

use Shitty\ORM\Cache\Lock;
use Shitty\ORM\Cache\Region;
use Shitty\ORM\EntityManager;
use Shitty\Tests\Models\Cache\State;
use Shitty\ORM\Cache\CollectionCacheKey;
use Shitty\ORM\Persisters\Collection\CollectionPersister;
use Shitty\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister;

/**
 * @group DDC-2183
 */
class ReadWriteCachedCollectionPersisterTest extends AbstractCollectionPersisterTest
{
    protected $regionMockMethods = array(
        'getName',
        'contains',
        'get',
        'getMultiple',
        'put',
        'evict',
        'evictAll',
        'lock',
        'unlock',
    );

    /**
     * {@inheritdoc}
     */
    protected function createPersister(EntityManager $em, CollectionPersister $persister, Region $region, array $mapping)
    {
        return new ReadWriteCachedCollectionPersister($persister, $region, $em, $mapping);
    }

    /**
     * @return \Shitty\ORM\Cache\Region
     */
    protected function createRegion()
    {
        return $this->getMock('Doctrine\ORM\Cache\ConcurrentRegion', $this->regionMockMethods);
    }

    public function testDeleteShouldLockItem()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($collection);
    }

    public function testUpdateShouldLockItem()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($collection);
    }

    public function testUpdateTransactionRollBackShouldEvictItem()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($collection);
        $persister->afterTransactionRolledBack();
    }

    public function testDeleteTransactionRollBackShouldEvictItem()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($collection);
        $persister->afterTransactionRolledBack();
    }

    public function testTransactionRollBackDeleteShouldClearQueue()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));
        $property   = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($collection);

        $this->assertCount(1, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testTransactionRollBackUpdateShouldClearQueue()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));
        $property   = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($collection);

        $this->assertCount(1, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testTransactionRollCommitDeleteShouldClearQueue()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));
        $property   = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($collection);

        $this->assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testTransactionRollCommitUpdateShouldClearQueue()
    {
        $entity     = new State("Foo");
        $lock       = Lock::createLockRead();
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));
        $property   = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($collection);

        $this->assertCount(1, $property->getValue($persister));

        $persister->afterTransactionComplete();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testDeleteLockFailureShouldIgnoreQueue()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));
        $property   = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue(null));

        $this->collectionPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($collection));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($collection);
        $this->assertCount(0, $property->getValue($persister));
    }

    public function testUpdateLockFailureShouldIgnoreQueue()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $key        = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>1));
        $property   = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue(null));

        $this->collectionPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($collection));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($collection);
        $this->assertCount(0, $property->getValue($persister));
    }
}
