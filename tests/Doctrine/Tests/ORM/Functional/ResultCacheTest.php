<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function assert;
use function count;
use function iterator_to_array;

class ResultCacheTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
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

        $cache = DoctrineProvider::wrap(new ArrayAdapter());

        $query->setResultCacheDriver($cache)->setResultCacheId('my_cache_id');

        self::assertFalse($cache->contains('my_cache_id'));

        $users = $query->getResult();

        self::assertTrue($cache->contains('my_cache_id'));
        self::assertCount(1, $users);
        self::assertEquals('Roman', $users[0]->name);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query2->setResultCacheDriver($cache)->setResultCacheId('my_cache_id');

        $users = $query2->getResult();

        self::assertTrue($cache->contains('my_cache_id'));
        self::assertCount(1, $users);
        self::assertEquals('Roman', $users[0]->name);
    }

    public function testSetResultCacheId(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');

        self::assertFalse($cache->contains('testing_result_cache_id'));

        $query->getResult();

        self::assertTrue($cache->contains('testing_result_cache_id'));
    }

    public function testUseResultCacheTrue(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->useResultCache(true);
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->getResult();

        self::assertTrue($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl($this->createMock(Cache::class));
    }

    public function testUseResultCacheFalse(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->useResultCache(false);
        $query->getResult();

        self::assertFalse($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl($this->createMock(Cache::class));
    }

    /**
     * @group DDC-1026
     */
    public function testUseResultCacheParams(): void
    {
        $cache    = DoctrineProvider::wrap(new ArrayAdapter());
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
        $cache = DoctrineProvider::wrap(new ArrayAdapter());
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->enableResultCache();
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->getResult();

        self::assertTrue($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl($this->createMock(Cache::class));
    }

    public function testEnableResultCacheWithIterable(): void
    {
        $cache            = DoctrineProvider::wrap(new ArrayAdapter());
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

        $this->_em->getConfiguration()->setResultCacheImpl($this->createMock(Cache::class));
    }

    /**
     * @group DDC-1026
     */
    public function testEnableResultCacheParams(): void
    {
        $cache    = DoctrineProvider::wrap(new ArrayAdapter());
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
        $cache = DoctrineProvider::wrap(new ArrayAdapter());
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->disableResultCache();
        $query->getResult();

        self::assertFalse($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl($this->createMock(Cache::class));
    }

    public function testNativeQueryResultCaching(): array
    {
        $adapter = new ArrayAdapter();
        $cache   = DoctrineProvider::wrap($adapter);
        $rsm     = new ResultSetMapping();

        $rsm->addScalarResult('id', 'u', 'integer');

        $query = $this->_em->createNativeQuery('select u.id FROM cms_users u WHERE u.id = ?', $rsm);

        $query->setParameter(1, 10);
        $query->setResultCacheDriver($cache)->enableResultCache();

        self::assertCount(0, $adapter->getValues());

        $query->getResult();

        self::assertCount(2, $adapter->getValues());

        return [$query, $adapter];
    }

    /**
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheNotDependsOnQueryHints(array $previous): void
    {
        [$query, $adapter] = $previous;
        assert($query instanceof NativeQuery);
        assert($adapter instanceof ArrayAdapter);

        $cacheCount = count($adapter->getValues());

        $query->setHint('foo', 'bar');
        $query->getResult();

        self::assertCount($cacheCount, $adapter->getValues());
    }

    /**
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheDependsOnParameters(array $previous): void
    {
        [$query, $adapter] = $previous;
        assert($query instanceof NativeQuery);
        assert($adapter instanceof ArrayAdapter);

        $cacheCount = count($adapter->getValues());

        $query->setParameter(1, 50);
        $query->getResult();

        self::assertCount($cacheCount + 1, $adapter->getValues());
    }

    /**
     * @depends testNativeQueryResultCaching
     */
    public function testResultCacheNotDependsOnHydrationMode(array $previous): void
    {
        [$query, $adapter] = $previous;
        assert($query instanceof NativeQuery);
        assert($adapter instanceof ArrayAdapter);

        $cacheCount = count($adapter->getValues());

        self::assertNotEquals(Query::HYDRATE_ARRAY, $query->getHydrationMode());
        $query->getArrayResult();

        self::assertCount($cacheCount, $adapter->getValues());
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

        $cache = new ArrayAdapter();

        $query->setResultCacheDriver(DoctrineProvider::wrap($cache))->enableResultCache();

        $articles = $query->getResult();

        self::assertCount(1, $articles);
        self::assertEquals('baz', $articles[0]->topic);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query2->setParameter(1, $user1);

        $query2->setResultCacheDriver(DoctrineProvider::wrap($cache))->enableResultCache();

        $articles = $query2->getResult();

        self::assertCount(1, $articles);
        self::assertEquals('baz', $articles[0]->topic);

        $query3 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query3->setParameter(1, $user2);

        $query3->setResultCacheDriver(DoctrineProvider::wrap($cache))->enableResultCache();

        $articles = $query3->getResult();

        self::assertCount(0, $articles);
    }
}
