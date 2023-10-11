<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Proxy\InternalProxy;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2183')]
class SecondLevelCacheRepositoryTest extends SecondLevelCacheFunctionalTestCase
{
    public function testRepositoryCacheFind(): void
    {
        $this->evictRegions();
        $this->loadFixturesCountries();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->getQueryLog()->reset()->enable();
        $repository = $this->_em->getRepository(Country::class);
        $country1   = $repository->find($this->countries[0]->getId());
        $country2   = $repository->find($this->countries[1]->getId());

        $this->assertQueryCount(0);

        self::assertInstanceOf(Country::class, $country1);
        self::assertInstanceOf(Country::class, $country2);

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(0, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::class)));
    }

    public function testRepositoryCacheFindAll(): void
    {
        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $repository = $this->_em->getRepository(Country::class);
        $this->getQueryLog()->reset()->enable();

        self::assertCount(2, $repository->findAll());
        $this->assertQueryCount(1);

        $this->getQueryLog()->reset()->enable();
        $countries = $repository->findAll();

        $this->assertQueryCount(0);

        self::assertInstanceOf(Country::class, $countries[0]);
        self::assertInstanceOf(Country::class, $countries[1]);

        self::assertEquals(3, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));
    }

    public function testRepositoryCacheFindAllInvalidation(): void
    {
        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $repository = $this->_em->getRepository(Country::class);
        $this->getQueryLog()->reset()->enable();

        self::assertCount(2, $repository->findAll());
        $this->assertQueryCount(1);

        $this->getQueryLog()->reset()->enable();
        $countries = $repository->findAll();

        $this->assertQueryCount(0);

        self::assertCount(2, $countries);
        self::assertInstanceOf(Country::class, $countries[0]);
        self::assertInstanceOf(Country::class, $countries[1]);

        $country = new Country('foo');

        $this->_em->persist($country);
        $this->_em->flush();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        self::assertCount(3, $repository->findAll());
        $this->assertQueryCount(1);

        $country = $repository->find($country->getId());

        $this->_em->remove($country);
        $this->_em->flush();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        self::assertCount(2, $repository->findAll());
        $this->assertQueryCount(1);
    }

    public function testRepositoryCacheFindBy(): void
    {
        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));

        $criteria   = ['name' => $this->countries[0]->getName()];
        $repository = $this->_em->getRepository(Country::class);
        $this->getQueryLog()->reset()->enable();

        self::assertCount(1, $repository->findBy($criteria));
        $this->assertQueryCount(1);

        $this->getQueryLog()->reset()->enable();
        $countries = $repository->findBy($criteria);

        $this->assertQueryCount(0);

        self::assertCount(1, $countries);
        self::assertInstanceOf(Country::class, $countries[0]);

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
    }

    public function testRepositoryCacheFindOneBy(): void
    {
        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));

        $criteria   = ['name' => $this->countries[0]->getName()];
        $repository = $this->_em->getRepository(Country::class);
        $this->getQueryLog()->reset()->enable();

        self::assertNotNull($repository->findOneBy($criteria));
        $this->assertQueryCount(1);

        $this->getQueryLog()->reset()->enable();
        $country = $repository->findOneBy($criteria);

        $this->assertQueryCount(0);

        self::assertInstanceOf(Country::class, $country);

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getMissCount());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
    }

    public function testRepositoryCacheFindAllToOneAssociation(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();

        $this->evictRegions();

        $this->secondLevelCacheLogger->clearStats();
        $this->_em->clear();

        // load from database
        $repository = $this->_em->getRepository(State::class);
        $this->getQueryLog()->reset()->enable();
        $entities = $repository->findAll();

        self::assertCount(4, $entities);
        $this->assertQueryCount(1);

        self::assertInstanceOf(State::class, $entities[0]);
        self::assertInstanceOf(State::class, $entities[1]);
        self::assertInstanceOf(Country::class, $entities[0]->getCountry());
        self::assertInstanceOf(Country::class, $entities[0]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[0]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[1]->getCountry());

        // load from cache
        $this->getQueryLog()->reset()->enable();
        $entities = $repository->findAll();

        self::assertCount(4, $entities);
        $this->assertQueryCount(0);

        self::assertInstanceOf(State::class, $entities[0]);
        self::assertInstanceOf(State::class, $entities[1]);
        self::assertInstanceOf(Country::class, $entities[0]->getCountry());
        self::assertInstanceOf(Country::class, $entities[1]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[0]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[1]->getCountry());

        // invalidate cache
        $this->_em->persist(new State('foo', $this->_em->find(Country::class, $this->countries[0]->getId())));
        $this->_em->flush();
        $this->_em->clear();

        // load from database
        $this->getQueryLog()->reset()->enable();
        $entities = $repository->findAll();

        self::assertCount(5, $entities);
        $this->assertQueryCount(1);

        self::assertInstanceOf(State::class, $entities[0]);
        self::assertInstanceOf(State::class, $entities[1]);
        self::assertInstanceOf(Country::class, $entities[0]->getCountry());
        self::assertInstanceOf(Country::class, $entities[1]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[0]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[1]->getCountry());

        // load from cache
        $this->getQueryLog()->reset()->enable();
        $entities = $repository->findAll();

        self::assertCount(5, $entities);
        $this->assertQueryCount(0);

        self::assertInstanceOf(State::class, $entities[0]);
        self::assertInstanceOf(State::class, $entities[1]);
        self::assertInstanceOf(Country::class, $entities[0]->getCountry());
        self::assertInstanceOf(Country::class, $entities[1]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[0]->getCountry());
        self::assertInstanceOf(InternalProxy::class, $entities[1]->getCountry());
    }
}
