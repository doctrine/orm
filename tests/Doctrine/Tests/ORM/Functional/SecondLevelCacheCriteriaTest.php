<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;

/**
 * @group DDC-2183
 */
class SecondLevelCacheCriteriaTest extends SecondLevelCacheAbstractTest
{
    public function testMatchingPut()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->evictRegions();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));

        $repository = $this->_em->getRepository(Country::class);
        $queryCount = $this->getCurrentQueryCount();
        $name       = $this->countries[0]->getName();
        $result1    = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $name)
        ));

        // Because matching returns lazy collection, we force initialization
        $result1->toArray();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));

        $this->_em->clear();

        $result2 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $name)
        ));

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(1, $result2);

        $this->assertInstanceOf(Country::class, $result2[0]);

        $this->assertEquals($result1[0]->getId(), $result2[0]->getId());
        $this->assertEquals($result1[0]->getName(), $result2[0]->getName());
    }

    public function testRepositoryMatching()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));

        $repository = $this->_em->getRepository(Country::class);
        $queryCount = $this->getCurrentQueryCount();
        $result1    = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[0]->getName())
        ));

        // Because matching returns lazy collection, we force initialization
        $result1->toArray();

        $this->assertCount(1, $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result1[0]->getName());

        $this->_em->clear();

        $result2 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[0]->getName())
        ));

        // Because matching returns lazy collection, we force initialization
        $result2->toArray();

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertCount(1, $result2);

        $this->assertInstanceOf(Country::class, $result2[0]);

        $this->assertEquals($this->countries[0]->getId(), $result2[0]->getId());
        $this->assertEquals($this->countries[0]->getName(), $result2[0]->getName());

        $result3 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[1]->getName())
        ));

        // Because matching returns lazy collection, we force initialization
        $result3->toArray();

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertCount(1, $result3);

        $this->assertInstanceOf(Country::class, $result3[0]);

        $this->assertEquals($this->countries[1]->getId(), $result3[0]->getId());
        $this->assertEquals($this->countries[1]->getName(), $result3[0]->getName());

        $result4 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[1]->getName())
        ));

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        $this->assertCount(1, $result4);

        $this->assertInstanceOf(Country::class, $result4[0]);

        $this->assertEquals($this->countries[1]->getId(), $result4[0]->getId());
        $this->assertEquals($this->countries[1]->getName(), $result4[0]->getName());
    }

    public function testCollectionMatching()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $entity     = $this->_em->find(State::class, $this->states[0]->getId());
        $itemName   = $this->states[0]->getCities()->get(0)->getName();
        $queryCount = $this->getCurrentQueryCount();
        $collection = $entity->getCities();
        $matching   = $collection->matching(new Criteria(
            Criteria::expr()->eq('name', $itemName)
        ));

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertInstanceOf(Collection::class, $matching);
        $this->assertCount(1, $matching);

        $this->_em->clear();

        $entity     = $this->_em->find(State::class, $this->states[0]->getId());
        $queryCount = $this->getCurrentQueryCount();
        $collection = $entity->getCities();
        $matching   = $collection->matching(new Criteria(
            Criteria::expr()->eq('name', $itemName)
        ));

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertInstanceOf(Collection::class, $matching);
        $this->assertCount(1, $matching);
    }

}
