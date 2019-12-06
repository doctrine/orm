<?php

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmTestCase;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;

use Doctrine\Tests\Models\Cache\State;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-2183
 */
abstract class AbstractCollectionPersisterTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    protected $region;

    /**
     * @var \Doctrine\ORM\Persisters\Collection\CollectionPersister
     */
    protected $collectionPersister;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $regionMockMethods = [
        'getName',
        'contains',
        'get',
        'getMultiple',
        'put',
        'evict',
        'evictAll'
    ];

    /**
     * @var array
     */
    protected $collectionPersisterMockMethods = [
        'delete',
        'update',
        'count',
        'slice',
        'contains',
        'containsKey',
        'removeElement',
        'removeKey',
        'get',
        'getMultiple',
        'loadCriteria'
    ];

    /**
     * @param \Doctrine\ORM\EntityManager                             $em
     * @param \Doctrine\ORM\Persisters\Collection\CollectionPersister $persister
     * @param \Doctrine\ORM\Cache\Region                              $region
     * @param array                                                   $mapping
     *
     * @return \Doctrine\ORM\Cache\Persister\Collection\AbstractCollectionPersister
     */
    abstract protected function createPersister(EntityManager $em, CollectionPersister $persister, Region $region, array $mapping);

    protected function setUp()
    {
        $this->getSharedSecondLevelCacheDriverImpl()->flushAll();
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em                   = $this->_getTestEntityManager();
        $this->region               = $this->createRegion();
        $this->collectionPersister  = $this->getMockBuilder(CollectionPersister::class)
                                           ->setMethods($this->collectionPersisterMockMethods)
                                           ->getMock();
    }

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion()
    {
        return $this->getMockBuilder(Region::class)
                    ->setMethods($this->regionMockMethods)
                    ->getMock();
    }

    /**
     * @return \Doctrine\ORM\PersistentCollection
     */
    protected function createCollection($owner, $assoc = null, $class = null, $elements = null)
    {
        $em    = $this->em;
        $class = $class ?: $this->em->getClassMetadata(State::class);
        $assoc = $assoc ?: $class->associationMappings['cities'];
        $coll  = new PersistentCollection($em, $class, $elements ?: new ArrayCollection);

        $coll->setOwner($owner, $assoc);
        $coll->setInitialized(true);

        return $coll;
    }

    protected function createPersisterDefault()
    {
        $assoc = $this->em->getClassMetadata(State::class)->associationMappings['cities'];

        return $this->createPersister($this->em, $this->collectionPersister, $this->region, $assoc);
    }

    public function testImplementsEntityPersister()
    {
        $persister = $this->createPersisterDefault();

        $this->assertInstanceOf(CollectionPersister::class, $persister);
        $this->assertInstanceOf(CachedPersister::class, $persister);
        $this->assertInstanceOf(CachedCollectionPersister::class, $persister);
    }

    public function testInvokeDelete()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($collection));

        $this->assertNull($persister->delete($collection));
    }

    public function testInvokeUpdate()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $collection->setDirty(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($collection));

        $this->assertNull($persister->update($collection));
    }

    public function testInvokeCount()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('count')
            ->with($this->equalTo($collection))
            ->will($this->returnValue(0));

        $this->assertEquals(0, $persister->count($collection));
    }

    public function testInvokeSlice()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $slice      = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('slice')
            ->with($this->equalTo($collection), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue($slice));

        $this->assertEquals($slice, $persister->slice($collection, 1 , 2));
    }

    public function testInvokeContains()
    {
        $entity     = new State("Foo");
        $element    = new State("Bar");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('contains')
            ->with($this->equalTo($collection), $this->equalTo($element))
            ->will($this->returnValue(false));

        $this->assertFalse($persister->contains($collection,$element));
    }

    public function testInvokeContainsKey()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('containsKey')
            ->with($this->equalTo($collection), $this->equalTo(0))
            ->will($this->returnValue(false));

        $this->assertFalse($persister->containsKey($collection, 0));
    }

    public function testInvokeGet()
    {
        $entity     = new State("Foo");
        $element    = new State("Bar");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id'=>1], ['id'=>1, 'name'=>'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('get')
            ->with($this->equalTo($collection), $this->equalTo(0))
            ->will($this->returnValue($element));

        $this->assertEquals($element, $persister->get($collection, 0));
    }
}
