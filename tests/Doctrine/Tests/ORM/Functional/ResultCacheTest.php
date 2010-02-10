<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/../../TestInit.php';

/**
 * ResultCacheTest
 *
 * @author robo
 */
class ResultCacheTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testResultCache()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->_em->persist($user);
        $this->_em->flush();


        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayCache();
        
        $query->setResultCacheDriver($cache)->setResultCacheId('my_cache_id');

        $this->assertFalse($cache->contains('my_cache_id'));

        $users = $query->getResult();

        $this->assertTrue($cache->contains('my_cache_id'));
        $this->assertEquals(1, count($users));
        $this->assertEquals('Roman', $users[0]->name);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query2->setResultCacheDriver($cache)->setResultCacheId('my_cache_id');

        $users = $query2->getResult();

        $this->assertTrue($cache->contains('my_cache_id'));
        $this->assertEquals(1, count($users));
        $this->assertEquals('Roman', $users[0]->name);
    }

    public function testSetResultCacheId()
    {
        $cache = new ArrayCache;

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');

        $this->assertFalse($cache->contains('testing_result_cache_id'));
        
        $users = $query->getResult();

        $this->assertTrue($cache->contains('testing_result_cache_id'));
    }

    public function testUseResultCache()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $this->_em->getConfiguration()->setResultCacheImpl($cache);

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->useResultCache(true);
        $query->setResultCacheId('testing_result_cache_id');
        $users = $query->getResult();

        $this->assertTrue($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl(null);
    }

    public function testNativeQueryResultCaching()
    {
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('id', 'u');
        $query = $this->_em->createNativeQuery('select u.id FROM cms_users u WHERE u.id = ?', $rsm);
        $query->setParameter(1, 10);

        $cache = new ArrayCache();
        $query->setResultCacheDriver($cache)->useResultCache(true);
        
        $this->assertEquals(0, count($cache->getIds()));
        $query->getResult();
        $this->assertEquals(1, count($cache->getIds()));

        return $query;
    }

    /**
     * @param <type> $query
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheDependsOnQueryHints($query)
    {
        $cache = $query->getResultCacheDriver();
        $cacheCount = count($cache->getIds());

        $query->setHint('foo', 'bar');
        $query->getResult();

        $this->assertEquals($cacheCount + 1, count($cache->getIds()));
    }

    /**
     * @param <type> $query
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheDependsOnParameters($query)
    {
        $cache = $query->getResultCacheDriver();
        $cacheCount = count($cache->getIds());

        $query->setParameter(1, 50);
        $query->getResult();

        $this->assertEquals($cacheCount + 1, count($cache->getIds()));
    }
}