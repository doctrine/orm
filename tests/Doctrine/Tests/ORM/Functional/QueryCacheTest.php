<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/../../TestInit.php';

/**
 * QueryCacheTest
 *
 * @author robo
 */
class QueryCacheTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var \ReflectionProperty
     */
    private $cacheDataReflection;

    protected function setUp()
    {
        $this->cacheDataReflection = new \ReflectionProperty("Doctrine\Common\Cache\ArrayCache", "data");
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
        $this->assertEquals(2, $this->getCacheSize($cache));

        $query->setHint('foo', 'bar');

        $query->getResult();
        $this->assertEquals(3, $this->getCacheSize($cache));

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

        $cache = new \Doctrine\Common\Cache\ArrayCache();

        $query->setQueryCacheDriver($cache);

        $users = $query->getResult();

        $data = $this->cacheDataReflection->getValue($cache);
        $this->assertEquals(2, count($data));

        $this->assertInstanceOf('Doctrine\ORM\Query\ParserResult', array_pop($data));
    }

    public function testQueryCache_HitDoesNotSaveParserResult()
    {
        $this->_em->getConfiguration()->setQueryCacheImpl(new ArrayCache());

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $sqlExecMock = $this->getMock('Doctrine\ORM\Query\Exec\AbstractSqlExecutor', array('execute'));
        $sqlExecMock->expects($this->once())
                    ->method('execute')
                    ->will($this->returnValue( 10 ));

        $parserResultMock = $this->getMock('Doctrine\ORM\Query\ParserResult');
        $parserResultMock->expects($this->once())
                         ->method('getSqlExecutor')
                         ->will($this->returnValue($sqlExecMock));

        $cache = $this->getMock('Doctrine\Common\Cache\CacheProvider',
                array('doFetch', 'doContains', 'doSave', 'doDelete', 'doFlush', 'doGetStats'));
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

