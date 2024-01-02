<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\Tests\DoctrineTestCase;
use Doctrine\Tests\Models\Cache\State;

/** @group DDC-2183 */
class StatisticsCacheLoggerTest extends DoctrineTestCase
{
    /** @var StatisticsCacheLogger */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new StatisticsCacheLogger();
    }

    public function testEntityCache(): void
    {
        $name = 'my_entity_region';
        $key  = new EntityCacheKey(State::class, ['id' => 1]);

        $this->logger->entityCacheHit($name, $key);
        $this->logger->entityCachePut($name, $key);
        $this->logger->entityCacheMiss($name, $key);

        self::assertEquals(1, $this->logger->getHitCount());
        self::assertEquals(1, $this->logger->getPutCount());
        self::assertEquals(1, $this->logger->getMissCount());
        self::assertEquals(1, $this->logger->getRegionHitCount($name));
        self::assertEquals(1, $this->logger->getRegionPutCount($name));
        self::assertEquals(1, $this->logger->getRegionMissCount($name));
    }

    public function testCollectionCache(): void
    {
        $name = 'my_collection_region';
        $key  = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);

        $this->logger->collectionCacheHit($name, $key);
        $this->logger->collectionCachePut($name, $key);
        $this->logger->collectionCacheMiss($name, $key);

        self::assertEquals(1, $this->logger->getHitCount());
        self::assertEquals(1, $this->logger->getPutCount());
        self::assertEquals(1, $this->logger->getMissCount());
        self::assertEquals(1, $this->logger->getRegionHitCount($name));
        self::assertEquals(1, $this->logger->getRegionPutCount($name));
        self::assertEquals(1, $this->logger->getRegionMissCount($name));
    }

    public function testQueryCache(): void
    {
        $name = 'my_query_region';
        $key  = new QueryCacheKey('my_query_hash');

        $this->logger->queryCacheHit($name, $key);
        $this->logger->queryCachePut($name, $key);
        $this->logger->queryCacheMiss($name, $key);

        self::assertEquals(1, $this->logger->getHitCount());
        self::assertEquals(1, $this->logger->getPutCount());
        self::assertEquals(1, $this->logger->getMissCount());
        self::assertEquals(1, $this->logger->getRegionHitCount($name));
        self::assertEquals(1, $this->logger->getRegionPutCount($name));
        self::assertEquals(1, $this->logger->getRegionMissCount($name));
    }

    public function testMultipleCaches(): void
    {
        $coolRegion   = 'my_collection_region';
        $entityRegion = 'my_entity_region';
        $queryRegion  = 'my_query_region';

        $coolKey   = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);
        $entityKey = new EntityCacheKey(State::class, ['id' => 1]);
        $queryKey  = new QueryCacheKey('my_query_hash');

        $this->logger->queryCacheHit($queryRegion, $queryKey);
        $this->logger->queryCachePut($queryRegion, $queryKey);
        $this->logger->queryCacheMiss($queryRegion, $queryKey);

        $this->logger->entityCacheHit($entityRegion, $entityKey);
        $this->logger->entityCachePut($entityRegion, $entityKey);
        $this->logger->entityCacheMiss($entityRegion, $entityKey);

        $this->logger->collectionCacheHit($coolRegion, $coolKey);
        $this->logger->collectionCachePut($coolRegion, $coolKey);
        $this->logger->collectionCacheMiss($coolRegion, $coolKey);

        self::assertEquals(3, $this->logger->getHitCount());
        self::assertEquals(3, $this->logger->getPutCount());
        self::assertEquals(3, $this->logger->getMissCount());

        self::assertEquals(1, $this->logger->getRegionHitCount($queryRegion));
        self::assertEquals(1, $this->logger->getRegionPutCount($queryRegion));
        self::assertEquals(1, $this->logger->getRegionMissCount($queryRegion));

        self::assertEquals(1, $this->logger->getRegionHitCount($coolRegion));
        self::assertEquals(1, $this->logger->getRegionPutCount($coolRegion));
        self::assertEquals(1, $this->logger->getRegionMissCount($coolRegion));

        self::assertEquals(1, $this->logger->getRegionHitCount($entityRegion));
        self::assertEquals(1, $this->logger->getRegionPutCount($entityRegion));
        self::assertEquals(1, $this->logger->getRegionMissCount($entityRegion));

        $miss = $this->logger->getRegionsMiss();
        $hit  = $this->logger->getRegionsHit();
        $put  = $this->logger->getRegionsPut();

        self::assertArrayHasKey($coolRegion, $miss);
        self::assertArrayHasKey($queryRegion, $miss);
        self::assertArrayHasKey($entityRegion, $miss);

        self::assertArrayHasKey($coolRegion, $put);
        self::assertArrayHasKey($queryRegion, $put);
        self::assertArrayHasKey($entityRegion, $put);

        self::assertArrayHasKey($coolRegion, $hit);
        self::assertArrayHasKey($queryRegion, $hit);
        self::assertArrayHasKey($entityRegion, $hit);
    }
}
