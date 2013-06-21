<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\City;

/**
 * @group DDC-2183
 */
class SecondLevelCacheQueryCacheTest extends SecondLevelCacheAbstractTest
{
    public function testBasicQueryCache()
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));

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

        $result2  = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]);
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[1]);

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
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::CLASSNAME)));
    }

    public function testBasicQueryCachePutEntityCache()
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

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));

        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::CLASSNAME)));

        $this->_em->clear();

        $result2  = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]);
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[1]);

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
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::CLASSNAME)));
    }

    public function testBasicQueryParamsParams()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));

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

        $result2  = $this->_em->createQuery($dql)->setCacheable(true)
                ->setParameter('name', $name)
                ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(1, $result2);

        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[0]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
    }

    public function testLoadFromDatabaseWhenEntityMissing()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $result1    = $this->_em->createQuery($dql)->setCacheable(true)->getResult();

        $this->assertCount(2, $result1);
        $this->assertEquals($queryCount + 1 , $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[1]->getId(), $result1[1]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());
        $this->assertEquals($this->countries[1]->getName(), $result1[1]->getName());
        
        $this->assertEquals(3, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->cache->evictEntity(Country::CLASSNAME, $result1[0]->getId());
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $result1[0]->getId()));

        $this->_em->clear();

        $result2  = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 2 , $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(5, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertNotInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]);
        $this->assertNotInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[1]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[1]->getId(), $result2[1]->getId());

        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
        $this->assertEquals($result1[1]->getName(), $result2[1]->getName());

        $this->assertEquals($queryCount + 2 , $this->getCurrentQueryCount());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Second level cache query does not support collections yet.
     */
    public function testBasicQueryFetchJoinsOneToMany()
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
        $this->assertInstanceOf(State::CLASSNAME, $result1[0]);
        $this->assertInstanceOf(State::CLASSNAME, $result1[1]);
        $this->assertCount(2, $result1[0]->getCities());
        $this->assertCount(2, $result1[1]->getCities());

        $this->assertInstanceOf(City::CLASSNAME, $result1[0]->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $result1[0]->getCities()->get(1));
        $this->assertInstanceOf(City::CLASSNAME, $result1[1]->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $result1[1]->getCities()->get(1));

        $this->assertNotNull($result1[0]->getCities()->get(0)->getId());
        $this->assertNotNull($result1[0]->getCities()->get(1)->getId());
        $this->assertNotNull($result1[1]->getCities()->get(0)->getId());
        $this->assertNotNull($result1[1]->getCities()->get(1)->getId());

        $this->assertNotNull($result1[0]->getCities()->get(0)->getName());
        $this->assertNotNull($result1[0]->getCities()->get(1)->getName());
        $this->assertNotNull($result1[1]->getCities()->get(0)->getName());
        $this->assertNotNull($result1[1]->getCities()->get(1)->getName());

        $this->_em->clear();

        $result2  = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]);
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]);
        $this->assertInstanceOf(State::CLASSNAME, $result2[0]);
        $this->assertInstanceOf(State::CLASSNAME, $result2[1]);
        $this->assertCount(2, $result2[0]->getCities());
        $this->assertCount(2, $result2[1]->getCities());

        $this->markTestIncomplete();

        $this->assertInstanceOf(City::CLASSNAME, $result2[0]->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $result2[0]->getCities()->get(1));
        $this->assertInstanceOf(City::CLASSNAME, $result2[1]->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $result2[1]->getCities()->get(1));

        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]->getCities()->get(0));
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]->getCities()->get(1));
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]->getCities()->get(0));
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]->getCities()->get(1));

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

    public function testBasicQueryFetchJoinsManyToOne()
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
        $this->assertInstanceOf(City::CLASSNAME, $result1[0]);
        $this->assertInstanceOf(City::CLASSNAME, $result1[1]);
        $this->assertInstanceOf(State::CLASSNAME, $result1[0]->getState());
        $this->assertInstanceOf(State::CLASSNAME, $result1[1]->getState());

        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $result1[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $result1[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $result1[0]->getState()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $result1[1]->getState()->getId()));

        $this->assertEquals(7, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::CLASSNAME)));
        $this->assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(City::CLASSNAME)));

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $result2  = $this->_em->createQuery($dql)
                ->setCacheable(true)
                ->getResult();

        $this->assertCount(4, $result1);
        $this->assertInstanceOf(City::CLASSNAME, $result2[0]);
        $this->assertInstanceOf(City::CLASSNAME, $result2[1]);
        $this->assertInstanceOf(State::CLASSNAME, $result2[0]->getState());
        $this->assertInstanceOf(State::CLASSNAME, $result2[1]->getState());

        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]);
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]);
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]->getState());
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]->getState());

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

    public function testBasicNativeQueryCache()
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(Country::CLASSNAME, 'c');
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

        $result2  = $this->_em->createNativeQuery($sql, $rsm)
            ->setCacheable(true)
            ->getResult();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(2, $result2);

        $this->assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getDefaultQueryRegionName()));

        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[0]);
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $result2[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[1]);

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
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::CLASSNAME)));
    }
}