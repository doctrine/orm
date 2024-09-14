<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\Tests\OrmFunctionalTestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function assert;
use function count;

class QueryCacheTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testQueryCacheDependsOnHints(): array
    {
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayAdapter();
        $query->setQueryCache($cache);

        $query->getResult();
        self::assertCount(1, $cache->getValues());

        $query->setHint('foo', 'bar');

        $query->getResult();
        self::assertCount(2, $cache->getValues());

        return [$query, $cache];
    }

    /** @depends testQueryCacheDependsOnHints */
    public function testQueryCacheDependsOnFirstResult(array $previous): void
    {
        [$query, $cache] = $previous;
        assert($query instanceof Query);
        assert($cache instanceof ArrayAdapter);

        $cacheCount = count($cache->getValues());

        $query->setFirstResult(10);
        $query->setMaxResults(9999);

        $query->getResult();
        self::assertCount($cacheCount + 1, $cache->getValues());
    }

    /** @depends testQueryCacheDependsOnHints */
    public function testQueryCacheDependsOnMaxResults(array $previous): void
    {
        [$query, $cache] = $previous;
        assert($query instanceof Query);
        assert($cache instanceof ArrayAdapter);

        $cacheCount = count($cache->getValues());

        $query->setMaxResults(10);

        $query->getResult();
        self::assertCount($cacheCount + 1, $cache->getValues());
    }

    /** @depends testQueryCacheDependsOnHints */
    public function testQueryCacheDependsOnHydrationMode(array $previous): void
    {
        [$query, $cache] = $previous;
        assert($query instanceof Query);
        assert($cache instanceof ArrayAdapter);

        $cacheCount = count($cache->getValues());

        $query->getArrayResult();
        self::assertCount($cacheCount + 1, $cache->getValues());
    }

    public function testQueryCacheNoHitSaveParserResult(): void
    {
        $this->_em->getConfiguration()->setQueryCache($this->createMock(CacheItemPoolInterface::class));

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $query->setQueryCache($cache);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects(self::never())->method('get');
        $cacheItem->expects(self::once())->method('set')->with(self::isInstanceOf(ParserResult::class))->willReturnSelf();
        $cacheItem->method('expiresAfter')->willReturnSelf();

        $cache->expects(self::once())
            ->method('getItem')
            ->with(self::isType('string'))
            ->willReturn($cacheItem);

        $cache
            ->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($cacheItem))
            ->willReturn(true);

        $query->getResult();
    }

    public function testQueryCacheHitDoesNotSaveParserResult(): void
    {
        $this->_em->getConfiguration()->setQueryCache($this->createMock(CacheItemPoolInterface::class));

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $sqlExecMock = $this->getMockBuilder(AbstractSqlExecutor::class)
                            ->getMockForAbstractClass();

        $sqlExecMock->expects(self::once())
                    ->method('execute')
                    ->willReturn(10);

        $parserResultMock = new ParserResult();
        $parserResultMock->setSqlExecutor($sqlExecMock);

        $cache = $this->createMock(CacheItemPoolInterface::class);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn($parserResultMock);
        $cacheItem->expects(self::never())->method('set');

        $cache->expects(self::once())
            ->method('getItem')
            ->with(self::isType('string'))
            ->willReturn($cacheItem);

        $cache->expects(self::never())
              ->method('save');

        $query->setQueryCache($cache);

        $query->getResult();
    }
}
