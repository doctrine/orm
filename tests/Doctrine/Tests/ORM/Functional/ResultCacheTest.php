<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsArticle;
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
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.2.0-DEV', '>=')) {
            $this->markTestSkipped('Test not compatible with 2.2 common');
        }

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

        $this->_em->getConfiguration()->setResultCacheImpl(new ArrayCache());
    }

    /**
     * @group DDC-1026
     */
    public function testUseResultCacheParams()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $this->_em->getConfiguration()->setResultCacheImpl($cache);

        $sqlCount = count($this->_sqlLoggerStack->queries);
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux WHERE ux.id = ?1');
        $query->setParameter(1, 1);
        $query->useResultCache(true);
        $query->getResult();

        $query->setParameter(1, 2);
        $query->getResult();

        $this->assertEquals($sqlCount + 2, count($this->_sqlLoggerStack->queries), "Two non-cached queries.");

        $query->setParameter(1, 1);
        $query->useResultCache(true);
        $query->getResult();

        $query->setParameter(1, 2);
        $query->getResult();

        $this->assertEquals($sqlCount + 2, count($this->_sqlLoggerStack->queries), "The next two sql should have been cached, but were not.");
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

    /**
     * @param <type> $query
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheDependsOnHydrationMode($query)
    {
        $cache = $query->getResultCacheDriver();
        $cacheCount = count($cache->getIds());

        $this->assertNotEquals(\Doctrine\ORM\Query::HYDRATE_ARRAY, $query->getHydrationMode());
        $query->getArrayResult();

        $this->assertEquals($cacheCount + 1, count($cache->getIds()));
    }

    /**
     * @group DDC-909
     */
    public function testResultCacheWithObjectParameter()
    {
        $user1 = new CmsUser;
        $user1->name = 'Roman';
        $user1->username = 'romanb';
        $user1->status = 'dev';

        $user2 = new CmsUser;
        $user2->name = 'Benjamin';
        $user2->username = 'beberlei';
        $user2->status = 'dev';

        $article = new CmsArticle();
        $article->text = "foo";
        $article->topic = "baz";
        $article->user = $user1;

        $this->_em->persist($article);
        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();

        $query = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query->setParameter(1, $user1);

        $cache = new ArrayCache();

        $query->setResultCacheDriver($cache)->useResultCache(true);

        $articles = $query->getResult();

        $this->assertEquals(1, count($articles));
        $this->assertEquals('baz', $articles[0]->topic);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query2->setParameter(1, $user1);

        $query2->setResultCacheDriver($cache)->useResultCache(true);

        $articles = $query2->getResult();

        $this->assertEquals(1, count($articles));
        $this->assertEquals('baz', $articles[0]->topic);

        $query3 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query3->setParameter(1, $user2);

        $query3->setResultCacheDriver($cache)->useResultCache(true);

        $articles = $query3->getResult();

        $this->assertEquals(0, count($articles));
    }
}