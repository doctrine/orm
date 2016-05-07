<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Country;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\DefaultEntityHydrator;
use Doctrine\ORM\Cache\AssociationCacheEntry;

/**
 * @group DDC-2183
 */
class DefaultEntityHydratorTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\EntityHydrator
     */
    private $structure;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    protected function setUp()
    {
        parent::setUp();

        $this->em        = $this->_getTestEntityManager();
        $this->structure = new DefaultEntityHydrator($this->em);
    }

    public function testImplementsEntityEntryStructure()
    {
        self::assertInstanceOf('\Doctrine\ORM\Cache\EntityHydrator', $this->structure);
    }

    public function testCreateEntity()
    {
        $metadata = $this->em->getClassMetadata(Country::CLASSNAME);
        $key      = new EntityCacheKey($metadata->name, array('id'=>1));
        $entry    = new EntityCacheEntry($metadata->name, array('id'=>1, 'name'=>'Foo'));
        $entity   = $this->structure->loadCacheEntry($metadata, $key, $entry);

        self::assertInstanceOf($metadata->name, $entity);

        self::assertEquals(1, $entity->getId());
        self::assertEquals('Foo', $entity->getName());
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($entity));
    }

    public function testLoadProxy()
    {
        $metadata = $this->em->getClassMetadata(Country::CLASSNAME);
        $key      = new EntityCacheKey($metadata->name, array('id'=>1));
        $entry    = new EntityCacheEntry($metadata->name, array('id'=>1, 'name'=>'Foo'));
        $proxy    = $this->em->getReference($metadata->name, $key->identifier);
        $entity   = $this->structure->loadCacheEntry($metadata, $key, $entry, $proxy);

        self::assertInstanceOf($metadata->name, $entity);
        self::assertSame($proxy, $entity);

        self::assertEquals(1, $entity->getId());
        self::assertEquals('Foo', $entity->getName());
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($proxy));
    }

    public function testBuildCacheEntry()
    {
        $entity   = new Country('Foo');
        $uow      = $this->em->getUnitOfWork();
        $data     = array('id'=>1, 'name'=>'Foo');
        $metadata = $this->em->getClassMetadata(Country::CLASSNAME);
        $key      = new EntityCacheKey($metadata->name, array('id'=>1));

        $entity->setId(1);
        $uow->registerManaged($entity, $key->identifier, $data);

        $cache  = $this->structure->buildCacheEntry($metadata, $key, $entity);

        self::assertInstanceOf('Doctrine\ORM\Cache\CacheEntry', $cache);
        self::assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertEquals(array(
            'id'   => 1,
            'name' => 'Foo',
        ), $cache->data);
    }

    public function testBuildCacheEntryAssociation()
    {
        $country        = new Country('Foo');
        $state          = new State('Bat', $country);
        $uow            = $this->em->getUnitOfWork();
        $countryData    = array('id'=>11, 'name'=>'Foo');
        $stateData      = array('id'=>12, 'name'=>'Bar', 'country' => $country);
        $metadata       = $this->em->getClassMetadata(State::CLASSNAME);
        $key            = new EntityCacheKey($metadata->name, array('id'=>11));

        $country->setId(11);
        $state->setId(12);

        $uow->registerManaged($country, array('id'=>11), $countryData);
        $uow->registerManaged($state, array('id'=>12), $stateData);

        $cache = $this->structure->buildCacheEntry($metadata, $key, $state);

        self::assertInstanceOf('Doctrine\ORM\Cache\CacheEntry', $cache);
        self::assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertArrayHasKey('country', $cache->data);
        self::assertEquals(array(
            'id'        => 12,
            'name'      => 'Bar',
            'country'   => new AssociationCacheEntry(Country::CLASSNAME, array('id' => 11)),
        ), $cache->data);
    }

    public function testBuildCacheEntryNonInitializedAssocProxy()
    {
        $proxy          = $this->em->getReference(Country::CLASSNAME, 11);
        $entity         = new State('Bat', $proxy);
        $uow            = $this->em->getUnitOfWork();
        $entityData     = array('id'=>12, 'name'=>'Bar', 'country' => $proxy);
        $metadata       = $this->em->getClassMetadata(State::CLASSNAME);
        $key            = new EntityCacheKey($metadata->name, array('id'=>11));

        $entity->setId(12);

        $uow->registerManaged($entity, array('id'=>12), $entityData);

        $cache = $this->structure->buildCacheEntry($metadata, $key, $entity);

        self::assertInstanceOf('Doctrine\ORM\Cache\CacheEntry', $cache);
        self::assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertArrayHasKey('country', $cache->data);
        self::assertEquals(array(
            'id'        => 12,
            'name'      => 'Bar',
            'country'   => new AssociationCacheEntry(Country::CLASSNAME, array('id' => 11)),
        ), $cache->data);
    }

    public function testCacheEntryWithWrongIdentifierType()
    {
        $proxy          = $this->em->getReference(Country::CLASSNAME, 11);
        $entity         = new State('Bat', $proxy);
        $uow            = $this->em->getUnitOfWork();
        $entityData     = array('id'=> 12, 'name'=>'Bar', 'country' => $proxy);
        $metadata       = $this->em->getClassMetadata(State::CLASSNAME);
        $key            = new EntityCacheKey($metadata->name, array('id'=>'12'));

        $entity->setId(12);

        $uow->registerManaged($entity, array('id'=>12), $entityData);

        $cache = $this->structure->buildCacheEntry($metadata, $key, $entity);

        self::assertInstanceOf('Doctrine\ORM\Cache\CacheEntry', $cache);
        self::assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertArrayHasKey('country', $cache->data);
        self::assertSame($entity->getId(), $cache->data['id']);
        self::assertEquals(array(
            'id'        => 12,
            'name'      => 'Bar',
            'country'   => new AssociationCacheEntry(Country::CLASSNAME, array('id' => 11)),
        ), $cache->data);
    }

}
