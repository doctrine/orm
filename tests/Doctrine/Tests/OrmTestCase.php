<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use Doctrine\Common\Annotations;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\CacheFactory;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function realpath;

/**
 * Base testcase class for all ORM testcases.
 */
abstract class OrmTestCase extends DoctrineTestCase
{
    /**
     * The metadata cache that is shared between all ORM tests (except functional tests).
     *
     * @var CacheItemPoolInterface|null
     */
    private static $metadataCache = null;

    /**
     * The query cache that is shared between all ORM tests (except functional tests).
     *
     * @var CacheItemPoolInterface|null
     */
    private static $queryCache = null;

    /** @var bool */
    protected $isSecondLevelCacheEnabled = false;

    /** @var bool */
    protected $isSecondLevelCacheLogEnabled = false;

    /** @var CacheFactory */
    protected $secondLevelCacheFactory;

    /** @var StatisticsCacheLogger */
    protected $secondLevelCacheLogger;

    /** @var CacheItemPoolInterface|null */
    private $secondLevelCache = null;

    protected function createAnnotationDriver(array $paths = []): AnnotationDriver
    {
        return new AnnotationDriver(
            new Annotations\PsrCachedReader(new Annotations\AnnotationReader(), new ArrayAdapter()),
            $paths
        );
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     */
    protected function getTestEntityManager(?Connection $connection = null): EntityManagerMock
    {
        $metadataCache = self::getSharedMetadataCacheImpl();

        $config = new Configuration();

        $config->setMetadataCache($metadataCache);
        $config->setQueryCache(self::getSharedQueryCache());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $config->setMetadataDriverImpl(ORMSetup::createDefaultAnnotationDriver([
            realpath(__DIR__ . '/Models/Cache'),
        ]));

        if ($this->isSecondLevelCacheEnabled) {
            $cacheConfig = new CacheConfiguration();
            $factory     = new DefaultCacheFactory(
                $cacheConfig->getRegionsConfiguration(),
                $this->getSharedSecondLevelCache()
            );

            $this->secondLevelCacheFactory = $factory;

            $cacheConfig->setCacheFactory($factory);
            $config->setSecondLevelCacheEnabled();
            $config->setSecondLevelCacheConfiguration($cacheConfig);
        }

        if ($connection === null) {
            $connection = new Mocks\ConnectionMock([], $this->createDriverMock());
        }

        return EntityManagerMock::create($connection, $config);
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

    private function createDriverMock(): Driver
    {
        $result = $this->createMock(Driver\Result::class);
        $result->method('fetchAssociative')
            ->willReturn(false);

        $connection = $this->createMock(Driver\Connection::class);
        $connection->method('query')
            ->willReturn($result);

        $driver = $this->createMock(Driver::class);
        $driver->method('connect')
            ->willReturn($connection);
        $driver->method('getSchemaManager')
            ->willReturn($this->createMock(AbstractSchemaManager::class));

        return $driver;
    }
}
