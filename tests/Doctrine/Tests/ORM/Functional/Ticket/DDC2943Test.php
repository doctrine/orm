<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group DDC-2943 */
class DDC2943Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();
        $this->useModelSet('cache');

        parent::setUp();
    }

    private function loadFixtures(): void
    {
        $this->_em->persist(new Country('Brazil'));
        $this->_em->persist(new Country('Canada'));
        $this->_em->persist(new Country('Germany'));
        $this->_em->persist(new Country('France'));
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testIssue(): void
    {
        $this->loadFixtures();

        $region = $this->_em->getCache()->getEntityCacheRegion(Country::class);
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

    public function testIssueNonFetchJoin(): void
    {
        $this->loadFixtures();

        $region = $this->_em->getCache()->getEntityCacheRegion(Country::class);
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

    public function assertPaginatorQueryPut(Paginator $paginator, $regionName, $count, $pageSize): void
    {
        self::assertCount($count, $paginator);
        self::assertCount($pageSize, $paginator->getIterator());

        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionHitCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionHitCount($regionName));
        self::assertEquals($count, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
    }

    public function assertPaginatorQueryHit(Paginator $paginator, $regionName, $count, $pageSize): void
    {
        self::assertCount($count, $paginator);
        self::assertCount($pageSize, $paginator->getIterator());

        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionPutCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals($pageSize, $this->secondLevelCacheLogger->getRegionHitCount($regionName));
        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
    }
}
