<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Name\UnquotedIdentifierFolding;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\CacheFactory;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Tests\Mocks\EntityManagerMock;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function enum_exists;
use function method_exists;
use function realpath;
use function sprintf;

/**
 * Base testcase class for all ORM testcases.
 */
abstract class OrmTestCase extends TestCase
{
    /**
     * The metadata cache that is shared between all ORM tests (except functional tests).
     */
    private static CacheItemPoolInterface|null $metadataCache = null;

    /**
     * The query cache that is shared between all ORM tests (except functional tests).
     */
    private static CacheItemPoolInterface|null $queryCache = null;

    /** @var bool */
    protected $isSecondLevelCacheEnabled = false;

    /** @var bool */
    protected $isSecondLevelCacheLogEnabled = false;

    /** @var CacheFactory */
    protected $secondLevelCacheFactory;

    /** @var StatisticsCacheLogger */
    protected $secondLevelCacheLogger;

    private CacheItemPoolInterface|null $secondLevelCache = null;

    protected function createAttributeDriver(array $paths = []): AttributeDriver
    {
        return new AttributeDriver($paths);
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     */
    protected function getTestEntityManager(): EntityManagerMock
    {
        return $this->buildTestEntityManagerWithPlatform(
            $this->createConnectionMock($this->createPlatformMock()),
        );
    }

    protected function createTestEntityManagerWithConnection(Connection $connection): EntityManagerMock
    {
        return $this->buildTestEntityManagerWithPlatform($connection);
    }

    protected function createTestEntityManagerWithPlatform(AbstractPlatform $platform): EntityManagerMock
    {
        return $this->buildTestEntityManagerWithPlatform(
            $this->createConnectionMock($platform),
        );
    }

    private function buildTestEntityManagerWithPlatform(Connection $connection): EntityManagerMock
    {
        $metadataCache = self::getSharedMetadataCacheImpl();

        $config = new Configuration();

        TestUtil::configureProxies($config);
        $config->setMetadataCache($metadataCache);
        $config->setQueryCache(self::getSharedQueryCache());
        $config->setMetadataDriverImpl(new AttributeDriver([
            realpath(__DIR__ . '/Models/Cache'),
        ], true));

        if ($this->isSecondLevelCacheEnabled) {
            $cacheConfig = new CacheConfiguration();
            $factory     = new DefaultCacheFactory(
                $cacheConfig->getRegionsConfiguration(),
                $this->getSharedSecondLevelCache(),
            );

            $this->secondLevelCacheFactory = $factory;

            $cacheConfig->setCacheFactory($factory);
            $config->setSecondLevelCacheEnabled();
            $config->setSecondLevelCacheConfiguration($cacheConfig);
        }

        return new EntityManagerMock($connection, $config);
    }

    protected function enableSecondLevelCache(bool $log = true): void
    {
        $this->isSecondLevelCacheEnabled    = true;
        $this->isSecondLevelCacheLogEnabled = $log;
    }

    private static function getSharedMetadataCacheImpl(): CacheItemPoolInterface
    {
        return self::$metadataCache
            ?? self::$metadataCache = new ArrayAdapter();
    }

    private static function getSharedQueryCache(): CacheItemPoolInterface
    {
        return self::$queryCache
            ?? self::$queryCache = new ArrayAdapter();
    }

    protected function getSharedSecondLevelCache(): CacheItemPoolInterface
    {
        return $this->secondLevelCache
            ?? $this->secondLevelCache = new ArrayAdapter();
    }

    private function createConnectionMock(AbstractPlatform $platform): Connection
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([[], $this->createDriverMock($platform)])
            ->onlyMethods(['quote'])
            ->getMockForAbstractClass();
        $connection->method('quote')->willReturnCallback(static fn (string $input) => sprintf("'%s'", $input));

        return $connection;
    }

    private function createPlatformMock(): AbstractPlatform
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('createSchemaConfig')
            ->willReturn(new SchemaConfig());

        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->setConstructorArgs(enum_exists(UnquotedIdentifierFolding::class) ? [UnquotedIdentifierFolding::UPPER] : [])
            ->onlyMethods(['supportsIdentityColumns', 'createSchemaManager'])
            ->getMockForAbstractClass();
        $platform->method('supportsIdentityColumns')
            ->willReturn(true);
        $platform->method('createSchemaManager')
            ->willReturn($schemaManager);

        return $platform;
    }

    private function createDriverMock(AbstractPlatform $platform): Driver
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')
            ->willReturn(false);

        $connection = $this->createMock(Driver\Connection::class);
        $connection->method('query')
            ->willReturn($result);

        $driver = $this->createMock(Driver::class);
        $driver->method('connect')
            ->willReturn($connection);
        $driver->method('getDatabasePlatform')
            ->willReturn($platform);

        if (method_exists(Driver::class, 'getSchemaManager')) {
            $driver->method('getSchemaManager')
                ->willReturnCallback([$platform, 'createSchemaManager']);
        }

        return $driver;
    }
}
