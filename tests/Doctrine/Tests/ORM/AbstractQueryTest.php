<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use stdClass;

final class AbstractQueryTest extends TestCase
{
    use VerifyDeprecations;

    /**
     * @requires function Doctrine\DBAL\Cache\QueryCacheProfile::getResultCacheDriver
     */
    public function testItMakesHydrationCacheProfilesAwareOfTheResultCacheDriver(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $configuration = new Configuration();
        $configuration->setHydrationCache($cache);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);
        $cacheProfile = new QueryCacheProfile();

        $query->setHydrationCacheProfile($cacheProfile);
        self::assertSame($cache, CacheAdapter::wrap($query->getHydrationCacheProfile()->getResultCacheDriver()));
    }

    /**
     * @requires function Doctrine\DBAL\Cache\QueryCacheProfile::getResultCache
     */
    public function testItMakesHydrationCacheProfilesAwareOfTheResultCache(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $configuration = new Configuration();
        $configuration->setHydrationCache($cache);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);
        $cacheProfile = new QueryCacheProfile();

        $query->setHydrationCacheProfile($cacheProfile);
        self::assertSame($cache, $query->getHydrationCacheProfile()->getResultCache());
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/dbal/pull/4620');
    }

    public function testItMakesResultCacheProfilesAwareOfTheResultCache(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $configuration = new Configuration();
        $configuration->setResultCache($cache);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);
        $cacheProfile = new QueryCacheProfile();

        $query->setResultCacheProfile($cacheProfile);
        self::assertSame($cache, CacheAdapter::wrap($query->getResultCacheDriver()));
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/dbal/pull/4620');
    }

    /** @dataProvider provideSettersWithDeprecatedDefault */
    public function testCallingSettersWithoutArgumentsIsDeprecated(string $setter): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn(new Configuration());
        $query = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9791');
        $query->$setter();
    }

    /** @return array<string, array{string}> */
    public static function provideSettersWithDeprecatedDefault(): array
    {
        return [
            'setHydrationCacheProfile' => ['setHydrationCacheProfile'],
            'setResultCache' => ['setResultCache'],
            'setResultCacheProfile' => ['setResultCacheProfile'],
        ];
    }

    public function testSettingTheResultCacheIsPossibleWithoutCallingDeprecatedMethods(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn(new Configuration());
        $query = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);

        $query->setResultCache($cache);
        self::assertSame($cache, CacheAdapter::wrap($query->getResultCacheDriver()));
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/dbal/pull/4620');
    }

    public function testSettingTheFetchModeToRandomIntegersIsDeprecated(): void
    {
        $query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false // no need to call the constructor
        );
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9777');
        $query->setFetchMode(stdClass::class, 'foo', 42);
    }
}
