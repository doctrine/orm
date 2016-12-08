<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * QueryCacheTest
 *
 * @author robo
 */
class QueryCacheTest extends OrmFunctionalTestCase
{
    /**
     * @var \ReflectionProperty
     */
    private $cacheDataReflection;

    protected function setUp()
    {
        $this->cacheDataReflection = new \ReflectionProperty(ArrayCache::class, "data");
        $this->cacheDataReflection->setAccessible(true);

        $this->useModelSet('cms');

        parent::setUp();
    }

    /**
     * @param   ArrayCache $cache
     * @return  integer
     */
    private function getCacheSize(ArrayCache $cache)
    {
        return sizeof($this->cacheDataReflection->getValue($cache));
    }


    public function testQueryCache_DependsOnHints()
    {
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayCache();
        $query->setQueryCacheDriver($cache);

        $query->getResult();
        $this->assertEquals(1, $this->getCacheSize($cache));

        $query->setHint('foo', 'bar');

        $query->getResult();
        $this->assertEquals(2, $this->getCacheSize($cache));

        return $query;
    }

    /**
     * @param <type> $query
     * @depends testQueryCache_DependsOnHints
     */
    public function testQueryCache_DependsOnFirstResult($query)
    {
        $cache = $query->getQueryCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->setFirstResult(10);
        $query->setMaxResults(9999);

        $query->getResult();
        $this->assertEquals($cacheCount + 1, $this->getCacheSize($cache));
    }

    /**
     * @param <type> $query
     * @depends testQueryCache_DependsOnHints
     */
    public function testQueryCache_DependsOnMaxResults($query)
    {
        $cache = $query->getQueryCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->setMaxResults(10);

        $query->getResult();
        $this->assertEquals($cacheCount + 1, $this->getCacheSize($cache));
    }

    /**
     * @param <type> $query
     * @depends testQueryCache_DependsOnHints
     */
    public function testQueryCache_DependsOnHydrationMode($query)
    {
        $cache = $query->getQueryCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->getArrayResult();
        $this->assertEquals($cacheCount + 1, $this->getCacheSize($cache));
    }

    public function testQueryCache_NoHitSaveParserResult()
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

    public function testQueryCache_HitDoesNotSaveParserResult()
    {
        $this->_em->getConfiguration()->setQueryCacheImpl(new ArrayCache());

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $sqlExecMock = $this->getMockBuilder(AbstractSqlExecutor::class)
                            ->setMethods(['execute'])
                            ->getMock();

        $sqlExecMock->expects($this->once())
                    ->method('execute')
                    ->will($this->returnValue( 10 ));

        $parserResultMock = $this->getMockBuilder(ParserResult::class)
                                 ->setMethods(['getSqlExecutor'])
                                 ->getMock();
        $parserResultMock->expects($this->once())
                         ->method('getSqlExecutor')
                         ->will($this->returnValue($sqlExecMock));

        $cache = $this->getMockBuilder(CacheProvider::class)
                      ->setMethods(['doFetch', 'doContains', 'doSave', 'doDelete', 'doFlush', 'doGetStats'])
                      ->getMock();

        $cache->expects($this->at(0))->method('doFetch')->will($this->returnValue(1));
        $cache->expects($this->at(1))
              ->method('doFetch')
              ->with($this->isType('string'))
              ->will($this->returnValue($parserResultMock));

        $cache->expects($this->never())
              ->method('doSave');

        $query->setQueryCacheDriver($cache);

        $users = $query->getResult();
    }
}

