<?php

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister;

/**
 * @group DDC-2183
 */
class ReadWriteCachedEntityPersisterTest extends AbstractEntityPersisterTest
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
    protected function createPersister(EntityManager $em, EntityPersister $persister, Region $region, ClassMetadata $metadata)
    {
        return new ReadWriteCachedEntityPersister($persister, $region, $em, $metadata);
    }

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion()
    {
        return $this->getMock('Doctrine\ORM\Cache\ConcurrentRegion', $this->regionMockMethods);
    }

    public function testDeleteShouldLockItem()
    {
        $entity    = new Country("Foo");
        $lock      = Lock::createLockRead();
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($entity);
    }

    public function testUpdateShouldLockItem()
    {
        $entity    = new Country("Foo");
        $lock      = Lock::createLockRead();
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($entity);
    }

    public function testUpdateTransactionRollBackShouldEvictItem()
    {
        $entity    = new Country("Foo");
        $lock      = Lock::createLockRead();
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($entity);
        $persister->afterTransactionRolledBack();
    }

    public function testDeleteTransactionRollBackShouldEvictItem()
    {
        $entity    = new Country("Foo");
        $lock      = Lock::createLockRead();
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->once())
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($entity);
        $persister->afterTransactionRolledBack();
    }

    public function testTransactionRollBackShouldClearQueue()
    {
        $entity    = new Country("Foo");
        $lock      = Lock::createLockRead();
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));
        $property  = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->exactly(2))
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->exactly(2))
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($entity);
        $persister->delete($entity);

        $this->assertCount(2, $property->getValue($persister));

        $persister->afterTransactionRolledBack();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testTransactionCommitShouldClearQueue()
    {
        $entity    = new Country("Foo");
        $lock      = Lock::createLockRead();
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));
        $property  = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->exactly(2))
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue($lock));

        $this->region->expects($this->exactly(2))
            ->method('evict')
            ->with($this->equalTo($key));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($entity);
        $persister->delete($entity);

        $this->assertCount(2, $property->getValue($persister));

        $persister->afterTransactionComplete();

        $this->assertCount(0, $property->getValue($persister));
    }

    public function testDeleteLockFailureShouldIgnoreQueue()
    {
        $entity    = new Country("Foo");
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));
        $property  = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue(null));

        $this->entityPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->delete($entity);
        $this->assertCount(0, $property->getValue($persister));
    }

    public function testUpdateLockFailureShouldIgnoreQueue()
    {
        $entity    = new Country("Foo");
        $persister = $this->createPersisterDefault();
        $key       = new EntityCacheKey(Country::CLASSNAME, array('id'=>1));
        $property  = new \ReflectionProperty('Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister', 'queuedCache');

        $property->setAccessible(true);

        $this->region->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($key))
            ->will($this->returnValue(null));

        $this->entityPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $persister->update($entity);
        $this->assertCount(0, $property->getValue($persister));
    }
}
