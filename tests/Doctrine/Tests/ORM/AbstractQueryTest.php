<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

use function method_exists;

final class AbstractQueryTest extends TestCase
{
    use VerifyDeprecations;

    public function testItMakesHydrationCacheProfilesAwareOfTheResultCacheDriver(): void
    {
        if (method_exists(QueryCacheProfile::class, 'setResultCache')) {
            self::markTestSkipped('this test is meant for DBAL < 3.2');
        }

        $configuration = new Configuration();
        $configuration->setHydrationCacheImpl($this->createMock(Cache::class));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);
        $cacheProfile = new QueryCacheProfile();

        $query->setHydrationCacheProfile($cacheProfile);
        self::assertNotNull($query->getHydrationCacheProfile()->getResultCacheDriver());
    }

    /**
     * @requires function Doctrine\DBAL\Cache\QueryCacheProfile::setResultCache
     */
    public function testItMakesHydrationCacheProfilesAwareOfTheResultCache(): void
    {
        $configuration = new Configuration();
        $configuration->setHydrationCacheImpl($this->createMock(Cache::class));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);
        $cacheProfile = new QueryCacheProfile();

        $query->setHydrationCacheProfile($cacheProfile);
        self::assertNotNull($query->getHydrationCacheProfile()->getResultCache());
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/dbal/pull/4620');
    }

    public function testItMakesResultCacheProfilesAwareOfTheResultCacheDriver(): void
    {
        if (method_exists(QueryCacheProfile::class, 'setResultCache')) {
            self::markTestSkipped('this test is meant for DBAL < 3.2');
        }

        $configuration = new Configuration();
        $configuration->setResultCacheImpl($this->createMock(Cache::class));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);
        $cacheProfile = new QueryCacheProfile();

        $query->setResultCacheProfile($cacheProfile);
        self::assertNotNull($query->getResultCacheDriver());
    }

    /**
     * @requires function Doctrine\DBAL\Cache\QueryCacheProfile::setResultCache
     */
    public function testItMakesResultCacheProfilesAwareOfTheResultCache(): void
    {
        $configuration = new Configuration();
        $configuration->setResultCache($this->createMock(CacheItemPoolInterface::class));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);
        $cacheProfile = new QueryCacheProfile();

        $query->setResultCacheProfile($cacheProfile);
        self::assertNotNull($query->getResultCacheDriver());
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/dbal/pull/4620');
    }

    public function testSettingTheResultCacheIsPossibleWithoutCallingDeprecatedMethods(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn(new Configuration());
        $query = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);

        $query->setResultCache($this->createMock(CacheItemPoolInterface::class));
        self::assertNotNull($query->getResultCacheDriver());
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/dbal/pull/4620');
    }
}
