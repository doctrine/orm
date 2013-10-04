<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;

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

        $this->assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[1]->getId()));
    }

    public function testRepositoryCacheFindAllInvalidation()
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

        $this->assertCount(2, $countries);
        $this->assertInstanceOf(Country::CLASSNAME, $countries[0]);
        $this->assertInstanceOf(Country::CLASSNAME, $countries[1]);
        
        $country = new Country('foo');

        $this->_em->persist($country);
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $this->assertCount(3, $repository->findAll());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $country = $repository->find($country->getId());
        
        $this->_em->remove($country);
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $this->assertCount(2, $repository->findAll());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
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

        $this->assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
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

        $this->assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));
    }

    public function testRepositoryCacheFindAllToOneAssociation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();

        $this->evictRegions();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        // load from database
        $repository = $this->_em->getRepository(State::CLASSNAME);
        $queryCount = $this->getCurrentQueryCount();
        $entities   = $repository->findAll();

        $this->assertCount(4, $entities);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertInstanceOf(State::CLASSNAME, $entities[0]);
        $this->assertInstanceOf(State::CLASSNAME, $entities[1]);
        $this->assertInstanceOf(Country::CLASSNAME, $entities[0]->getCountry());
        $this->assertInstanceOf(Country::CLASSNAME, $entities[0]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[0]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[1]->getCountry());

        // load from cache
        $queryCount = $this->getCurrentQueryCount();
        $entities   = $repository->findAll();

        $this->assertCount(4, $entities);
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(State::CLASSNAME, $entities[0]);
        $this->assertInstanceOf(State::CLASSNAME, $entities[1]);
        $this->assertInstanceOf(Country::CLASSNAME, $entities[0]->getCountry());
        $this->assertInstanceOf(Country::CLASSNAME, $entities[1]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[0]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[1]->getCountry());

        // invalidate cache
        $this->_em->persist(new State('foo', $this->_em->find(Country::CLASSNAME, $this->countries[0]->getId())));
        $this->_em->flush();
        $this->_em->clear();

        // load from database
        $queryCount = $this->getCurrentQueryCount();
        $entities   = $repository->findAll();

        $this->assertCount(5, $entities);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertInstanceOf(State::CLASSNAME, $entities[0]);
        $this->assertInstanceOf(State::CLASSNAME, $entities[1]);
        $this->assertInstanceOf(Country::CLASSNAME, $entities[0]->getCountry());
        $this->assertInstanceOf(Country::CLASSNAME, $entities[1]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[0]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[1]->getCountry());

        // load from cache
        $queryCount = $this->getCurrentQueryCount();
        $entities   = $repository->findAll();

        $this->assertCount(5, $entities);
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(State::CLASSNAME, $entities[0]);
        $this->assertInstanceOf(State::CLASSNAME, $entities[1]);
        $this->assertInstanceOf(Country::CLASSNAME, $entities[0]->getCountry());
        $this->assertInstanceOf(Country::CLASSNAME, $entities[1]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[0]->getCountry());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $entities[1]->getCountry());
    }
}