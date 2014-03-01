<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Cache;

/**
 * @group DDC-2943
 */
class DDC2943Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->enableSecondLevelCache();
        $this->useModelSet('cache');
        parent::setUp();
    }

    private function loadFixtures()
    {
        $this->_em->persist(new Country("Brazil"));
        $this->_em->persist(new Country("Canada"));
        $this->_em->persist(new Country("Germany"));
        $this->_em->persist(new Country("France"));
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testIssue()
    {
        $this->loadFixtures();

        $region = $this->_em->getCache()->getEntityCacheRegion(Country::CLASSNAME);
        $dql    = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $query  = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->setFirstResult(0)
            ->setMaxResults(2);

        $this->assertPaginatorQueryPut(new Paginator(clone $query), $region->getName(), 4, 2);

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->assertPaginatorQueryHit(new Paginator(clone $query), $region->getName(), 4, 2);
    }

    public function testIssueNonFetchJoin()
    {
        $this->loadFixtures();

        $region = $this->_em->getCache()->getEntityCacheRegion(Country::CLASSNAME);
        $dql    = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $query  = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->setFirstResult(0)
            ->setMaxResults(2);

        $this->assertPaginatorQueryPut(new Paginator(clone $query, false), $region->getName(), 4, 2);

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->assertPaginatorQueryHit(new Paginator(clone $query, false), $region->getName(), 4, 2);
    }

    public function assertPaginatorQueryPut(Paginator $paginator, $regionName, $count, $pageSize)
    {
        $this->assertCount($count, $paginator);
        $this->assertCount($pageSize, $paginator->getIterator());

        $this->assertEquals(0, $this->secondLevelCacheLogger->getRegionHitCount(Cache::DEFAULT_QUERY_REGION_NAME));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount(Cache::DEFAULT_QUERY_REGION_NAME));
        $this->assertEquals(0, $this->secondLevelCacheLogger->getRegionHitCount($regionName));
        $this->assertEquals($count, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
    }

    public function assertPaginatorQueryHit(Paginator $paginator, $regionName, $count, $pageSize)
    {
        $this->assertCount($count, $paginator);
        $this->assertCount($pageSize, $paginator->getIterator());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount(Cache::DEFAULT_QUERY_REGION_NAME));
        $this->assertEquals(0, $this->secondLevelCacheLogger->getRegionPutCount(Cache::DEFAULT_QUERY_REGION_NAME));
        $this->assertEquals($pageSize, $this->secondLevelCacheLogger->getRegionHitCount($regionName));
        $this->assertEquals(0, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
    }
}
