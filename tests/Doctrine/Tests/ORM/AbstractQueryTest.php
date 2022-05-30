<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Result;
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

    public function testItMakesHydrationCacheProfilesAwareOfTheResultCache(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $configuration = new Configuration();
        $configuration->setHydrationCache($cache);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query        = new TestQuery($entityManager);
        $cacheProfile = new QueryCacheProfile();

        $query->setHydrationCacheProfile($cacheProfile);
        self::assertSame($cache, $query->getHydrationCacheProfile()->getResultCache());
    }

    public function testItMakesResultCacheProfilesAwareOfTheResultCache(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $configuration = new Configuration();
        $configuration->setResultCache($cache);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $query = new TestQuery($entityManager);
        $query->setResultCacheProfile(new QueryCacheProfile());

        self::assertSame($cache, $query->getResultCache());
    }

    /**
     * @dataProvider provideSettersWithDeprecatedDefault
     */
    public function testCallingSettersWithoutArgumentsIsDeprecated(string $setter): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn(new Configuration());
        $query = $this->getMockForAbstractClass(AbstractQuery::class, [$entityManager]);

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9791');
        $query->$setter();
    }

    /**
     * @return array<string, array{string}>
     */
    public function provideSettersWithDeprecatedDefault(): array
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
        $query = new TestQuery($entityManager);
        $query->setResultCache($cache);

        self::assertSame($cache, $query->getResultCache());
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

class TestQuery extends AbstractQuery
{
    public function getSQL(): string
    {
        return '';
    }

    protected function _doExecute(): Result|int
    {
        return 0;
    }

    public function getResultCache(): ?CacheItemPoolInterface
    {
        return $this->_queryCacheProfile->getResultCache();
    }
}
