<?php

namespace Doctrine\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

/**
 * Base testcase class for all ORM testcases.
 */
abstract class OrmTestCase extends DoctrineTestCase
{
    /** The metadata cache that is shared between all ORM tests (except functional tests). */
    private static $_metadataCacheImpl = null;
    /** The query cache that is shared between all ORM tests (except functional tests). */
    private static $_queryCacheImpl = null;

    /**
     * @param array $paths
     * @return \Doctrine\Common\Annotations\AnnotationReader
     */
    protected function createAnnotationDriver($paths = array(), $alias = null)
    {
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.2.0-DEV', '>=')) {
            // Register the ORM Annotations in the AnnotationRegistry
            AnnotationRegistry::registerFile(__DIR__ . '/../../../lib/Doctrine/ORM//Mapping/Driver/DoctrineAnnotations.php');

            $reader = new SimpleAnnotationReader();
            $reader->addNamespace('Doctrine\ORM\Mapping');
            $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());
        } else if (version_compare(\Doctrine\Common\Version::VERSION, '2.1.0-BETA3-DEV', '>=')) {
            $reader = new \Doctrine\Common\Annotations\AnnotationReader();
            $reader->setIgnoreNotImportedAnnotations(true);
            $reader->setEnableParsePhpImports(false);
            if ($alias) {
                $reader->setAnnotationNamespaceAlias('Doctrine\ORM\Mapping\\', $alias);
            } else {
                $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
            }
            $reader = new \Doctrine\Common\Annotations\CachedReader(
                new \Doctrine\Common\Annotations\IndexedReader($reader), new ArrayCache()
            );
        } else {
            $reader = new \Doctrine\Common\Annotations\AnnotationReader();
            if ($alias) {
                $reader->setAnnotationNamespaceAlias('Doctrine\ORM\Mapping\\', $alias);
            } else {
                $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
            }
        }
        return new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, (array)$paths);
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function _getTestEntityManager($conn = null, $conf = null, $eventManager = null, $withSharedMetadata = true)
    {
        $config = new \Doctrine\ORM\Configuration();
        if($withSharedMetadata) {
            $config->setMetadataCacheImpl(self::getSharedMetadataCacheImpl());
        } else {
            $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        }

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $config->setQueryCacheImpl(self::getSharedQueryCacheImpl());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new \Doctrine\Common\EventManager();
        if ($conn === null) {
            $conn = array(
                'driverClass' => 'Doctrine\Tests\Mocks\DriverMock',
                'wrapperClass' => 'Doctrine\Tests\Mocks\ConnectionMock',
                'user' => 'john',
                'password' => 'wayne'
            );
        }
        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, $eventManager);
        }
        return \Doctrine\Tests\Mocks\EntityManagerMock::create($conn, $config, $eventManager);
    }

    private static function getSharedMetadataCacheImpl()
    {
        if (self::$_metadataCacheImpl === null) {
            self::$_metadataCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }
        return self::$_metadataCacheImpl;
    }

    private static function getSharedQueryCacheImpl()
    {
        if (self::$_queryCacheImpl === null) {
            self::$_queryCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }
        return self::$_queryCacheImpl;
    }
}
