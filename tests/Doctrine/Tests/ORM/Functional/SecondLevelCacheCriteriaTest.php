<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Common\Collections\Criteria;

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

        self::assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));

        $repository = $this->_em->getRepository(Country::CLASSNAME);
        $queryCount = $this->getCurrentQueryCount();
        $name       = $this->countries[0]->getName();
        $result1    = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $name)
        ));

        // Because matching returns lazy collection, we force initialization
        $result1->toArray();

        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        self::assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        self::assertEquals($this->countries[0]->getName(), $result1[0]->getName());

        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));

        $this->_em->clear();

        $result2 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $name)
        ));

        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        self::assertCount(1, $result2);

        self::assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[0]);

        self::assertEquals($result1[0]->getId(), $result2[0]->getId());
        self::assertEquals($result1[0]->getName(), $result2[0]->getName());
    }

    public function testRepositoryMatching()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->countries[0]->getId()));

        $repository = $this->_em->getRepository(Country::CLASSNAME);
        $queryCount = $this->getCurrentQueryCount();
        $result1    = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[0]->getName())
        ));

        // Because matching returns lazy collection, we force initialization
        $result1->toArray();

        self::assertCount(1, $result1);
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        self::assertEquals($this->countries[0]->getId(), $result1[0]->getId());
        self::assertEquals($this->countries[0]->getName(), $result1[0]->getName());

        $this->_em->clear();

        $result2 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[0]->getName())
        ));

        // Because matching returns lazy collection, we force initialization
        $result2->toArray();

        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        self::assertCount(1, $result2);

        self::assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result2[0]);

        self::assertEquals($this->countries[0]->getId(), $result2[0]->getId());
        self::assertEquals($this->countries[0]->getName(), $result2[0]->getName());

        $result3 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[1]->getName())
        ));

        // Because matching returns lazy collection, we force initialization
        $result3->toArray();

        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        self::assertCount(1, $result3);

        self::assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result3[0]);

        self::assertEquals($this->countries[1]->getId(), $result3[0]->getId());
        self::assertEquals($this->countries[1]->getName(), $result3[0]->getName());

        $result4 = $repository->matching(new Criteria(
            Criteria::expr()->eq('name', $this->countries[1]->getName())
        ));

        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        self::assertCount(1, $result4);

        self::assertInstanceOf('Doctrine\Tests\Models\Cache\Country', $result4[0]);

        self::assertEquals($this->countries[1]->getId(), $result4[0]->getId());
        self::assertEquals($this->countries[1]->getName(), $result4[0]->getName());
    }

    public function testCollectionMatching()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $entity     = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $itemName   = $this->states[0]->getCities()->get(0)->getName();
        $queryCount = $this->getCurrentQueryCount();
        $collection = $entity->getCities();
        $matching   = $collection->matching(new Criteria(
            Criteria::expr()->eq('name', $itemName)
        ));

        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        self::assertInstanceOf('Doctrine\Common\Collections\Collection', $matching);
        self::assertCount(1, $matching);

        $this->_em->clear();

        $entity     = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $queryCount = $this->getCurrentQueryCount();
        $collection = $entity->getCities();
        $matching   = $collection->matching(new Criteria(
            Criteria::expr()->eq('name', $itemName)
        ));

        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertInstanceOf('Doctrine\Common\Collections\Collection', $matching);
        self::assertCount(1, $matching);
    }

}
