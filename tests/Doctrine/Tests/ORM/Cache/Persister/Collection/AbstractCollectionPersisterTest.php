<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Cache\Persister\Collection\AbstractCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-2183
 */
abstract class AbstractCollectionPersisterTest extends OrmTestCase
{
    /** @var Region */
    protected $region;

    /** @var CollectionPersister */
    protected $collectionPersister;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var array */
    protected $regionMockMethods = [
        'getName',
        'contains',
        'get',
        'getMultiple',
        'put',
        'evict',
        'evictAll',
    ];

    /** @var array */
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
        'loadCriteria',
    ];

    /**
     * @return AbstractCollectionPersister
     */
    abstract protected function createPersister(
        EntityManagerInterface $em,
        CollectionPersister $persister,
        Region $region,
        AssociationMetadata $association
    );

    protected function setUp() : void
    {
        $this->getSharedSecondLevelCacheDriverImpl()->flushAll();
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em                  = $this->getTestEntityManager();
        $this->region              = $this->createRegion();
        $this->collectionPersister = $this->getMockBuilder(CollectionPersister::class)
                                           ->setMethods($this->collectionPersisterMockMethods)
                                           ->getMock();
    }

    /**
     * @return Region
     */
    protected function createRegion()
    {
        return $this->getMockBuilder(Region::class)
                    ->setMethods($this->regionMockMethods)
                    ->getMock();
    }

    /**
     * @return PersistentCollection
     */
    protected function createCollection($owner, $assoc = null, $class = null, $elements = null)
    {
        $em    = $this->em;
        $class = $class ?: $this->em->getClassMetadata(State::class);
        $assoc = $assoc ?: $class->getProperty('cities');
        $coll  = new PersistentCollection($em, $class, $elements ?: new ArrayCollection());

        $coll->setOwner($owner, $assoc);
        $coll->setInitialized(true);

        return $coll;
    }

    protected function createPersisterDefault()
    {
        $assoc = $this->em->getClassMetadata(State::class)->getProperty('cities');

        return $this->createPersister($this->em, $this->collectionPersister, $this->region, $assoc);
    }

    public function testImplementsEntityPersister() : void
    {
        $persister = $this->createPersisterDefault();

        self::assertInstanceOf(CollectionPersister::class, $persister);
        self::assertInstanceOf(CachedPersister::class, $persister);
        self::assertInstanceOf(CachedCollectionPersister::class, $persister);
    }

    public function testInvokeDelete() : void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($collection));

        self::assertNull($persister->delete($collection));
    }

    public function testInvokeUpdate() : void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $collection->setDirty(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($collection));

        self::assertNull($persister->update($collection));
    }

    public function testInvokeCount() : void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('count')
            ->with($this->equalTo($collection))
            ->will($this->returnValue(0));

        self::assertEquals(0, $persister->count($collection));
    }

    public function testInvokeSlice() : void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $slice      = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('slice')
            ->with($this->equalTo($collection), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue($slice));

        self::assertEquals($slice, $persister->slice($collection, 1, 2));
    }

    public function testInvokeContains() : void
    {
        $entity     = new State('Foo');
        $element    = new State('Bar');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('contains')
            ->with($this->equalTo($collection), $this->equalTo($element))
            ->will($this->returnValue(false));

        self::assertFalse($persister->contains($collection, $element));
    }

    public function testInvokeContainsKey() : void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('containsKey')
            ->with($this->equalTo($collection), $this->equalTo(0))
            ->will($this->returnValue(false));

        self::assertFalse($persister->containsKey($collection, 0));
    }

    public function testInvokeRemoveElement() : void
    {
        $entity     = new State('Foo');
        $element    = new State('Bar');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('removeElement')
            ->with($this->equalTo($collection), $this->equalTo($element))
            ->will($this->returnValue(false));

        self::assertFalse($persister->removeElement($collection, $element));
    }

    public function testInvokeGet() : void
    {
        $entity     = new State('Foo');
        $element    = new State('Bar');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects($this->once())
            ->method('get')
            ->with($this->equalTo($collection), $this->equalTo(0))
            ->will($this->returnValue($element));

        self::assertEquals($element, $persister->get($collection, 0));
    }
}
