<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\Tests\OrmFunctionalTestCase;
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
        $query->setQueryCacheDriver(DoctrineProvider::wrap($cache));

        $query->getResult();
        self::assertCount(2, $cache->getValues());

        $query->setHint('foo', 'bar');

        $query->getResult();
        self::assertCount(3, $cache->getValues());

        return [$query, $cache];
    }

    /**
     * @depends testQueryCacheDependsOnHints
     */
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

    /**
     * @depends testQueryCacheDependsOnHints
     */
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

    /**
     * @depends testQueryCacheDependsOnHints
     */
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
        $this->_em->getConfiguration()->setQueryCacheImpl($this->createMock(Cache::class));

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
        $this->_em->getConfiguration()->setQueryCacheImpl($this->createMock(Cache::class));

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

        $query->getResult();
    }
}
