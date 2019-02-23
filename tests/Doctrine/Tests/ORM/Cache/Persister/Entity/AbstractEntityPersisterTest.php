<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Cache\Persister\Entity\AbstractEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-2183
 */
abstract class AbstractEntityPersisterTest extends OrmTestCase
{
    /** @var Region */
    protected $region;

    /** @var EntityPersister */
    protected $entityPersister;

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
    protected $entityPersisterMockMethods = [
        'getClassMetadata',
        'getResultSetMapping',
        'getInsertSQL',
        'getSelectSQL',
        'getCountSQL',
        'expandParameters',
        'expandCriteriaParameters',
        'getSelectConditionStatementSQL',
        'getIdentifier',
        'setIdentifier',
        'getColumnValue',
        'insert',
        'update',
        'delete',
        'getOwningTable',
        'load',
        'loadById',
        'loadToOneEntity',
        'count',
        'refresh',
        'loadCriteria',
        'loadAll',
        'getManyToManyCollection',
        'loadManyToManyCollection',
        'loadOneToManyCollection',
        'lock',
        'getOneToManyCollection',
        'exists',
    ];

    /**
     * @return AbstractEntityPersister
     */
    abstract protected function createPersister(EntityManagerInterface $em, EntityPersister $persister, Region $region, ClassMetadata $metadata);

    protected function setUp() : void
    {
        $this->getSharedSecondLevelCacheDriverImpl()->flushAll();
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->em              = $this->getTestEntityManager();
        $this->region          = $this->createRegion();
        $this->entityPersister = $this->getMockBuilder(EntityPersister::class)
                                       ->setMethods($this->entityPersisterMockMethods)
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
     * @return AbstractEntityPersister
     */
    protected function createPersisterDefault()
    {
        return $this->createPersister($this->em, $this->entityPersister, $this->region, $this->em->getClassMetadata(Country::class));
    }

    public function testImplementsEntityPersister() : void
    {
        $persister = $this->createPersisterDefault();

        self::assertInstanceOf(EntityPersister::class, $persister);
        self::assertInstanceOf(CachedPersister::class, $persister);
        self::assertInstanceOf(CachedEntityPersister::class, $persister);
    }

    public function testInvokeGetSelectSQL() : void
    {
        $persister = $this->createPersisterDefault();
        $assoc     = new OneToManyAssociationMetadata('name');

        $this->entityPersister->expects($this->once())
            ->method('getSelectSQL')
            ->with($this->equalTo(['name' => 'Foo']), $this->equalTo($assoc), $this->equalTo(1), $this->equalTo(2), $this->equalTo(3), $this->equalTo(
                [4]
            ))
            ->will($this->returnValue('SELECT * FROM foo WERE name = ?'));

        self::assertEquals('SELECT * FROM foo WERE name = ?', $persister->getSelectSQL(['name' => 'Foo'], $assoc, 1, 2, 3, [4]));
    }

    public function testInvokeGetInsertSQL() : void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('getInsertSQL')
            ->will($this->returnValue('INSERT INTO foo (?)'));

        self::assertEquals('INSERT INTO foo (?)', $persister->getInsertSQL());
    }

    public function testInvokeExpandParameters() : void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('expandParameters')
            ->with($this->equalTo(['name' => 'Foo']))
            ->will($this->returnValue(['name' => 'Foo']));

