<?php

namespace Doctrine\Tests\ORM\Cache\Persister\Entity;

use Doctrine\Tests\OrmTestCase;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\PersistentCollection;

/**
 * @group DDC-2183
 */
abstract class AbstractEntityPersisterTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    protected $region;

    /**
     * @var \Doctrine\ORM\Persisters\Entity\EntityPersister
     */
    protected $entityPersister;

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
        'getMultiple',
        'put',
        'evict',
        'evictAll'
    );

    /**
     * @var array
     */
    protected $entityPersisterMockMethods = array(
        'getClassMetadata',
        'getResultSetMapping',
        'getInserts',
        'getInsertSQL',
        'getSelectSQL',
        'getCountSQL',
        'expandParameters',
        'expandCriteriaParameters',
        'getSelectConditionStatementSQL',
        'addInsert',
        'executeInserts',
        'update',
        'delete',
        'getOwningTable',
        'load',
        'loadById',
        'loadOneToOneEntity',
        'count',
        'refresh',
        'loadCriteria',
        'loadAll',
        'getManyToManyCollection',
        'loadManyToManyCollection',
        'loadOneToManyCollection',
        'lock',
        'getOneToManyCollection',
        'exists'
    );

    /**
     * @param \Doctrine\ORM\EntityManager                     $em
     * @param \Doctrine\ORM\Persisters\Entity\EntityPersister $persister
     * @param \Doctrine\ORM\Cache\Region                      $region
     * @param \Doctrine\ORM\Mapping\ClassMetadata             $metadata
     *
     * @return \Doctrine\ORM\Cache\Persister\Entity\AbstractEntityPersister
     */
    abstract protected function createPersister(EntityManager $em, EntityPersister $persister, Region $region, ClassMetadata $metadata);

    protected function setUp()
    {
        $this->getSharedSecondLevelCacheDriverImpl()->flushAll();
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em               = $this->_getTestEntityManager();
        $this->region           = $this->createRegion();
        $this->entityPersister  = $this->getMock(
            'Doctrine\ORM\Persisters\Entity\EntityPersister',
            $this->entityPersisterMockMethods
        );
    }

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion()
    {
        return $this->getMock('Doctrine\ORM\Cache\Region', $this->regionMockMethods);
    }

    /**
     * @return \Doctrine\ORM\Cache\Persister\AbstractEntityPersister
     */
    protected function createPersisterDefault()
    {
        return $this->createPersister($this->em, $this->entityPersister, $this->region, $this->em->getClassMetadata('Doctrine\Tests\Models\Cache\Country'));
    }

    public function testImplementsEntityPersister()
    {
        $persister = $this->createPersisterDefault();

        $this->assertInstanceOf('Doctrine\ORM\Persisters\Entity\EntityPersister', $persister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedPersister', $persister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $persister);
    }

    public function testInvokeAddInsert()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('addInsert')
            ->with($this->equalTo($entity));

        $this->assertNull($persister->addInsert($entity));
    }

    public function testInvokeGetInserts()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('getInserts')
            ->will($this->returnValue(array($entity)));

        $this->assertEquals(array($entity), $persister->getInserts());
    }

    public function testInvokeGetSelectSQL()
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('getSelectSQL')
            ->with($this->equalTo(array('name'=>'Foo')), $this->equalTo(array(0)), $this->equalTo(1), $this->equalTo(2), $this->equalTo(3), $this->equalTo(array(4)))
            ->will($this->returnValue('SELECT * FROM foo WERE name = ?'));

        $this->assertEquals('SELECT * FROM foo WERE name = ?', $persister->getSelectSQL(array('name'=>'Foo'), array(0), 1, 2, 3, array(4)));
    }

    public function testInvokeGetInsertSQL()
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('getInsertSQL')
            ->will($this->returnValue('INSERT INTO foo (?)'));

        $this->assertEquals('INSERT INTO foo (?)', $persister->getInsertSQL());
    }

    public function testInvokeExpandParameters()
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('expandParameters')
            ->with($this->equalTo(array('name'=>'Foo')))
            ->will($this->returnValue(array('name'=>'Foo')));

        $this->assertEquals(array('name'=>'Foo'), $persister->expandParameters(array('name'=>'Foo')));
    }

    public function testInvokeExpandCriteriaParameters()
    {
        $persister = $this->createPersisterDefault();
        $criteria  = new Criteria();

        $this->entityPersister->expects($this->once())
            ->method('expandCriteriaParameters')
            ->with($this->equalTo($criteria))
            ->will($this->returnValue(array('name'=>'Foo')));

        $this->assertEquals(array('name'=>'Foo'), $persister->expandCriteriaParameters($criteria));
    }

    public function testInvokeSelectConditionStatementSQL()
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('getSelectConditionStatementSQL')
            ->with($this->equalTo('id'), $this->equalTo(1), $this->equalTo(array()), $this->equalTo('='))
            ->will($this->returnValue('name = 1'));

        $this->assertEquals('name = 1', $persister->getSelectConditionStatementSQL('id', 1, array(), '='));
    }

    public function testInvokeExecuteInserts()
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('executeInserts')
            ->will($this->returnValue(array('id' => 1)));

        $this->assertEquals(array('id' => 1), $persister->executeInserts());
    }

    public function testInvokeUpdate()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('update')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->assertNull($persister->update($entity));
    }

    public function testInvokeDelete()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($entity));

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->assertNull($persister->delete($entity));
    }

    public function testInvokeGetOwningTable()
    {
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('getOwningTable')
            ->with($this->equalTo('name'))
            ->will($this->returnValue('t'));

        $this->assertEquals('t', $persister->getOwningTable('name'));
    }

    public function testInvokeLoad()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('load')
            ->with($this->equalTo(array('id' => 1)), $this->equalTo($entity), $this->equalTo(array(0)), $this->equalTo(array(1)), $this->equalTo(2), $this->equalTo(3), $this->equalTo(array(4)))
            ->will($this->returnValue($entity));

        $this->assertEquals($entity, $persister->load(array('id' => 1), $entity, array(0), array(1), 2, 3, array(4)));
    }

    public function testInvokeLoadAll()
    {
        $rsm       = new ResultSetMappingBuilder($this->em);
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $rsm->addEntityResult(Country::CLASSNAME, 'c');

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));

        $this->entityPersister->expects($this->once())
            ->method('loadAll')
            ->with($this->equalTo(array('id' => 1)), $this->equalTo(array(0)), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue(array($entity)));

        $this->entityPersister->expects($this->once())
            ->method('getResultSetMapping')
            ->will($this->returnValue($rsm));

        $this->assertEquals(array($entity), $persister->loadAll(array('id' => 1), array(0), 1, 2));
    }

    public function testInvokeLoadById()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('loadById')
            ->with($this->equalTo(array('id' => 1)), $this->equalTo($entity))
            ->will($this->returnValue($entity));

        $this->assertEquals($entity, $persister->loadById(array('id' => 1), $entity));
    }

    public function testInvokeLoadOneToOneEntity()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('loadOneToOneEntity')
            ->with($this->equalTo(array()), $this->equalTo('foo'), $this->equalTo(array('id' => 11)))
            ->will($this->returnValue($entity));

        $this->assertEquals($entity, $persister->loadOneToOneEntity(array(), 'foo', array('id' => 11)));
    }

    public function testInvokeRefresh()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('refresh')
            ->with($this->equalTo(array('id' => 1)), $this->equalTo($entity), $this->equalTo(0))
            ->will($this->returnValue($entity));

        $this->assertNull($persister->refresh(array('id' => 1), $entity), 0);
    }

    public function testInvokeLoadCriteria()
    {
        $rsm       = new ResultSetMappingBuilder($this->em);
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");
        $criteria  = new Criteria();

        $this->em->getUnitOfWork()->registerManaged($entity, array('id'=>1), array('id'=>1, 'name'=>'Foo'));
        $rsm->addEntityResult(Country::CLASSNAME, 'c');

        $this->entityPersister->expects($this->once())
            ->method('getResultSetMapping')
            ->will($this->returnValue($rsm));

        $this->entityPersister->expects($this->once())
            ->method('loadCriteria')
            ->with($this->equalTo($criteria))
            ->will($this->returnValue(array($entity)));

        $this->assertEquals(array($entity), $persister->loadCriteria($criteria));
    }

    public function testInvokeGetManyToManyCollection()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('getManyToManyCollection')
            ->with($this->equalTo(array()), $this->equalTo('Foo'), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue(array($entity)));

        $this->assertEquals(array($entity), $persister->getManyToManyCollection(array(), 'Foo', 1 ,2));
    }

    public function testInvokeGetOneToManyCollection()
    {
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('getOneToManyCollection')
            ->with($this->equalTo(array()), $this->equalTo('Foo'), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue(array($entity)));

        $this->assertEquals(array($entity), $persister->getOneToManyCollection(array(), 'Foo', 1 ,2));
    }

    public function testInvokeLoadManyToManyCollection()
    {
        $mapping   = $this->em->getClassMetadata('Doctrine\Tests\Models\Cache\Country');
        $assoc     = array('type' => 1);
        $coll      = new PersistentCollection($this->em, $mapping, new ArrayCollection());
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('loadManyToManyCollection')
            ->with($this->equalTo($assoc), $this->equalTo('Foo'), $coll)
            ->will($this->returnValue(array($entity)));

        $this->assertEquals(array($entity), $persister->loadManyToManyCollection($assoc, 'Foo', $coll));
    }

    public function testInvokeLoadOneToManyCollection()
    {
        $mapping   = $this->em->getClassMetadata('Doctrine\Tests\Models\Cache\Country');
        $assoc     = array('type' => 1);
        $coll      = new PersistentCollection($this->em, $mapping, new ArrayCollection());
        $persister = $this->createPersisterDefault();
        $entity    = new Country("Foo");

        $this->entityPersister->expects($this->once())
            ->method('loadOneToManyCollection')
            ->with($this->equalTo($assoc), $this->equalTo('Foo'), $coll)
            ->will($this->returnValue(array($entity)));

        $this->assertEquals(array($entity), $persister->loadOneToManyCollection($assoc, 'Foo', $coll));
    }

    public function testInvokeLock()
    {
        $identifier = array('id' => 1);
        $persister  = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('lock')
            ->with($this->equalTo($identifier), $this->equalTo(1));

        $this->assertNull($persister->lock($identifier, 1));
    }

    public function testInvokeExists()
    {
        $entity    = new Country("Foo");
        $persister = $this->createPersisterDefault();

        $this->entityPersister->expects($this->once())
            ->method('exists')
            ->with($this->equalTo($entity), $this->equalTo(null));

        $this->assertNull($persister->exists($entity));
    }
}
