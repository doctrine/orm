<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function assert;
use function count;
use function iterator_to_array;
use function sprintf;

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

        $cache = new ArrayAdapter();

        $this->setResultCache($query, $cache);
        $query->setResultCacheId('my_cache_id');

        self::assertCacheDoesNotHaveItem('my_cache_id', $cache);

        $users = $query->getResult();

        self::assertCacheHasItem('my_cache_id', $cache);
        self::assertCount(1, $users);
        self::assertEquals('Roman', $users[0]->name);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $this->setResultCache($query2, $cache);
        $query2->setResultCacheId('my_cache_id');

        $users = $query2->getResult();

        self::assertCacheHasItem('my_cache_id', $cache);
        self::assertCount(1, $users);
        self::assertEquals('Roman', $users[0]->name);
    }

    public function testSetResultCacheId(): void
    {
        $cache = new ArrayAdapter();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $this->setResultCache($query, $cache);
        $query->setResultCacheId('testing_result_cache_id');

        self::assertCacheDoesNotHaveItem('testing_result_cache_id', $cache);

        $query->getResult();

        self::assertCacheHasItem('testing_result_cache_id', $cache);
    }

    #[Group('DDC-1026')]
    public function testUseResultCacheParams(): void
    {
        $cache = new ArrayAdapter();
        $this->getQueryLog()->reset()->enable();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux WHERE ux.id = ?1');

        $this->setResultCache($query, $cache);

        // these queries should result in cache miss:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        $this->assertQueryCount(2, 'Two non-cached queries.');

        // these two queries should actually be cached, as they repeat previous ones:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        $this->assertQueryCount(2, 'The next two sql queries should have been cached, but were not.');
    }

    public function testEnableResultCache(): void
    {
        $cache = new ArrayAdapter();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $query->enableResultCache();
        $this->setResultCache($query, $cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->getResult();

        self::assertCacheHasItem('testing_result_cache_id', $cache);

        $this->resetCache();
    }

    public function testEnableResultCacheWithIterable(): void
    {
        $cache = new ArrayAdapter();
        $this->getQueryLog()->reset()->enable();

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->enableResultCache();
        $this->setResultCache($query, $cache);
        $query->setResultCacheId('testing_iterable_result_cache_id');
        iterator_to_array($query->toIterable());

        $this->_em->clear();

        $this->assertQueryCount(1);
        self::assertCacheHasItem('testing_iterable_result_cache_id', $cache);

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->enableResultCache();
        $this->setResultCache($query, $cache);
        $query->setResultCacheId('testing_iterable_result_cache_id');
        iterator_to_array($query->toIterable());

        $this->assertQueryCount(1, 'Expected query to be cached');

        $this->resetCache();
    }

    #[Group('DDC-1026')]
    public function testEnableResultCacheParams(): void
    {
        $cache = new ArrayAdapter();
        $this->getQueryLog()->reset()->enable();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux WHERE ux.id = ?1');

        $this->setResultCache($query, $cache);
        $query->enableResultCache();

        // these queries should result in cache miss:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        $this->assertQueryCount(2, 'Two non-cached queries.');

        // these two queries should actually be cached, as they repeat previous ones:
        $query->setParameter(1, 1);
        $query->getResult();
        $query->setParameter(1, 2);
        $query->getResult();

        $this->assertQueryCount(2, 'The next two sql queries should have been cached, but were not.');
    }

    public function testDisableResultCache(): void
    {
        $cache = new ArrayAdapter();
        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');

        $this->setResultCache($query, $cache);
        $query->setResultCacheId('testing_result_cache_id');
        $query->disableResultCache();
        $query->getResult();

        self::assertFalse($cache->hasItem('testing_result_cache_id'));

        $this->resetCache();
    }

    public function testNativeQueryResultCaching(): array
    {
        $cache = new ArrayAdapter();
        $rsm   = new ResultSetMapping();

        $rsm->addScalarResult('id', 'u', 'integer');

        $query = $this->_em->createNativeQuery('select u.id FROM cms_users u WHERE u.id = ?', $rsm);

        $query->setParameter(1, 10);
        $this->setResultCache($query, $cache);
        $query->enableResultCache();

        self::assertEmpty($cache->getValues());

        $query->getResult();

        self::assertNotEmpty($cache->getValues());

        return [$query, $cache];
    }

    #[Depends('testNativeQueryResultCaching')]
    public function testResultCacheNotDependsOnQueryHints(array $previous): void
    {
        [$query, $cache] = $previous;
        assert($query instanceof NativeQuery);
        assert($cache instanceof ArrayAdapter);

        $cacheCount = count($cache->getValues());

        $query->setHint('foo', 'bar');
        $query->getResult();

        self::assertCount($cacheCount, $cache->getValues());
    }

    #[Depends('testNativeQueryResultCaching')]
    public function testResultCacheDependsOnParameters(array $previous): void
    {
        [$query, $cache] = $previous;
        assert($query instanceof NativeQuery);
        assert($cache instanceof ArrayAdapter);

        $cacheCount = count($cache->getValues());

        $query->setParameter(1, 50);
        $query->getResult();

        self::assertCount($cacheCount + 1, $cache->getValues());
    }

    #[Depends('testNativeQueryResultCaching')]
    public function testResultCacheNotDependsOnHydrationMode(array $previous): void
    {
        [$query, $cache] = $previous;
        assert($query instanceof NativeQuery);
        assert($cache instanceof ArrayAdapter);

        $cacheCount = count($cache->getValues());

        self::assertNotEquals(Query::HYDRATE_ARRAY, $query->getHydrationMode());
        $query->getArrayResult();

        self::assertCount($cacheCount, $cache->getValues());
    }

    #[Group('DDC-909')]
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

        $this->setResultCache($query, $cache);
        $query->enableResultCache();

        $articles = $query->getResult();

        self::assertCount(1, $articles);
        self::assertEquals('baz', $articles[0]->topic);

        $this->_em->clear();

        $query2 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query2->setParameter(1, $user1);

        $this->setResultCache($query2, $cache);
        $query2->enableResultCache();

        $articles = $query2->getResult();

        self::assertCount(1, $articles);
        self::assertEquals('baz', $articles[0]->topic);

        $query3 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = ?1');
        $query3->setParameter(1, $user2);

        $this->setResultCache($query3, $cache);
        $query3->enableResultCache();

        $articles = $query3->getResult();

        self::assertCount(0, $articles);
    }

    private function setResultCache(AbstractQuery $query, CacheItemPoolInterface $cache): void
    {
        $query->setResultCacheProfile(
            (new QueryCacheProfile())
                ->setResultCache($cache),
        );
    }

    private static function assertCacheHasItem(string $key, CacheItemPoolInterface $cache): void
    {
        self::assertTrue(
            $cache->hasItem($key),
            sprintf('Failed asserting that a given cache contains the key "%s".', $key),
        );
    }

    private function resetCache(): void
    {
        $this->_em->getConfiguration()->setResultCache(new ArrayAdapter());
    }

    private static function assertCacheDoesNotHaveItem(string $key, CacheItemPoolInterface $cache): void
    {
        self::assertFalse(
            $cache->hasItem($key),
            sprintf('Failed asserting that a given cache does not contain the key "%s".', $key),
        );
    }
}
