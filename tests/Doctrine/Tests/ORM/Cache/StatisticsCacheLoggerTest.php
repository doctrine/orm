<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\DoctrineTestCase;

/**
 * @group DDC-2183
 */
class StatisticsCacheLoggerTest extends DoctrineTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\Logging\StatisticsCacheLogger
     */
    private $logger;

    protected function setUp()
    {
        parent::setUp();

        $this->logger = new StatisticsCacheLogger();
    }

    public function testEntityCache()
    {
        $name = 'my_entity_region';
        $key  = new EntityCacheKey(State::CLASSNAME, array('id' => 1));

        $this->logger->entityCacheHit($name, $key);
        $this->logger->entityCachePut($name, $key);
        $this->logger->entityCacheMiss($name, $key);

        $this->assertEquals(1, $this->logger->getHitCount());
        $this->assertEquals(1, $this->logger->getPutCount());
        $this->assertEquals(1, $this->logger->getMissCount());
        $this->assertEquals(1, $this->logger->getRegionHitCount($name));
        $this->assertEquals(1, $this->logger->getRegionPutCount($name));
        $this->assertEquals(1, $this->logger->getRegionMissCount($name));
    }

    public function testCollectionCache()
    {
        $name = 'my_collection_region';
        $key  = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id' => 1));

        $this->logger->collectionCacheHit($name, $key);
        $this->logger->collectionCachePut($name, $key);
        $this->logger->collectionCacheMiss($name, $key);

        $this->assertEquals(1, $this->logger->getHitCount());
        $this->assertEquals(1, $this->logger->getPutCount());
        $this->assertEquals(1, $this->logger->getMissCount());
        $this->assertEquals(1, $this->logger->getRegionHitCount($name));
        $this->assertEquals(1, $this->logger->getRegionPutCount($name));
        $this->assertEquals(1, $this->logger->getRegionMissCount($name));
    }

    public function testQueryCache()
    {
        $name = 'my_query_region';
        $key  = new QueryCacheKey('my_query_hash');

        $this->logger->queryCacheHit($name, $key);
        $this->logger->queryCachePut($name, $key);
        $this->logger->queryCacheMiss($name, $key);

        $this->assertEquals(1, $this->logger->getHitCount());
        $this->assertEquals(1, $this->logger->getPutCount());
        $this->assertEquals(1, $this->logger->getMissCount());
        $this->assertEquals(1, $this->logger->getRegionHitCount($name));
        $this->assertEquals(1, $this->logger->getRegionPutCount($name));
        $this->assertEquals(1, $this->logger->getRegionMissCount($name));
    }

    public function testMultipleCaches()
    {
        $coolRegion   = 'my_collection_region';
        $entityRegion = 'my_entity_region';
        $queryRegion  = 'my_query_region';

        $coolKey    = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id' => 1));
        $entityKey  = new EntityCacheKey(State::CLASSNAME, array('id' => 1));
        $queryKey   = new QueryCacheKey('my_query_hash');

        $this->logger->queryCacheHit($queryRegion, $queryKey);
        $this->logger->queryCachePut($queryRegion, $queryKey);
        $this->logger->queryCacheMiss($queryRegion, $queryKey);

        $this->logger->entityCacheHit($entityRegion, $entityKey);
        $this->logger->entityCachePut($entityRegion, $entityKey);
        $this->logger->entityCacheMiss($entityRegion, $entityKey);

        $this->logger->collectionCacheHit($coolRegion, $coolKey);
        $this->logger->collectionCachePut($coolRegion, $coolKey);
        $this->logger->collectionCacheMiss($coolRegion, $coolKey);

        $this->assertEquals(3, $this->logger->getHitCount());
        $this->assertEquals(3, $this->logger->getPutCount());
        $this->assertEquals(3, $this->logger->getMissCount());

        $this->assertEquals(1, $this->logger->getRegionHitCount($queryRegion));
        $this->assertEquals(1, $this->logger->getRegionPutCount($queryRegion));
        $this->assertEquals(1, $this->logger->getRegionMissCount($queryRegion));

        $this->assertEquals(1, $this->logger->getRegionHitCount($coolRegion));
        $this->assertEquals(1, $this->logger->getRegionPutCount($coolRegion));
        $this->assertEquals(1, $this->logger->getRegionMissCount($coolRegion));

        $this->assertEquals(1, $this->logger->getRegionHitCount($entityRegion));
        $this->assertEquals(1, $this->logger->getRegionPutCount($entityRegion));
        $this->assertEquals(1, $this->logger->getRegionMissCount($entityRegion));

        $miss = $this->logger->getRegionsMiss();
        $hit  = $this->logger->getRegionsHit();
        $put  = $this->logger->getRegionsPut();

        $this->assertArrayHasKey($coolRegion, $miss);
        $this->assertArrayHasKey($queryRegion, $miss);
        $this->assertArrayHasKey($entityRegion, $miss);

        $this->assertArrayHasKey($coolRegion, $put);
        $this->assertArrayHasKey($queryRegion, $put);
        $this->assertArrayHasKey($entityRegion, $put);

        $this->assertArrayHasKey($coolRegion, $hit);
        $this->assertArrayHasKey($queryRegion, $hit);
        $this->assertArrayHasKey($entityRegion, $hit);
    }
}