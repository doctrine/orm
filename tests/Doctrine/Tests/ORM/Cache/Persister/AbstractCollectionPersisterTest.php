<?php

namespace Doctrine\Tests\ORM\Cache\Persister;

use Doctrine\Tests\OrmTestCase;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\CollectionPersister;

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
     * @var \Doctrine\ORM\Persisters\CollectionPersister
     */
    protected $collectionPersister;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $regionMockMethods = array(
        'getName',
        'contains',
        'get',
        'put',
        'evict',
        'evictAll'
    );

    /**
     * @var array
     */
    protected $collectionPersisterMockMethods = array(
        'delete',
        'update',
        'deleteRows',
        'insertRows',
        'count',
        'slice',
        'contains',
        'containsKey',
        'removeElement',
        'removeKey',
        'get',
    );

    /**
     * @param \Doctrine\ORM\EntityManager                   $em
     * @param \Doctrine\ORM\Persisters\CollectionPersister  $persister
     * @param \Doctrine\ORM\Cache\Region                    $region
     * @param array                                         $mapping
     *
     * @return Doctrine\ORM\Cache\Persister\AbstractCollectionPersister
     */
    abstract protected function createPersister(EntityManager $em, CollectionPersister $persister, Region $region, array $mapping);

    protected function setUp()
    {
        $this->getSharedSecondLevelCacheDriverImpl()->flushAll();
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em                   = $this->_getTestEntityManager();
        $this->region               = $this->createRegion();
        $this->collectionPersister  = $this->getMock('Doctrine\ORM\Persisters\CollectionPersister', $this->collectionPersisterMockMethods);
    }

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion()
    {
        return $this->getMock('Doctrine\ORM\Cache\Region', $this->regionMockMethods);
    }

    /**
     * @return \Doctrine\ORM\PersistentCollection
     */
    protected function createCollection($owner, $assoc = null, $class = null, $elements = null)
    {
        $em    = $this->em;
        $class = $class ?: $this->em->getClassMetadata('Doctrine\Tests\Models\Cache\State');
        $assoc = $assoc ?: $class->associationMappings['cities'];
        $coll  = new \Doctrine\ORM\PersistentCollection($em, $class, $elements ?: new ArrayCollection);

        $coll->setOwner($owner, $assoc);
        $coll->setInitialized(true);

        return $coll;
    }

    protected function createPersisterDefault()
    {
        $assoc = $this->em->getClassMetadata('Doctrine\Tests\Models\Cache\State')->associationMappings['cities'];

        return $this->createPersister($this->em, $this->collectionPersister, $this->region, $assoc);
    }

    public function testImplementsEntityPersister()
    {
        $persister = $this->createPersisterDefault();

        $this->assertInstanceOf('Doctrine\ORM\Persisters\CollectionPersister', $persister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedPersister', $persister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedCollectionPersister', $persister);
    }

    public function testInvokeDelete()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

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

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($collection));

        $this->assertNull($persister->update($collection));
    }

    public function testInvokeDeleteRows()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('deleteRows')
            ->with($this->equalTo($collection));

        $this->assertNull($persister->deleteRows($collection));
    }

    public function testInvokeInsertRows()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('insertRows')
            ->with($this->equalTo($collection));

        $this->assertNull($persister->insertRows($collection));
    }

    public function testInvokeCount()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('count')
            ->with($this->equalTo($collection))
            ->will($this->returnValue(0));

        $this->assertEquals(0, $persister->count($collection));
    }

    public function testInvokEslice()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $slice      = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

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

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

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

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('containsKey')
            ->with($this->equalTo($collection), $this->equalTo(0))
            ->will($this->returnValue(false));

        $this->assertFalse($persister->containsKey($collection, 0));
    }

    public function testInvokeRemoveElement()
    {
        $entity     = new State("Foo");
        $element    = new State("Bar");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('removeElement')
            ->with($this->equalTo($collection), $this->equalTo($element))
            ->will($this->returnValue(false));

        $this->assertFalse($persister->removeElement($collection, $element));
    }

    public function testInvokeRemoveKey()
    {
        $entity     = new State("Foo");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('removeKey')
            ->with($this->equalTo($collection), $this->equalTo(0))
            ->will($this->returnValue(false));

        $this->assertFalse($persister->removeKey($collection, 0));
    }

    public function testInvokeGet()
    {
        $entity     = new State("Foo");
        $element    = new State("Bar");
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->collectionPersister->expects($this->once())
            ->method('get')
            ->with($this->equalTo($collection), $this->equalTo(0))
            ->will($this->returnValue($element));

        $this->assertEquals($element, $persister->get($collection, 0));
    }
}