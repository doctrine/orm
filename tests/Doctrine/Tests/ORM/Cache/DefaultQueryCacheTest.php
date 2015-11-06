<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\Mocks\CacheRegionMock;
use Doctrine\ORM\Cache\DefaultQueryCache;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\ORM\Cache\QueryCacheEntry;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache;

/**
 * @group DDC-2183
 */
class DefaultQueryCacheTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\DefaultQueryCache
     */
    private $queryCache;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\Tests\Mocks\CacheRegionMock
     */
    private $region;

    /**
     * @var \Doctrine\Tests\ORM\Cache\CacheFactoryDefaultQueryCacheTest
     */
    private $cacheFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->enableSecondLevelCache();

        $this->em           = $this->_getTestEntityManager();
        $this->region       = new CacheRegionMock();
        $this->queryCache   = new DefaultQueryCache($this->em, $this->region);
        $this->cacheFactory = new CacheFactoryDefaultQueryCacheTest($this->queryCache, $this->region);

        $this->em->getConfiguration()
            ->getSecondLevelCacheConfiguration()
            ->setCacheFactory($this->cacheFactory);
    }

    public function testImplementQueryCache()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCache', $this->queryCache);
    }

    public function testGetRegion()
    {
        $this->assertSame($this->region, $this->queryCache->getRegion());
    }
    
    public function testClearShouldEvictRegion()
    {
        $this->queryCache->clear();

        $this->assertArrayHasKey('evictAll', $this->region->calls);
        $this->assertCount(1, $this->region->calls['evictAll']);
    }

    public function testPutBasicQueryResult()
    {
        $result   = array();
        $key      = new QueryCacheKey('query.key1', 0);
        $rsm      = new ResultSetMappingBuilder($this->em);
        $metadata = $this->em->getClassMetadata(Country::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        for ($i = 0; $i < 4; $i++) {
            $name       = "Country $i";
            $entity     = new Country($name);
            $result[]   = $entity;

            $metadata->setFieldValue($entity, 'id', $i);
            $this->em->getUnitOfWork()->registerManaged($entity, array('id' => $i), array('name' => $name));
        }

        $this->assertTrue($this->queryCache->put($key, $rsm, $result));
        $this->assertArrayHasKey('put', $this->region->calls);
        $this->assertCount(5, $this->region->calls['put']);

        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][0]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][1]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][2]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][3]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCacheKey', $this->region->calls['put'][4]['key']);

        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $this->region->calls['put'][0]['entry']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $this->region->calls['put'][1]['entry']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $this->region->calls['put'][2]['entry']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheEntry', $this->region->calls['put'][3]['entry']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCacheEntry', $this->region->calls['put'][4]['entry']);
    }

    public function testPutToOneAssociationQueryResult()
    {
        $result     = array();
        $uow        = $this->em->getUnitOfWork();
        $key        = new QueryCacheKey('query.key1', 0);
        $rsm        = new ResultSetMappingBuilder($this->em);
        $cityClass  = $this->em->getClassMetadata(City::CLASSNAME);
        $stateClass = $this->em->getClassMetadata(State::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(City::CLASSNAME, 'c');
        $rsm->addJoinedEntityFromClassMetadata(State::CLASSNAME, 's', 'c', 'state', array('id'=>'state_id', 'name'=>'state_name'));

        for ($i = 0; $i < 4; $i++) {
            $state    = new State("State $i");
            $city     = new City("City $i", $state);
            $result[] = $city;

            $cityClass->setFieldValue($city, 'id', $i);
            $stateClass->setFieldValue($state, 'id', $i*2);

            $uow->registerManaged($state, array('id' => $state->getId()), array('name' => $city->getName()));
            $uow->registerManaged($city, array('id' => $city->getId()), array('name' => $city->getName(), 'state' => $state));
        }

        $this->assertTrue($this->queryCache->put($key, $rsm, $result));
        $this->assertArrayHasKey('put', $this->region->calls);
        $this->assertCount(9, $this->region->calls['put']);

        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][0]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][1]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][2]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][3]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][4]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][5]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][6]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][7]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCacheKey', $this->region->calls['put'][8]['key']);
    }

    public function testPutToOneAssociation2LevelsQueryResult()
    {
        $result     = array();
        $uow        = $this->em->getUnitOfWork();
        $key        = new QueryCacheKey('query.key1', 0);
        $rsm        = new ResultSetMappingBuilder($this->em);
        $cityClass  = $this->em->getClassMetadata(City::CLASSNAME);
        $stateClass = $this->em->getClassMetadata(State::CLASSNAME);
        $countryClass = $this->em->getClassMetadata(Country::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(City::CLASSNAME, 'c');
        $rsm->addJoinedEntityFromClassMetadata(State::CLASSNAME, 's', 'c', 'state', array('id'=>'state_id', 'name'=>'state_name'));
        $rsm->addJoinedEntityFromClassMetadata(Country::CLASSNAME, 'co', 's', 'country', array('id'=>'country_id', 'name'=>'country_name'));

        for ($i = 0; $i < 4; $i++) {
            $country  = new Country("Country $i");
            $state    = new State("State $i", $country);
            $city     = new City("City $i", $state);

            $result[] = $city;

            $cityClass->setFieldValue($city, 'id', $i);
            $stateClass->setFieldValue($state, 'id', $i*2);
            $countryClass->setFieldValue($country, 'id', $i*3);

            $uow->registerManaged($country, array('id' => $country->getId()), array('name' => $country->getName()));
            $uow->registerManaged($state, array('id' => $state->getId()), array('name' => $city->getName(), 'country' => $country));
            $uow->registerManaged($city, array('id' => $city->getId()), array('name' => $city->getName(), 'state' => $state));
        }

        $this->assertTrue($this->queryCache->put($key, $rsm, $result));
        $this->assertArrayHasKey('put', $this->region->calls);
        $this->assertCount(9, $this->region->calls['put']);

        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][0]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][1]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][2]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][3]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][4]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][5]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][6]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][7]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCacheKey', $this->region->calls['put'][8]['key']);
    }

    public function testPutToOneAssociationNullQueryResult()
    {
        $result     = array();
        $uow        = $this->em->getUnitOfWork();
        $key        = new QueryCacheKey('query.key1', 0);
        $rsm        = new ResultSetMappingBuilder($this->em);
        $cityClass  = $this->em->getClassMetadata(City::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(City::CLASSNAME, 'c');
        $rsm->addJoinedEntityFromClassMetadata(State::CLASSNAME, 's', 'c', 'state', array('id'=>'state_id', 'name'=>'state_name'));

        for ($i = 0; $i < 4; $i++) {
            $city     = new City("City $i", null);
            $result[] = $city;

            $cityClass->setFieldValue($city, 'id', $i);

            $uow->registerManaged($city, array('id' => $city->getId()), array('name' => $city->getName(), 'state' => null));
        }

        $this->assertTrue($this->queryCache->put($key, $rsm, $result));
        $this->assertArrayHasKey('put', $this->region->calls);
        $this->assertCount(5, $this->region->calls['put']);

        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][0]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][1]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][2]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\EntityCacheKey', $this->region->calls['put'][3]['key']);
        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCacheKey', $this->region->calls['put'][4]['key']);
    }

    public function testPutToManyAssociationQueryResult()
    {
        $result     = array();
        $uow        = $this->em->getUnitOfWork();
        $key        = new QueryCacheKey('query.key1', 0);
        $rsm        = new ResultSetMappingBuilder($this->em);
        $cityClass  = $this->em->getClassMetadata(City::CLASSNAME);
        $stateClass = $this->em->getClassMetadata(State::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(State::CLASSNAME, 's');
        $rsm->addJoinedEntityFromClassMetadata(City::CLASSNAME, 'c', 's', 'cities', array('id'=>'c_id', 'name'=>'c_name'));

        for ($i = 0; $i < 4; $i++) {
            $state    = new State("State $i");
            $city1    = new City("City 1", $state);
            $city2    = new City("City 2", $state);
            $result[] = $state;

            $cityClass->setFieldValue($city1, 'id', $i + 11);
            $cityClass->setFieldValue($city2, 'id', $i + 22);
            $stateClass->setFieldValue($state, 'id', $i);

            $state->addCity($city1);
            $state->addCity($city2);

            $uow->registerManaged($city1, array('id' => $city1->getId()), array('name' => $city1->getName(), 'state' => $state));
            $uow->registerManaged($city2, array('id' => $city2->getId()), array('name' => $city2->getName(), 'state' => $state));
            $uow->registerManaged($state, array('id' => $state->getId()), array('name' => $state->getName(), 'cities' => $state->getCities()));
        }

        $this->assertTrue($this->queryCache->put($key, $rsm, $result));
        $this->assertArrayHasKey('put', $this->region->calls);
        $this->assertCount(13, $this->region->calls['put']);
    }

    public function testGetBasicQueryResult()
    {
        $rsm   = new ResultSetMappingBuilder($this->em);
        $key   = new QueryCacheKey('query.key1', 0);
        $entry = new QueryCacheEntry(array(
            array('identifier' => array('id' => 1)),
            array('identifier' => array('id' => 2))
        ));

        $data = array(
            array('id'=>1, 'name' => 'Foo'),
            array('id'=>2, 'name' => 'Bar')
        );

        $this->region->addReturn('get', $entry);
        $this->region->addReturn('get', new EntityCacheEntry(Country::CLASSNAME, $data[0]));
        $this->region->addReturn('get', new EntityCacheEntry(Country::CLASSNAME, $data[1]));

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        $result = $this->queryCache->get($key, $rsm);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Country::CLASSNAME, $result[0]);
        $this->assertInstanceOf(Country::CLASSNAME, $result[1]);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals(2, $result[1]->getId());
        $this->assertEquals('Foo', $result[0]->getName());
        $this->assertEquals('Bar', $result[1]->getName());
    }

    public function testCancelPutResultIfEntityPutFails()
    {
        $result   = array();
        $key      = new QueryCacheKey('query.key1', 0);
        $rsm      = new ResultSetMappingBuilder($this->em);
        $metadata = $this->em->getClassMetadata(Country::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        for ($i = 0; $i < 4; $i++) {
            $name       = "Country $i";
            $entity     = new Country($name);
            $result[]   = $entity;

            $metadata->setFieldValue($entity, 'id', $i);
            $this->em->getUnitOfWork()->registerManaged($entity, array('id' => $i), array('name' => $name));
        }

        $this->region->addReturn('put', false);

        $this->assertFalse($this->queryCache->put($key, $rsm, $result));
        $this->assertArrayHasKey('put', $this->region->calls);
        $this->assertCount(1, $this->region->calls['put']);
    }

    public function testCancelPutResultIfAssociationEntityPutFails()
    {
        $result     = array();
        $uow        = $this->em->getUnitOfWork();
        $key        = new QueryCacheKey('query.key1', 0);
        $rsm        = new ResultSetMappingBuilder($this->em);
        $cityClass  = $this->em->getClassMetadata(City::CLASSNAME);
        $stateClass = $this->em->getClassMetadata(State::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(City::CLASSNAME, 'c');
        $rsm->addJoinedEntityFromClassMetadata(State::CLASSNAME, 's', 'c', 'state', array('id'=>'state_id', 'name'=>'state_name'));

        $state    = new State("State 1");
        $city     = new City("City 2", $state);
        $result[] = $city;

        $cityClass->setFieldValue($city, 'id', 1);
        $stateClass->setFieldValue($state, 'id', 11);

        $uow->registerManaged($state, array('id' => $state->getId()), array('name' => $city->getName()));
        $uow->registerManaged($city, array('id' => $city->getId()), array('name' => $city->getName(), 'state' => $state));

        $this->region->addReturn('put', true);  // put root entity
        $this->region->addReturn('put', false); // association fails

        $this->assertFalse($this->queryCache->put($key, $rsm, $result));
    }

    public function testCancelPutToManyAssociationQueryResult()
    {
        $result     = array();
        $uow        = $this->em->getUnitOfWork();
        $key        = new QueryCacheKey('query.key1', 0);
        $rsm        = new ResultSetMappingBuilder($this->em);
        $cityClass  = $this->em->getClassMetadata(City::CLASSNAME);
        $stateClass = $this->em->getClassMetadata(State::CLASSNAME);

        $rsm->addRootEntityFromClassMetadata(State::CLASSNAME, 's');
        $rsm->addJoinedEntityFromClassMetadata(City::CLASSNAME, 'c', 's', 'cities', array('id'=>'c_id', 'name'=>'c_name'));

        $state    = new State("State");
        $city1    = new City("City 1", $state);
        $city2    = new City("City 2", $state);
        $result[] = $state;

        $stateClass->setFieldValue($state, 'id', 1);
        $cityClass->setFieldValue($city1, 'id', 11);
        $cityClass->setFieldValue($city2, 'id', 22);

        $state->addCity($city1);
        $state->addCity($city2);

        $uow->registerManaged($city1, array('id' => $city1->getId()), array('name' => $city1->getName(), 'state' => $state));
        $uow->registerManaged($city2, array('id' => $city2->getId()), array('name' => $city2->getName(), 'state' => $state));
        $uow->registerManaged($state, array('id' => $state->getId()), array('name' => $state->getName(), 'cities' => $state->getCities()));

        $this->region->addReturn('put', true);  // put root entity
        $this->region->addReturn('put', false); // collection association fails

        $this->assertFalse($this->queryCache->put($key, $rsm, $result));
        $this->assertArrayHasKey('put', $this->region->calls);
        $this->assertCount(2, $this->region->calls['put']);
    }

    public function testIgnoreCacheNonGetMode()
    {
        $rsm   = new ResultSetMappingBuilder($this->em);
        $key   = new QueryCacheKey('query.key1', 0, Cache::MODE_PUT);
        $entry = new QueryCacheEntry(array(
            array('identifier' => array('id' => 1)),
            array('identifier' => array('id' => 2))
        ));

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        $this->region->addReturn('get', $entry);

        $this->assertNull($this->queryCache->get($key, $rsm));
    }

    public function testIgnoreCacheNonPutMode()
    {
        $result   = array();
        $rsm      = new ResultSetMappingBuilder($this->em);
        $metadata = $this->em->getClassMetadata(Country::CLASSNAME);
        $key      = new QueryCacheKey('query.key1', 0, Cache::MODE_GET);

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        for ($i = 0; $i < 4; $i++) {
            $name       = "Country $i";
            $entity     = new Country($name);
            $result[]   = $entity;

            $metadata->setFieldValue($entity, 'id', $i);
            $this->em->getUnitOfWork()->registerManaged($entity, array('id' => $i), array('name' => $name));
        }

        $this->assertFalse($this->queryCache->put($key, $rsm, $result));
    }

    public function testGetShouldIgnoreOldQueryCacheEntryResult()
    {
        $rsm   = new ResultSetMappingBuilder($this->em);
        $key   = new QueryCacheKey('query.key1', 50);
        $entry = new QueryCacheEntry(array(
            array('identifier' => array('id' => 1)),
            array('identifier' => array('id' => 2))
        ));
        $entities = array(
            array('id'=>1, 'name' => 'Foo'),
            array('id'=>2, 'name' => 'Bar')
        );

        $entry->time = time() - 100;

        $this->region->addReturn('get', $entry);
        $this->region->addReturn('get', new EntityCacheEntry(Country::CLASSNAME, $entities[0]));
        $this->region->addReturn('get', new EntityCacheEntry(Country::CLASSNAME, $entities[1]));

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        $this->assertNull($this->queryCache->get($key, $rsm));
    }

    public function testGetShouldIgnoreNonQueryCacheEntryResult()
    {
        $rsm   = new ResultSetMappingBuilder($this->em);
        $key   = new QueryCacheKey('query.key1', 0);
        $entry = new \ArrayObject(array(
            array('identifier' => array('id' => 1)),
            array('identifier' => array('id' => 2))
        ));

        $data = array(
            array('id'=>1, 'name' => 'Foo'),
            array('id'=>2, 'name' => 'Bar')
        );

        $this->region->addReturn('get', $entry);
        $this->region->addReturn('get', new EntityCacheEntry(Country::CLASSNAME, $data[0]));
        $this->region->addReturn('get', new EntityCacheEntry(Country::CLASSNAME, $data[1]));

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        $this->assertNull($this->queryCache->get($key, $rsm));
    }

    public function testGetShouldIgnoreMissingEntityQueryCacheEntry()
    {
        $rsm   = new ResultSetMappingBuilder($this->em);
        $key   = new QueryCacheKey('query.key1', 0);
        $entry = new QueryCacheEntry(array(
            array('identifier' => array('id' => 1)),
            array('identifier' => array('id' => 2))
        ));

        $this->region->addReturn('get', $entry);
        $this->region->addReturn('get', null);

        $rsm->addRootEntityFromClassMetadata(Country::CLASSNAME, 'c');

        $this->assertNull($this->queryCache->get($key, $rsm));
    }

    /**
     * @expectedException Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Second level cache does not support scalar results.
     */
    public function testScalarResultException()
    {
        $result   = array();
        $key      = new QueryCacheKey('query.key1', 0);
        $rsm      = new ResultSetMappingBuilder($this->em);

        $rsm->addScalarResult('id', 'u', 'integer');

        $this->queryCache->put($key, $rsm, $result);
    }

    /**
     * @expectedException Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Second level cache does not support multiple root entities.
     */
    public function testSupportMultipleRootEntitiesException()
    {
        $result   = array();
        $key      = new QueryCacheKey('query.key1', 0);
        $rsm      = new ResultSetMappingBuilder($this->em);

        $rsm->addEntityResult('Doctrine\Tests\Models\Cache\City', 'e1');
        $rsm->addEntityResult('Doctrine\Tests\Models\Cache\State', 'e2');

        $this->queryCache->put($key, $rsm, $result);
    }

    /**
     * @expectedException Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Entity "Doctrine\Tests\Models\Generic\BooleanModel" not configured as part of the second-level cache.
     */
    public function testNotCacheableEntityException()
    {
        $result    = array();
        $key       = new QueryCacheKey('query.key1', 0);
        $rsm       = new ResultSetMappingBuilder($this->em);
        $className = 'Doctrine\Tests\Models\Generic\BooleanModel';

        $rsm->addRootEntityFromClassMetadata($className, 'c');

        for ($i = 0; $i < 4; $i++) {
            $entity  = new BooleanModel();
            $boolean = ($i % 2 === 0);

            $entity->id             = $i;
            $entity->booleanField   = $boolean;
            $result[]               = $entity;

            $this->em->getUnitOfWork()->registerManaged($entity, array('id' => $i), array('booleanField' => $boolean));
        }

        $this->assertFalse($this->queryCache->put($key, $rsm, $result));
    }

}

class CacheFactoryDefaultQueryCacheTest extends \Doctrine\ORM\Cache\DefaultCacheFactory
{
    private $queryCache;
    private $region;

    public function __construct(DefaultQueryCache $queryCache, CacheRegionMock $region)
    {
        $this->queryCache = $queryCache;
        $this->region     = $region;
    }

    public function buildQueryCache(EntityManagerInterface $em, $regionName = null)
    {
        return $this->queryCache;
    }

    public function getRegion(array $cache)
    {
        return $this->region;
    }

    public function getTimestampRegion()
    {
        return new \Doctrine\Tests\Mocks\TimestampRegionMock();
    }
}
