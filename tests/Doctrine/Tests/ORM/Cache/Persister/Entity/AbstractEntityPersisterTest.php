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
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group DDC-2183
 */
abstract class AbstractEntityPersisterTest extends OrmTestCase
{
    /** @var Region&MockObject */
    protected $region;

    /** @var EntityPersister&MockObject */
    protected $entityPersister;

    /** @var EntityManagerInterface */
    protected $em;

    abstract protected function createPersister(EntityManagerInterface $em, EntityPersister $persister, Region $region, ClassMetadata $metadata): AbstractEntityPersister;

    protected function setUp(): void
    {
        $this->getSharedSecondLevelCache()->clear();
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em              = $this->getTestEntityManager();
        $this->region          = $this->createRegion();
        $this->entityPersister = $this->createMock(EntityPersister::class);
    }

    /**
     * @return Region&MockObject
     */
    protected function createRegion(): Region
    {
        return $this->createMock(Region::class);
    }

    protected function createPersisterDefault(): AbstractEntityPersister
    {
        return $this->createPersister($this->em, $this->entityPersister, $this->region, $this->em->getClassMetadata(Country::class));
    }

    public function testImplementsEntityPersister(): void
    {
        $persister = $this->createPersisterDefault();

        self::assertInstanceOf(EntityPersister::class, $persister);
        self::assertInstanceOf(CachedPersister::class, $persister);
        self::assertInstanceOf(CachedEntityPersister::class, $persister);
    }

    public function testInvokeAddInsert(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('addInsert')
            ->with(self::equalTo($entity));

        self::assertNull($persister->addInsert($entity));
    }

    public function testInvokeGetInserts(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('getInserts')
            ->will(self::returnValue([$entity]));

        self::assertEquals([$entity], $persister->getInserts());
    }

    public function testInvokeGetSelectSQL(): void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('getSelectSQL')
            ->with(self::equalTo(['name' => 'Foo']), self::equalTo([0]), self::equalTo(1), self::equalTo(2), self::equalTo(3), self::equalTo(
                [4]
            ))
            ->will(self::returnValue('SELECT * FROM foo WERE name = ?'));