        self::assertEquals(['name' => 'Foo'], $persister->expandParameters(['name' => 'Foo']));
    }

    public function testInvokeExpandCriteriaParameters() : void
    {
        $persister = $this->createPersisterDefault();
        $criteria  = new Criteria();

        $this->entityPersister->expects($this->once())
            ->method('expandCriteriaParameters')
            ->with($this->equalTo($criteria))
            ->will($this->returnValue(['name' => 'Foo']));

        self::assertEquals(['name' => 'Foo'], $persister->expandCriteriaParameters($criteria));
    }

    public function testInvokeSelectConditionStatementSQL() : void
    {
        $persister = $this->createPersisterDefault();
        $assoc     = new OneToOneAssociationMetadata('id');

        $this->entityPersister->expects($this->once())
            ->method('getSelectConditionStatementSQL')
            ->with($this->equalTo('id'), $this->equalTo(1), $this->equalTo($assoc), $this->equalTo('='))
            ->will($this->returnValue('name = 1'));

        self::assertEquals('name = 1', $persister->getSelectConditionStatementSQL('id', 1, $assoc, '='));
    }

    public function testInvokeInsert() : void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('insert')
            ->with($this->equalTo($entity));

        self::assertNull($persister->insert($entity));
    }

    public function testInvokeUpdate() : void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        self::assertNull($persister->update($entity));
    }

    public function testInvokeDelete() : void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        self::assertNull($persister->delete($entity));
    }

    public function testInvokeGetOwningTable() : void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('getOwningTable')
            ->with($this->equalTo('name'))
            ->will($this->returnValue('t'));

        self::assertEquals('t', $persister->getOwningTable('name'));
    }

    public function testInvokeLoad() : void
    {
        $persister = $this->createPersisterDefault();
        $assoc     = new OneToOneAssociationMetadata('id');
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('load')
            ->with(
                $this->equalTo(['id' => 1]),
                $this->equalTo($entity),
                $this->equalTo($assoc),
                $this->equalTo([1]),
                $this->equalTo(2),
                $this->equalTo(3),
                $this->equalTo([4])
            )
            ->will($this->returnValue($entity));

        self::assertEquals($entity, $persister->load(['id' => 1], $entity, $assoc, [1], 2, 3, [4]));
    }

    public function testInvokeLoadAll() : void
    {
        $rsm       = new ResultSetMappingBuilder($this->em);
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $rsm->addEntityResult(Country::class, 'c');

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->entityPersister->expects($this->once())
            ->method('loadAll')
            ->with($this->equalTo(['id' => 1]), $this->equalTo([0]), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue([$entity]));

        $this->entityPersister->expects($this->once())
            ->method('getResultSetMapping')
            ->will($this->returnValue($rsm));

        self::assertEquals([$entity], $persister->loadAll(['id' => 1], [0], 1, 2));
    }

    public function testInvokeLoadById() : void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('loadById')
            ->with($this->equalTo(['id' => 1]), $this->equalTo($entity))
            ->will($this->returnValue($entity));

        self::assertEquals($entity, $persister->loadById(['id' => 1], $entity));
    }

    public function testInvokeLoadToOneEntity() : void
    {
        $persister = $this->createPersisterDefault();
        $assoc     = new OneToOneAssociationMetadata('name');
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('loadToOneEntity')
            ->with($this->equalTo($assoc), $this->equalTo('foo'), $this->equalTo(['id' => 11]))
            ->will($this->returnValue($entity));

        self::assertEquals($entity, $persister->loadToOneEntity($assoc, 'foo', ['id' => 11]));
    }

    public function testInvokeRefresh() : void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('refresh')
            ->with($this->equalTo(['id' => 1]), $this->equalTo($entity), $this->equalTo(0))
            ->will($this->returnValue($entity));

        self::assertNull($persister->refresh(['id' => 1], $entity));
    }

    public function testInvokeLoadCriteria() : void
    {
        $rsm       = new ResultSetMappingBuilder($this->em);
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');
        $criteria  = new Criteria();

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);
        $rsm->addEntityResult(Country::class, 'c');

        $this->entityPersister->expects($this->once())
            ->method('getResultSetMapping')
            ->will($this->returnValue($rsm));

        $this->entityPersister->expects($this->once())
            ->method('loadCriteria')
            ->with($this->equalTo($criteria))
            ->will($this->returnValue([$entity]));

        self::assertEquals([$entity], $persister->loadCriteria($criteria));
    }

    public function testInvokeGetManyToManyCollection() : void
    {
        $persister = $this->createPersisterDefault();
        $assoc     = new ManyToManyAssociationMetadata('name');
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('getManyToManyCollection')
            ->with($this->equalTo($assoc), $this->equalTo('Foo'), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue([$entity]));

        self::assertEquals([$entity], $persister->getManyToManyCollection($assoc, 'Foo', 1, 2));
    }

    public function testInvokeGetOneToManyCollection() : void
    {
        $persister = $this->createPersisterDefault();
        $assoc     = new OneToManyAssociationMetadata('name');
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('getOneToManyCollection')
            ->with($this->equalTo($assoc), $this->equalTo('Foo'), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue([$entity]));

        self::assertEquals([$entity], $persister->getOneToManyCollection($assoc, 'Foo', 1, 2));
    }

    public function testInvokeLoadManyToManyCollection() : void
    {
        $mapping   = $this->em->getClassMetadata(Country::class);
        $assoc     = new ManyToManyAssociationMetadata('name');
        $coll      = new PersistentCollection($this->em, $mapping, new ArrayCollection());
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('loadManyToManyCollection')
            ->with($this->equalTo($assoc), $this->equalTo('Foo'), $coll)
            ->will($this->returnValue([$entity]));

        self::assertEquals([$entity], $persister->loadManyToManyCollection($assoc, 'Foo', $coll));
    }

    public function testInvokeLoadOneToManyCollection() : void
    {
        $mapping   = $this->em->getClassMetadata(Country::class);
        $assoc     = new OneToManyAssociationMetadata('name');
        $coll      = new PersistentCollection($this->em, $mapping, new ArrayCollection());
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects($this->once())
            ->method('loadOneToManyCollection')
            ->with($this->equalTo($assoc), $this->equalTo('Foo'), $coll)
            ->will($this->returnValue([$entity]));

        self::assertEquals([$entity], $persister->loadOneToManyCollection($assoc, 'Foo', $coll));
    }

    public function testInvokeLock() : void
    {
        $identifier = ['id' => 1];
        $persister  = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($identifier), $this->equalTo(1));

        self::assertNull($persister->lock($identifier, 1));
    }

    public function testInvokeExists() : void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('exists')
            ->with($this->equalTo($entity), $this->equalTo(null));

        self::assertNull($persister->exists($entity));
    }
}
