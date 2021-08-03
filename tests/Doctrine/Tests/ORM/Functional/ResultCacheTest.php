<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use ReflectionProperty;

use function class_exists;
use function count;
use function iterator_to_array;

/**
 * ResultCacheTest
 */
class ResultCacheTest extends OrmFunctionalTestCase
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

    public function testResultCache(): void
    {
        $user = new CmsUser();

        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';

        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $cache = new ArrayCache();

        $query->setResultCacheDriver($cache)->setResultCacheId('my_cache_id');

        self::assertFalse($cache->contains('my_cache_id'));

        $users = $query->getResult();

        self::assertTrue($cache->contains('my_cache_id'));
        self::assertEquals(1, count($users));
        self::assertEquals('Roman', $users[0]->name);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query2->setResultCacheDriver($cache)->setResultCacheId('my_cache_id');

        $users = $query2->getResult();

        self::assertTrue($cache->contains('my_cache_id'));
        self::assertEquals(1, count($users));
        self::assertEquals('Roman', $users[0]->name);
    }

    public function testSetResultCacheId(): void
    {
        $cache = new ArrayCache();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');

        self::assertFalse($cache->contains('testing_result_cache_id'));

        $users = $query->getResult();

        self::assertTrue($cache->contains('testing_result_cache_id'));
    }

    public function testUseResultCacheTrue(): void
    {
        $cache = new ArrayCache();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->useResultCache(true);
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $users = $query->getResult();

        self::assertTrue($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl(new ArrayCache());
    }

    public function testUseResultCacheFalse(): void
    {
        $cache = new ArrayCache();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->useResultCache(false);
        $query->getResult();

        self::assertFalse($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl(new ArrayCache());
    }

    /**
     * @group DDC-1026
     */
    public function testUseResultCacheParams(): void
    {
        $cache    = new ArrayCache();
        $sqlCount = count($this->_sqlLoggerStack->queries);
        $query    = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux WHERE ux.id = ?1');

        $query->setResultCacheDriver($cache);
        $query->useResultCache(true);

        // these queries should result in cache miss:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        self::assertCount(
            $sqlCount + 2,
            $this->_sqlLoggerStack->queries,
            'Two non-cached queries.'
        );

        // these two queries should actually be cached, as they repeat previous ones:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        self::assertCount(
            $sqlCount + 2,
            $this->_sqlLoggerStack->queries,
            'The next two sql queries should have been cached, but were not.'
        );
    }

    public function testEnableResultCache(): void
    {
        $cache = new ArrayCache();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->enableResultCache();
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->getResult();

        self::assertTrue($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl(new ArrayCache());
    }

    public function testEnableResultCacheWithIterable(): void
    {
        $cache            = new ArrayCache();
        $expectedSQLCount = count($this->_sqlLoggerStack->queries) + 1;

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->enableResultCache();
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_iterable_result_cache_id');
        iterator_to_array($query->toIterable());

        $this->_em->clear();

        self::assertCount(
            $expectedSQLCount,
            $this->_sqlLoggerStack->queries
        );
        self::assertTrue($cache->contains('testing_iterable_result_cache_id'));

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->enableResultCache();
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_iterable_result_cache_id');
        iterator_to_array($query->toIterable());

        self::assertCount(
            $expectedSQLCount,
            $this->_sqlLoggerStack->queries,
            'Expected query to be cached'
        );

        $this->_em->getConfiguration()->setResultCacheImpl(new ArrayCache());
    }

    /**
     * @group DDC-1026
     */
    public function testEnableResultCacheParams(): void
    {
        $cache    = new ArrayCache();
        $sqlCount = count($this->_sqlLoggerStack->queries);
        $query    = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux WHERE ux.id = ?1');

        $query->setResultCacheDriver($cache);
        $query->enableResultCache();

        // these queries should result in cache miss:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        self::assertCount(
            $sqlCount + 2,
            $this->_sqlLoggerStack->queries,
            'Two non-cached queries.'
        );

        // these two queries should actually be cached, as they repeat previous ones:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        self::assertCount(
            $sqlCount + 2,
            $this->_sqlLoggerStack->queries,
            'The next two sql queries should have been cached, but were not.'
        );
    }

    public function testDisableResultCache(): void
    {
        $cache = new ArrayCache();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->disableResultCache();
        $query->getResult();

        self::assertFalse($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl(new ArrayCache());
    }

    public function testNativeQueryResultCaching(): NativeQuery
    {
        $cache = new ArrayCache();
        $rsm   = new ResultSetMapping();

        $rsm->addScalarResult('id', 'u', 'integer');

        $query = $this->_em->createNativeQuery('select u.id FROM cms_users u WHERE u.id = ?', $rsm);

        $query->setParameter(1, 10);
        $query->setResultCacheDriver($cache)->enableResultCache();

        self::assertEquals(0, $this->getCacheSize($cache));

        $query->getResult();

        self::assertEquals(1, $this->getCacheSize($cache));

        return $query;
    }

    /**
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheNotDependsOnQueryHints(NativeQuery $query): void
    {
        $cache      = $query->getResultCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->setHint('foo', 'bar');
        $query->getResult();

        self::assertEquals($cacheCount, $this->getCacheSize($cache));
    }

    /**
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheDependsOnParameters(NativeQuery $query): void
    {
        $cache      = $query->getResultCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        $query->setParameter(1, 50);
        $query->getResult();

        self::assertEquals($cacheCount + 1, $this->getCacheSize($cache));
    }

    /**
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheNotDependsOnHydrationMode(NativeQuery $query): void
    {
        $cache      = $query->getResultCacheDriver();
        $cacheCount = $this->getCacheSize($cache);

        self::assertNotEquals(Query::HYDRATE_ARRAY, $query->getHydrationMode());
        $query->getArrayResult();

        self::assertEquals($cacheCount, $this->getCacheSize($cache));
    }

    /**
     * @group DDC-909
     */
    public function testResultCacheWithObjectParameter(): void
    {
        $user1           = new CmsUser();
        $user1->name     = 'Roman';
        $user1->username = 'romanb';
        $user1->status   = 'dev';

        $user2           = new CmsUser();
        $user2->name     = 'Benjamin';
        $user2->username = 'beberlei';
        $user2->status   = 'dev';

        $article        = new CmsArticle();
        $article->text  = 'foo';
        $article->topic = 'baz';
        $article->user  = $user1;

        $this->_em->persist($article);
        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();

        $query = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query->setParameter(1, $user1);

        $cache = new ArrayCache();

        $query->setResultCacheDriver($cache)->enableResultCache();

        $articles = $query->getResult();

        self::assertEquals(1, count($articles));
        self::assertEquals('baz', $articles[0]->topic);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query2->setParameter(1, $user1);

        $query2->setResultCacheDriver($cache)->enableResultCache();

        $articles = $query2->getResult();

        self::assertEquals(1, count($articles));
        self::assertEquals('baz', $articles[0]->topic);

        $query3 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query3->setParameter(1, $user2);

        $query3->setResultCacheDriver($cache)->enableResultCache();

        $articles = $query3->getResult();

        self::assertEquals(0, count($articles));
    }
}
