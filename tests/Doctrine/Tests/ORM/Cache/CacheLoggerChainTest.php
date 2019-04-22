<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\Logging\CacheLoggerChain;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\Tests\DoctrineTestCase;
use Doctrine\Tests\Models\Cache\State;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group DDC-2183
 */
class CacheLoggerChainTest extends DoctrineTestCase
{
    /** @var CacheLoggerChain */
    private $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheLogger */
    private $mock;

    protected function setUp() : void
    {
        parent::setUp();

        $this->logger = new CacheLoggerChain();
        $this->mock   = $this->createMock(CacheLogger::class);
    }

    public function testGetAndSetLogger() : void
    {
        self::assertEmpty($this->logger->getLoggers());

        self::assertNull($this->logger->getLogger('mock'));

        $this->logger->setLogger('mock', $this->mock);

        self::assertSame($this->mock, $this->logger->getLogger('mock'));
        self::assertEquals(['mock' => $this->mock], $this->logger->getLoggers());
    }

    public function testEntityCacheChain() : void
    {
        $name = 'my_entity_region';
        $key  = new EntityCacheKey(State::class, ['id' => 1]);

        $this->logger->setLogger('mock', $this->mock);

        $this->mock->expects($this->once())
            ->method('entityCacheHit')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->mock->expects($this->once())
            ->method('entityCachePut')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->mock->expects($this->once())
            ->method('entityCacheMiss')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->logger->entityCacheHit($name, $key);
        $this->logger->entityCachePut($name, $key);
        $this->logger->entityCacheMiss($name, $key);
    }

    public function testCollectionCacheChain() : void
    {
        $name = 'my_collection_region';
        $key  = new CollectionCacheKey(State::class, 'cities', ['id' => 1]);

        $this->logger->setLogger('mock', $this->mock);

        $this->mock->expects($this->once())
            ->method('collectionCacheHit')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->mock->expects($this->once())
            ->method('collectionCachePut')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->mock->expects($this->once())
            ->method('collectionCacheMiss')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->logger->collectionCacheHit($name, $key);
        $this->logger->collectionCachePut($name, $key);
        $this->logger->collectionCacheMiss($name, $key);
    }

    public function testQueryCacheChain() : void
    {
        $name = 'my_query_region';
        $key  = new QueryCacheKey('my_query_hash');

        $this->logger->setLogger('mock', $this->mock);

        $this->mock->expects($this->once())
            ->method('queryCacheHit')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->mock->expects($this->once())
            ->method('queryCachePut')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->mock->expects($this->once())
            ->method('queryCacheMiss')
            ->with($this->equalTo($name), $this->equalTo($key));

        $this->logger->queryCacheHit($name, $key);
        $this->logger->queryCachePut($name, $key);
        $this->logger->queryCacheMiss($name, $key);
    }
}
