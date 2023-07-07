<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use LogicException;
use Psr\Cache\CacheItemPoolInterface;
use Redis;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

use function apcu_enabled;
use function class_exists;
use function extension_loaded;
use function md5;
use function sys_get_temp_dir;

final class ORMSetup
{
    /**
     * Creates a configuration with an annotation metadata driver.
     *
     * @deprecated Use another mapping driver.
     *
     * @param string[] $paths
     */
    public static function createAnnotationMetadataConfiguration(
        array $paths,
        bool $isDevMode = false,
        ?string $proxyDir = null,
        ?CacheItemPoolInterface $cache = null
    ): Configuration {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/10098',
            '%s is deprecated and will be removed in Doctrine ORM 3.0',
            __METHOD__
        );
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(self::createDefaultAnnotationDriver($paths));

        return $config;
    }

    /**
     * Adds a new default annotation driver with a correctly configured annotation reader.
     *
     * @deprecated Use another mapping driver.
     *
     * @param string[] $paths
     */
    public static function createDefaultAnnotationDriver(
        array $paths = [],
        ?CacheItemPoolInterface $cache = null,
        bool $reportFieldsWhereDeclared = false
    ): AnnotationDriver {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/10098',
            '%s is deprecated and will be removed in Doctrine ORM 3.0',
            __METHOD__
        );
        if (! class_exists(AnnotationReader::class)) {
            throw new LogicException(
                'The annotation metadata driver cannot be enabled because the "doctrine/annotations" library'
                . ' is not installed. Please run "composer require doctrine/annotations" or choose a different'
                . ' metadata driver.'
            );
        }

        $reader = new AnnotationReader();

        if ($cache === null && class_exists(ArrayAdapter::class)) {
            $cache = new ArrayAdapter();
        }

        if ($cache !== null) {
            $reader = new PsrCachedReader($reader, $cache);
        }

        return new AnnotationDriver($reader, $paths, $reportFieldsWhereDeclared);
    }

    /**
     * Creates a configuration with an attribute metadata driver.
     *
     * @param string[] $paths
     */
    public static function createAttributeMetadataConfiguration(
        array $paths,
        bool $isDevMode = false,
        ?string $proxyDir = null,
        ?CacheItemPoolInterface $cache = null
    ): Configuration {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new AttributeDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration with an XML metadata driver.
     *
     * @param string[] $paths
     */
    public static function createXMLMetadataConfiguration(
        array $paths,
        bool $isDevMode = false,
        ?string $proxyDir = null,
        ?CacheItemPoolInterface $cache = null,
        bool $isXsdValidationEnabled = false
    ): Configuration {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new XmlDriver($paths, XmlDriver::DEFAULT_FILE_EXTENSION, $isXsdValidationEnabled));

        return $config;
    }

    /**
     * Creates a configuration with a YAML metadata driver.
     *
     * @deprecated YAML metadata mapping is deprecated and will be removed in 3.0
     *
     * @param string[] $paths
     */
    public static function createYAMLMetadataConfiguration(
        array $paths,
        bool $isDevMode = false,
        ?string $proxyDir = null,
        ?CacheItemPoolInterface $cache = null
    ): Configuration {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/8465',
            'YAML mapping driver is deprecated and will be removed in Doctrine ORM 3.0, please migrate to attribute or XML driver.'
        );

        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new YamlDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration without a metadata driver.
     */
    public static function createConfiguration(
        bool $isDevMode = false,
        ?string $proxyDir = null,
        ?CacheItemPoolInterface $cache = null
    ): Configuration {
        $proxyDir = $proxyDir ?: sys_get_temp_dir();

        $cache = self::createCacheInstance($isDevMode, $proxyDir, $cache);

        $config = new Configuration();

        $config->setMetadataCache($cache);
        $config->setQueryCache($cache);
        $config->setResultCache($cache);
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace('DoctrineProxies');
        $config->setAutoGenerateProxyClasses($isDevMode);

        return $config;
    }

    private static function createCacheInstance(
        bool $isDevMode,
        string $proxyDir,
        ?CacheItemPoolInterface $cache
    ): CacheItemPoolInterface {
        if ($cache !== null) {
            return $cache;
        }

        if (! class_exists(ArrayAdapter::class)) {
            throw new RuntimeException(
                'The Doctrine setup tool cannot configure caches without symfony/cache.'
                . ' Please add symfony/cache as explicit dependency or pass your own cache implementation.'
            );
        }

        if ($isDevMode) {
            return new ArrayAdapter();
        }

        $namespace = 'dc2_' . md5($proxyDir);

        if (extension_loaded('apcu') && apcu_enabled()) {
            return new ApcuAdapter($namespace);
        }

        if (MemcachedAdapter::isSupported()) {
            return new MemcachedAdapter(MemcachedAdapter::createConnection('memcached://127.0.0.1'), $namespace);
        }

        if (extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect('127.0.0.1');

            return new RedisAdapter($redis, $namespace);
        }

        return new ArrayAdapter();
    }

    private function __construct()
    {
    }
}
