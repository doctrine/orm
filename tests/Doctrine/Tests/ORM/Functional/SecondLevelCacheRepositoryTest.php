<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Country;

/**
 * @group DDC-2183
 */
class SecondLevelCacheRepositoryTest extends SecondLevelCacheAbstractTest
{
    public function testRepositoryCacheFind()
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $repository = $this->_em->getRepository(Country::CLASSNAME);
        $country1   = $repository->find($this->countries[0]->getId());
        $country2   = $repository->find($this->countries[1]->getId());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Country::CLASSNAME, $country1);
        $this->assertInstanceOf(Country::CLASSNAME, $country2);

        $this->assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(0, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::CLASSNAME)));

    }

    public function testRepositoryCacheFindAll()
    {
        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));

        $repository = $this->_em->getRepository(Country::CLASSNAME);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertCount(2, $repository->findAll());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $queryCount = $this->getCurrentQueryCount();
        $countries  = $repository->findAll();

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Country::CLASSNAME, $countries[0]);
        $this->assertInstanceOf(Country::CLASSNAME, $countries[1]);

        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));
    }

    public function testRepositoryCacheFindBy()
    {
        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));

        $criteria   = array('name'=>$this->countries[0]->getName());
        $repository = $this->_em->getRepository(Country::CLASSNAME);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertCount(1, $repository->findBy($criteria));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $queryCount = $this->getCurrentQueryCount();
        $countries  = $repository->findBy($criteria);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertCount(1, $countries);
        $this->assertInstanceOf(Country::CLASSNAME, $countries[0]);

        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
    }

    public function testRepositoryCacheFindOneBy()
    {
        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));

        $criteria   = array('name'=>$this->countries[0]->getName());
        $repository = $this->_em->getRepository(Country::CLASSNAME);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertNotNull($repository->findOneBy($criteria));
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $queryCount = $this->getCurrentQueryCount();
        $country    = $repository->findOneBy($criteria);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Country::CLASSNAME, $country);

        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
    }
}