<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Doctrine\ORM\Tools\Pagination\WindowPage;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2943')]
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
            ->setCacheable(true);

        $paginator = new OffsetPaginator();
        $window    = new Window(0, 2);

        $this->assertPaginatorQueryPut($paginator->paginate($query, $window), $region->getName(), 4, 2);

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->assertPaginatorQueryHit($paginator->paginate($query, $window), $region->getName(), 4, 2);
    }

    public function testIssueNonFetchJoin(): void
    {
        $this->loadFixtures();

        $region = $this->_em->getCache()->getEntityCacheRegion(Country::class);
        $dql    = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c';
        $query  = $this->_em->createQuery($dql)
            ->setCacheable(true);

        $paginator = new OffsetPaginator(false);
        $window    = new Window(0, 2);

        $this->assertPaginatorQueryPut($paginator->paginate($query, $window), $region->getName(), 4, 2);

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->assertPaginatorQueryHit($paginator->paginate($query, $window), $region->getName(), 4, 2);
    }

    /** @param WindowPage<mixed> $page */
    public function assertPaginatorQueryPut(WindowPage $page, $regionName, $count, $pageSize): void
    {
        self::assertSame($count, $page->getTotalCount());
        self::assertCount($pageSize, $page);

        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionHitCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionHitCount($regionName));
        self::assertEquals($count, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
    }

    /** @param WindowPage<mixed> $page */
    public function assertPaginatorQueryHit(WindowPage $page, $regionName, $count, $pageSize): void
    {
        self::assertSame($count, $page->getTotalCount());
        self::assertCount($pageSize, $page);

        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionPutCount(Cache::DEFAULT_QUERY_REGION_NAME));
        self::assertEquals($pageSize, $this->secondLevelCacheLogger->getRegionHitCount($regionName));
        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
    }
}
