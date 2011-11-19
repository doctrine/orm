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
    protected function setUp() {
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.2.0-DEV', '>=')) {
            $this->markTestSkipped('Test not compatible with 2.2 common');
        }

        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testQueryCache_DependsOnHints()
    {
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayCache();
        $query->setQueryCacheDriver($cache);

        $query->getResult();
        $this->assertEquals(1, count($cache->getIds()));

        $query->setHint('foo', 'bar');

        $query->getResult();
        $this->assertEquals(2, count($cache->getIds()));

        return $query;
    }

    /**
     * @param <type> $query
     * @depends testQueryCache_DependsOnHints
     */
    public function testQueryCache_DependsOnFirstResult($query)
    {
        $cache = $query->getQueryCacheDriver();
        $cacheCount = count($cache->getIds());

        $query->setFirstResult(10);
        $query->setMaxResults(9999);

        $query->getResult();
        $this->assertEquals($cacheCount + 1, count($cache->getIds()));
    }

    /**
     * @param <type> $query
     * @depends testQueryCache_DependsOnHints
     */
    public function testQueryCache_DependsOnMaxResults($query)
    {
        $cache = $query->getQueryCacheDriver();
        $cacheCount = count($cache->getIds());

        $query->setMaxResults(10);

        $query->getResult();
        $this->assertEquals($cacheCount + 1, count($cache->getIds()));
    }

    /**
     * @param <type> $query
     * @depends testQueryCache_DependsOnHints
     */
    public function testQueryCache_DependsOnHydrationMode($query)
    {
        $cache = $query->getQueryCacheDriver();
        $cacheCount = count($cache->getIds());

        $query->getArrayResult();
        $this->assertEquals($cacheCount + 1, count($cache->getIds()));
    }

    public function testQueryCache_NoHitSaveParserResult()
    {
        $this->_em->getConfiguration()->setQueryCacheImpl(new ArrayCache());

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = $this->getMock('Doctrine\Common\Cache\AbstractCache', array('_doFetch', '_doContains', '_doSave', '_doDelete', 'getIds'));
        $cache->expects($this->at(0))
              ->method('_doFetch')
              ->with($this->isType('string'))
              ->will($this->returnValue(false));
        $cache->expects($this->at(1))
              ->method('_doSave')
              ->with($this->isType('string'), $this->isInstanceOf('Doctrine\ORM\Query\ParserResult'), $this->equalTo(null));

        $query->setQueryCacheDriver($cache);

        $users = $query->getResult();
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

        $cache = $this->getMock('Doctrine\Common\Cache\AbstractCache', array('_doFetch', '_doContains', '_doSave', '_doDelete', 'getIds'));
        $cache->expects($this->once())
              ->method('_doFetch')
              ->with($this->isType('string'))
              ->will($this->returnValue($parserResultMock));
        $cache->expects($this->never())
              ->method('_doSave');

        $query->setQueryCacheDriver($cache);

        $users = $query->getResult();
    }
}

