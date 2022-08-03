<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use ReflectionMethod;

/**
 * @group DDC-2183
 */
class SecondLevelCacheQueryCacheTest extends SecondLevelCacheAbstractTest
{
    public function testBasicQueryCache(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $result1    = $this->_em->createQuery($dql)->setCacheable(true)->getResult();

        $this->assertCount(2, $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[1]->getId(), $result1[1]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());
        $this->assertEquals($this->countries[1]->getName(), $result1[1]->getName());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertInstanceOf(Country::class, $result2[0]);
        $this->assertInstanceOf(Country::class, $result2[1]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[1]->getId(), $result2[1]->getId());

        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
        $this->assertEquals($result1[1]->getName(), $result2[1]->getName());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));
    }

    public function testQueryCacheModeGet(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();
        $this->evictRegions();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $queryGet   = $this->_em->createQuery($dql)
            ->setCacheMode(Cache::MODE_GET)
            ->setCacheable(true);

        // MODE_GET should never add items to the cache.
        $this->assertCount(2, $queryGet->getResult());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertCount(2, $queryGet->getResult());
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());

        $result = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(2, $result);
        $this->assertEquals($queryCount + 3, $this->getCurrentQueryCount());

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        // MODE_GET should read items if exists.
        $this->assertCount(2, $queryGet->getResult());
        $this->assertEquals($queryCount + 3, $this->getCurrentQueryCount());
    }

    public function testQueryCacheModePut(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();
        $this->evictRegions();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $result     = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->assertCount(2, $result);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $queryPut = $this->_em->createQuery($dql)
            ->setCacheMode(Cache::MODE_PUT)
            ->setCacheable(true);

        // MODE_PUT should never read itens from cache.
        $this->assertCount(2, $queryPut->getResult());
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->assertCount(2, $queryPut->getResult());
        $this->assertEquals($queryCount + 3, $this->getCurrentQueryCount());
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));
    }

    public function testQueryCacheModeRefresh(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();
        $this->evictRegions();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $region     = $this->cache->getEntityCacheRegion(Country::class);
        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $result     = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->assertCount(2, $result);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $countryId1   = $this->countries[0]->getId();
        $countryId2   = $this->countries[1]->getId();
        $countryName1 = $this->countries[0]->getName();
        $countryName2 = $this->countries[1]->getName();

        $key1   = new EntityCacheKey(Country::class, ['id' => $countryId1]);
        $key2   = new EntityCacheKey(Country::class, ['id' => $countryId2]);
        $entry1 = new EntityCacheEntry(Country::class, ['id' => $countryId1, 'name' => 'outdated']);
        $entry2 = new EntityCacheEntry(Country::class, ['id' => $countryId2, 'name' => 'outdated']);

        $region->put($key1, $entry1);
        $region->put($key2, $entry2);
        $this->_em->clear();

        $queryRefresh = $this->_em->createQuery($dql)
            ->setCacheMode(Cache::MODE_REFRESH)
            ->setCacheable(true);

        // MODE_REFRESH should never read itens from cache.
        $result1 = $queryRefresh->getResult();
        $this->assertCount(2, $result1);
        $this->assertEquals($countryName1, $result1[0]->getName());
        $this->assertEquals($countryName2, $result1[1]->getName());
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());

        $this->_em->clear();

        $result2 = $queryRefresh->getResult();
        $this->assertCount(2, $result2);
        $this->assertEquals($countryName1, $result2[0]->getName());
        $this->assertEquals($countryName2, $result2[1]->getName());
        $this->assertEquals($queryCount + 3, $this->getCurrentQueryCount());
    }

    public function testBasicQueryCachePutEntityCache(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $result1    = $this->_em->createQuery($dql)->setCacheable(true)->getResult();

        $this->assertCount(2, $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[1]->getId(), $result1[1]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());
        $this->assertEquals($this->countries[1]->getName(), $result1[1]->getName());

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertInstanceOf(Country::class, $result2[0]);
        $this->assertInstanceOf(Country::class, $result2[1]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[1]->getId(), $result2[1]->getId());

        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
        $this->assertEquals($result1[1]->getName(), $result2[1]->getName());

        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));
    }

    /**
     * @group 5854
     */
    public function testMultipleNestedDQLAliases(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $queryRegionName      = $this->getDefaultQueryRegionName();
        $cityRegionName       = $this->getEntityRegion(City::class);
        $stateRegionName      = $this->getEntityRegion(State::class);
        $attractionRegionName = $this->getEntityRegion(Attraction::class);

        $this->secondLevelCacheLogger->clearStats();
        $this->evictRegions();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT s, c, a FROM Doctrine\Tests\Models\Cache\State s JOIN s.cities c JOIN c.attractions a';
        $result1    = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(2, $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[2]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[3]->getId()));

        $this->assertTrue($this->cache->containsEntity(Attraction::class, $this->attractions[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Attraction::class, $this->attractions[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(Attraction::class, $this->attractions[2]->getId()));
        $this->assertTrue($this->cache->containsEntity(Attraction::class, $this->attractions[3]->getId()));

        $this->assertInstanceOf(State::class, $result1[0]);
        $this->assertInstanceOf(State::class, $result1[1]);

        $this->assertCount(2, $result1[0]->getCities());
        $this->assertCount(2, $result1[1]->getCities());

        $this->assertInstanceOf(City::class, $result1[0]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result1[0]->getCities()->get(1));
        $this->assertInstanceOf(City::class, $result1[1]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result1[1]->getCities()->get(1));

        $this->assertCount(2, $result1[0]->getCities()->get(0)->getAttractions());
        $this->assertCount(2, $result1[0]->getCities()->get(1)->getAttractions());
        $this->assertCount(2, $result1[1]->getCities()->get(0)->getAttractions());
        $this->assertCount(1, $result1[1]->getCities()->get(1)->getAttractions());

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(2, $result2);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertInstanceOf(State::class, $result2[0]);
        $this->assertInstanceOf(State::class, $result2[1]);

        $this->assertCount(2, $result2[0]->getCities());
        $this->assertCount(2, $result2[1]->getCities());

        $this->assertInstanceOf(City::class, $result2[0]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result2[0]->getCities()->get(1));
        $this->assertInstanceOf(City::class, $result2[1]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result2[1]->getCities()->get(1));

        $this->assertCount(2, $result2[0]->getCities()->get(0)->getAttractions());
        $this->assertCount(2, $result2[0]->getCities()->get(1)->getAttractions());
        $this->assertCount(2, $result2[1]->getCities()->get(0)->getAttractions());
        $this->assertCount(1, $result2[1]->getCities()->get(1)->getAttractions());
    }

    public function testBasicQueryParams(): void
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $name       = $this->countries[0]->getName();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c WHERE c.name = :name';
        $result1    = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->setParameter('name', $name)
                ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)->setCacheable(true)
                ->setParameter('name', $name)
                ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(1, $result2);

        $this->assertInstanceOf(Country::class, $result2[0]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
    }

    public function testLoadFromDatabaseWhenEntityMissing(): void
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $result1    = $this->_em->createQuery($dql)->setCacheable(true)->getResult();

        $this->assertCount(2, $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[1]->getId(), $result1[1]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());
        $this->assertEquals($this->countries[1]->getName(), $result1[1]->getName());

        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->cache->evictEntity(Country::class, $result1[0]->getId());
        $this->assertFalse($this->cache->containsEntity(Country::class, $result1[0]->getId()));

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(5, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertInstanceOf(Country::class, $result2[0]);
        $this->assertInstanceOf(Country::class, $result2[1]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[1]->getId(), $result2[1]->getId());

        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
        $this->assertEquals($result1[1]->getName(), $result2[1]->getName());

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    public function testBasicQueryFetchJoinsOneToMany(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->evictRegions();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT s, c FROM Doctrine\Tests\Models\Cache\State s JOIN s.cities c';
        $result1    = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertInstanceOf(State::class, $result1[0]);
        $this->assertInstanceOf(State::class, $result1[1]);
        $this->assertCount(2, $result1[0]->getCities());
        $this->assertCount(2, $result1[1]->getCities());

        $this->assertInstanceOf(City::class, $result1[0]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result1[0]->getCities()->get(1));
        $this->assertInstanceOf(City::class, $result1[1]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result1[1]->getCities()->get(1));

        $this->assertNotNull($result1[0]->getCities()->get(0)->getId());
        $this->assertNotNull($result1[0]->getCities()->get(1)->getId());
        $this->assertNotNull($result1[1]->getCities()->get(0)->getId());
        $this->assertNotNull($result1[1]->getCities()->get(1)->getId());

        $this->assertNotNull($result1[0]->getCities()->get(0)->getName());
        $this->assertNotNull($result1[0]->getCities()->get(1)->getName());
        $this->assertNotNull($result1[1]->getCities()->get(0)->getName());
        $this->assertNotNull($result1[1]->getCities()->get(1)->getName());

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertInstanceOf(State::class, $result2[0]);
        $this->assertInstanceOf(State::class, $result2[1]);
        $this->assertCount(2, $result2[0]->getCities());
        $this->assertCount(2, $result2[1]->getCities());

        $this->assertInstanceOf(City::class, $result2[0]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result2[0]->getCities()->get(1));
        $this->assertInstanceOf(City::class, $result2[1]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result2[1]->getCities()->get(1));

        $this->assertNotNull($result2[0]->getCities()->get(0)->getId());
        $this->assertNotNull($result2[0]->getCities()->get(1)->getId());
        $this->assertNotNull($result2[1]->getCities()->get(0)->getId());
        $this->assertNotNull($result2[1]->getCities()->get(1)->getId());

        $this->assertNotNull($result2[0]->getCities()->get(0)->getName());
        $this->assertNotNull($result2[0]->getCities()->get(1)->getName());
        $this->assertNotNull($result2[1]->getCities()->get(0)->getName());
        $this->assertNotNull($result2[1]->getCities()->get(1)->getName());

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testBasicQueryFetchJoinsManyToOne(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c, s FROM Doctrine\Tests\Models\Cache\City c JOIN c.state s';
        $result1    = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertCount(4, $result1);
        $this->assertInstanceOf(City::class, $result1[0]);
        $this->assertInstanceOf(City::class, $result1[1]);
        $this->assertInstanceOf(State::class, $result1[0]->getState());
        $this->assertInstanceOf(State::class, $result1[1]->getState());

        $this->assertTrue($this->cache->containsEntity(City::class, $result1[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $result1[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $result1[0]->getState()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $result1[1]->getState()->getId()));

        $this->assertEquals(7, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));
        $this->assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(City::class)));

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $result2 = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertCount(4, $result1);
        $this->assertInstanceOf(City::class, $result2[0]);
        $this->assertInstanceOf(City::class, $result2[1]);
        $this->assertInstanceOf(State::class, $result2[0]->getState());
        $this->assertInstanceOf(State::class, $result2[1]->getState());

        $this->assertNotNull($result2[0]->getId());
        $this->assertNotNull($result2[0]->getId());
        $this->assertNotNull($result2[1]->getState()->getId());
        $this->assertNotNull($result2[1]->getState()->getId());

        $this->assertNotNull($result2[0]->getName());
        $this->assertNotNull($result2[0]->getName());
        $this->assertNotNull($result2[1]->getState()->getName());
        $this->assertNotNull($result2[1]->getState()->getName());

        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
        $this->assertEquals($result1[1]->getName(), $result2[1]->getName());
        $this->assertEquals($result1[0]->getState()->getName(), $result2[0]->getState()->getName());
        $this->assertEquals($result1[1]->getState()->getName(), $result2[1]->getState()->getName());

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testReloadQueryIfToOneIsNotFound(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c, s FROM Doctrine\Tests\Models\Cache\City c JOIN c.state s';
        $result1    = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertCount(4, $result1);
        $this->assertInstanceOf(City::class, $result1[0]);
        $this->assertInstanceOf(City::class, $result1[1]);
        $this->assertInstanceOf(State::class, $result1[0]->getState());
        $this->assertInstanceOf(State::class, $result1[1]->getState());

        $this->assertTrue($this->cache->containsEntity(City::class, $result1[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $result1[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $result1[0]->getState()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $result1[1]->getState()->getId()));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->clear();

        $this->cache->evictEntityRegion(State::class);

        $result2 = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertCount(4, $result1);
        $this->assertInstanceOf(City::class, $result2[0]);
        $this->assertInstanceOf(City::class, $result2[1]);
        $this->assertInstanceOf(State::class, $result2[0]->getState());
        $this->assertInstanceOf(State::class, $result2[1]->getState());

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    public function testReloadQueryIfToManyAssociationItemIsNotFound(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->evictRegions();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT s, c FROM Doctrine\Tests\Models\Cache\State s JOIN s.cities c';
        $result1    = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertInstanceOf(State::class, $result1[0]);
        $this->assertInstanceOf(State::class, $result1[1]);
        $this->assertCount(2, $result1[0]->getCities());
        $this->assertCount(2, $result1[1]->getCities());

        $this->assertInstanceOf(City::class, $result1[0]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result1[0]->getCities()->get(1));
        $this->assertInstanceOf(City::class, $result1[1]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result1[1]->getCities()->get(1));

        $this->_em->clear();

        $this->cache->evictEntityRegion(City::class);

        $result2 = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertInstanceOf(State::class, $result2[0]);
        $this->assertInstanceOf(State::class, $result2[1]);
        $this->assertCount(2, $result2[0]->getCities());
        $this->assertCount(2, $result2[1]->getCities());

        $this->assertInstanceOf(City::class, $result2[0]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result2[0]->getCities()->get(1));
        $this->assertInstanceOf(City::class, $result2[1]->getCities()->get(0));
        $this->assertInstanceOf(City::class, $result2[1]->getCities()->get(1));

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    public function testBasicNativeQueryCache(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Country::class, 'c');
        $rsm->addFieldResult('c', 'name', 'name');
        $rsm->addFieldResult('c', 'id', 'id');

        $queryCount = $this->getCurrentQueryCount();
        $sql        = 'SELECT id, name FROM cache_country';
        $result1    = $this->_em->createNativeQuery($sql, $rsm)->setCacheable(true)->getResult();

        $this->assertCount(2, $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[1]->getId(), $result1[1]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());
        $this->assertEquals($this->countries[1]->getName(), $result1[1]->getName());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->_em->clear();

        $result2 = $this->_em->createNativeQuery($sql, $rsm)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertInstanceOf(Country::class, $result2[0]);
        $this->assertInstanceOf(Country::class, $result2[1]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[1]->getId(), $result2[1]->getId());

        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
        $this->assertEquals($result1[1]->getName(), $result2[1]->getName());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));
    }

    public function testQueryDependsOnFirstAndMaxResultResult(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $result1    = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->setFirstResult(1)
            ->setMaxResults(1)
            ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->setFirstResult(2)
            ->setMaxResults(1)
            ->getResult();

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());

        $this->_em->clear();

        $result3 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 3, $this->getCurrentQueryCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getMissCount());
    }

    public function testQueryCacheLifetime(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $getHash = static function (AbstractQuery $query) {
            $method = new ReflectionMethod($query, 'getHash');
            $method->setAccessible(true);

            return $method->invoke($query);
        };

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $query      = $this->_em->createQuery($dql);
        $result1    = $query->setCacheable(true)
            ->setLifetime(3600)
            ->getResult();

        $this->assertNotEmpty($result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->_em->clear();

        $key   = new QueryCacheKey($getHash($query), 3600);
        $entry = $this->cache->getQueryCache()
            ->getRegion()
            ->get($key);

        $this->assertInstanceOf(Cache\QueryCacheEntry::class, $entry);
        $entry->time /= 2;

        $this->cache->getQueryCache()
            ->getRegion()
            ->put($key, $entry);

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->setLifetime(3600)
            ->getResult();

        $this->assertNotEmpty($result2);
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
    }

    public function testQueryCacheRegion(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $query      = $this->_em->createQuery($dql);

        $query1  = clone $query;
        $result1 = $query1->setCacheable(true)
            ->setCacheRegion('foo_region')
            ->getResult();

        $this->assertNotEmpty($result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals(0, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount('foo_region'));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount('foo_region'));

        $query2  = clone $query;
        $result2 = $query2->setCacheable(true)
            ->setCacheRegion('bar_region')
            ->getResult();

        $this->assertNotEmpty($result2);
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertEquals(0, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount('bar_region'));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount('bar_region'));

        $query3  = clone $query;
        $result3 = $query3->setCacheable(true)
            ->setCacheRegion('foo_region')
            ->getResult();

        $this->assertNotEmpty($result3);
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount('foo_region'));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount('foo_region'));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount('foo_region'));

        $query4  = clone $query;
        $result4 = $query4->setCacheable(true)
            ->setCacheRegion('bar_region')
            ->getResult();

        $this->assertNotEmpty($result3);
        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertEquals(6, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount('bar_region'));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount('bar_region'));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount('bar_region'));
    }

    public function testResolveAssociationCacheEntry(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();
        $this->loadFixturesStates();

        $this->_em->clear();

        $stateId     = $this->states[0]->getId();
        $countryName = $this->states[0]->getCountry()->getName();
        $dql         = 'SELECT s FROM Doctrine\Tests\Models\Cache\State s WHERE s.id = :id';
        $query       = $this->_em->createQuery($dql);
        $queryCount  = $this->getCurrentQueryCount();

        $query1 = clone $query;
        $state1 = $query1
            ->setParameter('id', $stateId)
            ->setCacheable(true)
            ->setMaxResults(1)
            ->getSingleResult();

        $this->assertNotNull($state1);
        $this->assertNotNull($state1->getCountry());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertInstanceOf(State::class, $state1);
        $this->assertInstanceOf(Proxy::class, $state1->getCountry());
        $this->assertEquals($countryName, $state1->getCountry()->getName());
        $this->assertEquals($stateId, $state1->getId());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $query2     = clone $query;
        $state2     = $query2
            ->setParameter('id', $stateId)
            ->setCacheable(true)
            ->setMaxResults(1)
            ->getSingleResult();

        $this->assertNotNull($state2);
        $this->assertNotNull($state2->getCountry());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertInstanceOf(State::class, $state2);
        $this->assertInstanceOf(Proxy::class, $state2->getCountry());
        $this->assertEquals($countryName, $state2->getCountry()->getName());
        $this->assertEquals($stateId, $state2->getId());
    }

    public function testResolveToOneAssociationCacheEntry(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->evictRegions();

        $this->_em->clear();

        $cityId     = $this->cities[0]->getId();
        $dql        = 'SELECT c, s FROM Doctrine\Tests\Models\Cache\City c JOIN c.state s WHERE c.id = :id';
        $query      = $this->_em->createQuery($dql);
        $queryCount = $this->getCurrentQueryCount();

        $query1 = clone $query;
        $city1  = $query1
            ->setParameter('id', $cityId)
            ->setCacheable(true)
            ->setMaxResults(1)
            ->getSingleResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertInstanceOf(City::class, $city1);
        $this->assertInstanceOf(State::class, $city1->getState());
        $this->assertInstanceOf(City::class, $city1->getState()->getCities()->get(0));
        $this->assertInstanceOf(State::class, $city1->getState()->getCities()->get(0)->getState());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $query2     = clone $query;
        $city2      = $query2
            ->setParameter('id', $cityId)
            ->setCacheable(true)
            ->setMaxResults(1)
            ->getSingleResult();

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertInstanceOf(City::class, $city2);
        $this->assertInstanceOf(State::class, $city2->getState());
        $this->assertInstanceOf(City::class, $city2->getState()->getCities()->get(0));
        $this->assertInstanceOf(State::class, $city2->getState()->getCities()->get(0)->getState());
    }

    public function testResolveToManyAssociationCacheEntry(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->evictRegions();

        $this->_em->clear();

        $stateId    = $this->states[0]->getId();
        $dql        = 'SELECT s, c FROM Doctrine\Tests\Models\Cache\State s JOIN s.cities c WHERE s.id = :id';
        $query      = $this->_em->createQuery($dql);
        $queryCount = $this->getCurrentQueryCount();

        $query1 = clone $query;
        $state1 = $query1
            ->setParameter('id', $stateId)
            ->setCacheable(true)
            ->setMaxResults(1)
            ->getSingleResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertInstanceOf(State::class, $state1);
        $this->assertInstanceOf(Proxy::class, $state1->getCountry());
        $this->assertInstanceOf(City::class, $state1->getCities()->get(0));
        $this->assertInstanceOf(State::class, $state1->getCities()->get(0)->getState());
        $this->assertSame($state1, $state1->getCities()->get(0)->getState());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $query2     = clone $query;
        $state2     = $query2
            ->setParameter('id', $stateId)
            ->setCacheable(true)
            ->setMaxResults(1)
            ->getSingleResult();

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertInstanceOf(State::class, $state2);
        $this->assertInstanceOf(Proxy::class, $state2->getCountry());
        $this->assertInstanceOf(City::class, $state2->getCities()->get(0));
        $this->assertInstanceOf(State::class, $state2->getCities()->get(0)->getState());
        $this->assertSame($state2, $state2->getCities()->get(0)->getState());
    }

    public function testHintClearEntityRegionUpdateStatement(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->_em->createQuery('DELETE Doctrine\Tests\Models\Cache\Country u WHERE u.id = 4')
            ->setHint(Query::HINT_CACHE_EVICT, true)
            ->execute();

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));
    }

    public function testHintClearEntityRegionDeleteStatement(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->_em->createQuery("UPDATE Doctrine\Tests\Models\Cache\Country u SET u.name = 'foo' WHERE u.id = 1")
            ->setHint(Query::HINT_CACHE_EVICT, true)
            ->execute();

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));
    }

    public function testCacheablePartialQueryException(): void
    {
        $this->expectException('Doctrine\ORM\Cache\CacheException');
        $this->expectExceptionMessage('Second level cache does not support partial entities.');
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->_em->createQuery('SELECT PARTIAL c.{id} FROM Doctrine\Tests\Models\Cache\Country c')
            ->setCacheable(true)
            ->getResult();
    }

    public function testCacheableForcePartialLoadHintQueryException(): void
    {
        $this->expectException('Doctrine\ORM\Cache\CacheException');
        $this->expectExceptionMessage('Second level cache does not support partial entities.');
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\Cache\Country c')
            ->setCacheable(true)
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    public function testNonCacheableQueryDeleteStatementException(): void
    {
        $this->expectException('Doctrine\ORM\Cache\CacheException');
        $this->expectExceptionMessage('Second-level cache query supports only select statements.');
        $this->_em->createQuery('DELETE Doctrine\Tests\Models\Cache\Country u WHERE u.id = 4')
            ->setCacheable(true)
            ->getResult();
    }

    public function testNonCacheableQueryUpdateStatementException(): void
    {
        $this->expectException('Doctrine\ORM\Cache\CacheException');
        $this->expectExceptionMessage('Second-level cache query supports only select statements.');
        $this->_em->createQuery("UPDATE Doctrine\Tests\Models\Cache\Country u SET u.name = 'foo' WHERE u.id = 4")
            ->setCacheable(true)
            ->getResult();
    }

    public function testQueryCacheShouldBeEvictedOnTimestampUpdate(): void
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT country FROM Doctrine\Tests\Models\Cache\Country country';

        $result1 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(2, $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->persist(new Country('France'));
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(3, $result2);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        foreach ($result2 as $entity) {
            $this->assertInstanceOf(Country::class, $entity);
        }
    }
}