        self::assertEquals('SELECT * FROM foo WERE name = ?', $persister->getSelectSQL(
            ['name' => 'Foo'],
            [0],
            1,
            2,
            3,
            [4]
        ));
    }

    public function testInvokeGetInsertSQL(): void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('getInsertSQL')
            ->will(self::returnValue('INSERT INTO foo (?)'));

        self::assertEquals('INSERT INTO foo (?)', $persister->getInsertSQL());
    }

    public function testInvokeExpandParameters(): void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('expandParameters')
            ->with(self::equalTo(['name' => 'Foo']))
            ->will(self::returnValue(['name' => 'Foo']));

        self::assertEquals(['name' => 'Foo'], $persister->expandParameters(['name' => 'Foo']));
    }

    public function testInvokeExpandCriteriaParameters(): void
    {
        $persister = $this->createPersisterDefault();
        $criteria  = new Criteria();

        $this->entityPersister->expects(self::once())
            ->method('expandCriteriaParameters')
            ->with(self::equalTo($criteria))
            ->will(self::returnValue(['name' => 'Foo']));

        self::assertEquals(['name' => 'Foo'], $persister->expandCriteriaParameters($criteria));
    }

    public function testInvokeSelectConditionStatementSQL(): void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('getSelectConditionStatementSQL')
            ->with(self::equalTo('id'), self::equalTo(1), self::equalTo([]), self::equalTo('='))
            ->will(self::returnValue('name = 1'));

        self::assertEquals('name = 1', $persister->getSelectConditionStatementSQL('id', 1, [], '='));
    }

    public function testInvokeExecuteInserts(): void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('executeInserts')
            ->will(self::returnValue(['id' => 1]));

        self::assertEquals(['id' => 1], $persister->executeInserts());
    }

    public function testInvokeUpdate(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('update')
            ->with(self::equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        self::assertNull($persister->update($entity));
    }

    public function testInvokeDelete(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('delete')
            ->with(self::equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        self::assertNull($persister->delete($entity));
    }

    public function testInvokeGetOwningTable(): void
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('getOwningTable')
            ->with(self::equalTo('name'))
            ->will(self::returnValue('t'));

        self::assertEquals('t', $persister->getOwningTable('name'));
    }

    public function testInvokeLoad(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('load')
            ->with(self::equalTo(['id' => 1]), self::equalTo($entity), self::equalTo([0]), self::equalTo(
                [1]
            ), self::equalTo(2), self::equalTo(3), self::equalTo(
                [4]
            ))
            ->will(self::returnValue($entity));

        self::assertEquals($entity, $persister->load(['id' => 1], $entity, [0], [1], 2, 3, [4]));
    }

    public function testInvokeLoadAll(): void
    {
        $rsm       = new ResultSetMappingBuilder($this->em);
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $rsm->addEntityResult(Country::class, 'c');

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->entityPersister->expects(self::once())
            ->method('loadAll')
            ->with(self::equalTo(['id' => 1]), self::equalTo([0]), self::equalTo(1), self::equalTo(2))
            ->will(self::returnValue([$entity]));

        $this->entityPersister->expects(self::once())
            ->method('getResultSetMapping')
            ->will(self::returnValue($rsm));

        self::assertEquals([$entity], $persister->loadAll(['id' => 1], [0], 1, 2));
    }

    public function testInvokeLoadById(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('loadById')
            ->with(self::equalTo(['id' => 1]), self::equalTo($entity))
            ->will(self::returnValue($entity));

        self::assertEquals($entity, $persister->loadById(['id' => 1], $entity));
    }

    public function testInvokeLoadOneToOneEntity(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('loadOneToOneEntity')
            ->with(self::equalTo([]), self::equalTo('foo'), self::equalTo(['id' => 11]))
            ->will(self::returnValue($entity));

        self::assertEquals($entity, $persister->loadOneToOneEntity([], 'foo', ['id' => 11]));
    }

    public function testInvokeRefresh(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('refresh')
            ->with(self::equalTo(['id' => 1]), self::equalTo($entity), self::equalTo(0))
            ->will(self::returnValue($entity));

        self::assertNull($persister->refresh(['id' => 1], $entity));
    }

    public function testInvokeLoadCriteria(): void
    {
        $rsm       = new ResultSetMappingBuilder($this->em);
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');
        $criteria  = new Criteria();

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);
        $rsm->addEntityResult(Country::class, 'c');

        $this->entityPersister->expects(self::once())
            ->method('getResultSetMapping')
            ->will(self::returnValue($rsm));

        $this->entityPersister->expects(self::once())
            ->method('loadCriteria')
            ->with(self::equalTo($criteria))
            ->will(self::returnValue([$entity]));

        self::assertEquals([$entity], $persister->loadCriteria($criteria));
    }

    public function testInvokeGetManyToManyCollection(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('getManyToManyCollection')
            ->with(self::equalTo([]), self::equalTo('Foo'), self::equalTo(1), self::equalTo(2))
            ->will(self::returnValue([$entity]));

        self::assertEquals([$entity], $persister->getManyToManyCollection([], 'Foo', 1, 2));
    }

    public function testInvokeGetOneToManyCollection(): void
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('getOneToManyCollection')
            ->with(self::equalTo([]), self::equalTo('Foo'), self::equalTo(1), self::equalTo(2))
            ->will(self::returnValue([$entity]));

        self::assertEquals([$entity], $persister->getOneToManyCollection([], 'Foo', 1, 2));
    }

    public function testInvokeLoadManyToManyCollection(): void
    {
        $mapping   = $this->em->getClassMetadata(Country::class);
        $assoc     = ['type' => 1];
        $coll      = new PersistentCollection($this->em, $mapping, new ArrayCollection());
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('loadManyToManyCollection')
            ->with(self::equalTo($assoc), self::equalTo('Foo'), $coll)
            ->will(self::returnValue([$entity]));

        self::assertEquals([$entity], $persister->loadManyToManyCollection($assoc, 'Foo', $coll));
    }

    public function testInvokeLoadOneToManyCollection(): void
    {
        $mapping   = $this->em->getClassMetadata(Country::class);
        $assoc     = ['type' => 1];
        $coll      = new PersistentCollection($this->em, $mapping, new ArrayCollection());
        $persister = $this->createPersisterDefault();
        $entity    = new Country('Foo');

        $this->entityPersister->expects(self::once())
            ->method('loadOneToManyCollection')
            ->with(self::equalTo($assoc), self::equalTo('Foo'), $coll)
            ->will(self::returnValue([$entity]));

        self::assertEquals([$entity], $persister->loadOneToManyCollection($assoc, 'Foo', $coll));
    }

    public function testInvokeLock(): void
    {
        $identifier = ['id' => 1];
        $persister  = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('lock')
            ->with(self::equalTo($identifier), self::equalTo(1));

        self::assertNull($persister->lock($identifier, 1));
    }

    public function testInvokeExists(): void
    {
        $entity    = new Country('Foo');
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects(self::once())
            ->method('exists')
            ->with(self::equalTo($entity), self::equalTo(null));

        self::assertNull($persister->exists($entity));
    }
}
