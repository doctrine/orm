<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\Tests\OrmFunctionalTestCase;
use ReflectionProperty;

use function class_exists;
use function count;

/**
 * QueryCacheTest
 */
class QueryCacheTest extends OrmFunctionalTestCase
{
    /** @var ReflectionProperty */
    private $cacheDataReflection;

    protected function setUp(): void
    {
        if (! class_exists(ArrayCache::class)) {
            self::markTestSkipped('Test only applies with doctrine/cache 1.x');
        }

        $this->cacheDataReflection = new ReflectionProperty(ArrayCache::class, 'data');
        $this->cacheDataReflection->setAccessible(true);

        $this->useModelSet('cms');

        parent::setUp();
    }

    private function getCacheSize(ArrayCache $cache): int
    {
        return count($this->cacheDataReflection->getValue($cache));
    }

    public function testQueryCacheDependsOnHints(): Query
    {
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayCache();
        $query->setQueryCacheDriver($cache);

        $query->getResult();
        self::assertEquals(1, $this->getCacheSize($cache));

        $query->setHint('foo', 'bar');

        $query->getResult();
        self::assertEquals(2, $this->getCacheSize($cache));

        return $query;
    }

    /**
     * @param <type> $query
     *
     * @depends testQueryCacheDependsOnHints
     */
    public function testQueryCacheDependsOnFirstResult($query): void
    {
        $cache      = $query->getQueryCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->setFirstResult(10);
        $query->setMaxResults(9999);

        $query->getResult();
        self::assertEquals($cacheCount + 1, $this->getCacheSize($cache));
    }

    /**
     * @param <type> $query
     *
     * @depends testQueryCacheDependsOnHints
     */
    public function testQueryCacheDependsOnMaxResults($query): void
    {
        $cache      = $query->getQueryCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->setMaxResults(10);

        $query->getResult();
        self::assertEquals($cacheCount + 1, $this->getCacheSize($cache));
    }

    /**
     * @param <type> $query
     *
     * @depends testQueryCacheDependsOnHints
     */
    public function testQueryCacheDependsOnHydrationMode($query): void
    {
        $cache      = $query->getQueryCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->getArrayResult();
        self::assertEquals($cacheCount + 1, $this->getCacheSize($cache));
    }

    public function testQueryCacheNoHitSaveParserResult(): void
    {
        $this->_em->getConfiguration()->setQueryCacheImpl(new ArrayCache());

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = $this->createMock(Cache::class);

        $query->setQueryCacheDriver($cache);

        $cache
            ->expects(self::once())
            ->method('save')
            ->with(self::isType('string'), self::isInstanceOf(ParserResult::class));

        $query->getResult();
    }

    public function testQueryCacheHitDoesNotSaveParserResult(): void
    {
        $this->_em->getConfiguration()->setQueryCacheImpl(new ArrayCache());

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $sqlExecMock = $this->getMockBuilder(AbstractSqlExecutor::class)
                            ->setMethods(['execute'])
                            ->getMock();

        $sqlExecMock->expects(self::once())
                    ->method('execute')
                    ->will(self::returnValue(10));

        $parserResultMock = $this->getMockBuilder(ParserResult::class)
                                 ->setMethods(['getSqlExecutor'])
                                 ->getMock();
        $parserResultMock->expects(self::once())
                         ->method('getSqlExecutor')
                         ->will(self::returnValue($sqlExecMock));

        $cache = $this->getMockBuilder(CacheProvider::class)
                      ->setMethods(['doFetch', 'doContains', 'doSave', 'doDelete', 'doFlush', 'doGetStats'])
                      ->getMock();

        $cache->expects(self::exactly(2))
            ->method('doFetch')
            ->withConsecutive(
                [self::isType('string')],
                [self::isType('string')]
            )
            ->willReturnOnConsecutiveCalls(
                self::returnValue(1),
                self::returnValue($parserResultMock)
            );

        $cache->expects(self::never())
              ->method('doSave');

        $query->setQueryCacheDriver($cache);

        $users = $query->getResult();
    }
}
