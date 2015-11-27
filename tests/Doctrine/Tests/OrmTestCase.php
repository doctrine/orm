<?php

namespace Doctrine\Tests;

use Doctrine\Common\Annotations;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Version;
use Doctrine\ORM\Cache\DefaultCacheFactory;

/**
 * Base testcase class for all ORM testcases.
 */
abstract class OrmTestCase extends DoctrineTestCase
{
    /**
     * The metadata cache that is shared between all ORM tests (except functional tests).
     *
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private static $_metadataCacheImpl = null;

    /**
     * The query cache that is shared between all ORM tests (except functional tests).
     *
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private static $_queryCacheImpl = null;

    /**
     * @var bool
     */
    protected $isSecondLevelCacheEnabled = false;

    /**
     * @var bool
     */
    protected $isSecondLevelCacheLogEnabled = false;

    /**
     * @var \Doctrine\ORM\Cache\CacheFactory
     */
    protected $secondLevelCacheFactory;

    /**
     * @var \Doctrine\ORM\Cache\Logging\StatisticsCacheLogger
     */
    protected $secondLevelCacheLogger;

    /**
     * @var \Doctrine\Common\Cache\Cache|null
     */
    protected $secondLevelCacheDriverImpl = null;

    /**
     * @param array $paths
     * @param mixed $alias
     *
     * @return \Doctrine\ORM\Mapping\Driver\AnnotationDriver
     */
    protected function createAnnotationDriver($paths = array(), $alias = null)
    {
        if (version_compare(Version::VERSION, '3.0.0', '>=')) {
            $reader = new Annotations\CachedReader(new Annotations\AnnotationReader(), new ArrayCache());
        } else if (version_compare(Version::VERSION, '2.2.0-DEV', '>=')) {
            // Register the ORM Annotations in the AnnotationRegistry
            $reader = new Annotations\SimpleAnnotationReader();

            $reader->addNamespace('Doctrine\ORM\Mapping');

            $reader = new Annotations\CachedReader($reader, new ArrayCache());
        } else if (version_compare(Version::VERSION, '2.1.0-BETA3-DEV', '>=')) {
            $reader = new Annotations\AnnotationReader();

            $reader->setIgnoreNotImportedAnnotations(true);
            $reader->setEnableParsePhpImports(false);

            if ($alias) {
                $reader->setAnnotationNamespaceAlias('Doctrine\ORM\Mapping\\', $alias);
            } else {
                $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
            }

            $reader = new Annotations\CachedReader(new Annotations\IndexedReader($reader), new ArrayCache());
        } else {
            $reader = new Annotations\AnnotationReader();

            if ($alias) {
                $reader->setAnnotationNamespaceAlias('Doctrine\ORM\Mapping\\', $alias);
            } else {
                $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
            }
        }

        Annotations\AnnotationRegistry::registerFile(__DIR__ . "/../../../lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");

        return new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, (array) $paths);
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     *
     * @param \Doctrine\DBAL\Connection|array    $conn
     * @param mixed                              $conf
     * @param \Doctrine\Common\EventManager|null $eventManager
     * @param bool                               $withSharedMetadata
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function _getTestEntityManager($conn = null, $conf = null, $eventManager = null, $withSharedMetadata = true)
    {
        $metadataCache = $withSharedMetadata
            ? self::getSharedMetadataCacheImpl()
            : new ArrayCache();

        $config = new \Doctrine\ORM\Configuration();

        $config->setMetadataCacheImpl($metadataCache);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), true));
        $config->setQueryCacheImpl(self::getSharedQueryCacheImpl());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(
            realpath(__DIR__ . '/Models/Cache')
        ), true));

        if ($this->isSecondLevelCacheEnabled) {

            $cacheConfig    = new \Doctrine\ORM\Cache\CacheConfiguration();
            $cache          = $this->getSharedSecondLevelCacheDriverImpl();
            $factory        = new DefaultCacheFactory($cacheConfig->getRegionsConfiguration(), $cache);

            $this->secondLevelCacheFactory = $factory;

            $cacheConfig->setCacheFactory($factory);
            $config->setSecondLevelCacheEnabled(true);
            $config->setSecondLevelCacheConfiguration($cacheConfig);
        }

        if ($conn === null) {
            $conn = array(
                'driverClass'  => 'Doctrine\Tests\Mocks\DriverMock',
                'wrapperClass' => 'Doctrine\Tests\Mocks\ConnectionMock',
                'user'         => 'john',
                'password'     => 'wayne'
            );
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, $eventManager);
        }

        return \Doctrine\Tests\Mocks\EntityManagerMock::create($conn, $config, $eventManager);
    }

    protected function enableSecondLevelCache($log = true)
    {
        $this->isSecondLevelCacheEnabled    = true;
        $this->isSecondLevelCacheLogEnabled = $log;
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    private static function getSharedMetadataCacheImpl()
    {
        if (self::$_metadataCacheImpl === null) {
            self::$_metadataCacheImpl = new ArrayCache();
        }

        return self::$_metadataCacheImpl;
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    private static function getSharedQueryCacheImpl()
    {
        if (self::$_queryCacheImpl === null) {
            self::$_queryCacheImpl = new ArrayCache();
        }

        return self::$_queryCacheImpl;
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    protected function getSharedSecondLevelCacheDriverImpl()
    {
        if ($this->secondLevelCacheDriverImpl === null) {
            $this->secondLevelCacheDriverImpl = new ArrayCache();
        }

        return $this->secondLevelCacheDriverImpl;
    }
}
