<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Memcached;
use Redis;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

use function class_exists;
use function extension_loaded;
use function md5;
use function sys_get_temp_dir;

/**
 * Convenience class for setting up Doctrine from different installations and configurations.
 *
 * @deprecated Use {@see DoctrineSetup} instead.
 */
class Setup
{
    /**
     * Creates a configuration with an annotation metadata driver.
     *
     * @param string[]    $paths
     * @param bool        $isDevMode
     * @param string|null $proxyDir
     * @param bool        $useSimpleAnnotationReader
     *
     * @return Configuration
     */
    public static function createAnnotationMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, ?Cache $cache = null, $useSimpleAnnotationReader = true)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9443',
            '%s is deprecated and will be removed in Doctrine 3.0, please use %s instead.',
            self::class,
            DoctrineSetup::class
        );

        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths, $useSimpleAnnotationReader));

        return $config;
    }

    /**
     * Creates a configuration with an attribute metadata driver.
     *
     * @param string[]    $paths
     * @param bool        $isDevMode
     * @param string|null $proxyDir
     */
    public static function createAttributeMetadataConfiguration(
        array $paths,
        $isDevMode = false,
        $proxyDir = null,
        ?Cache $cache = null
    ): Configuration {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9443',
            '%s is deprecated and will be removed in Doctrine 3.0, please use %s instead.',
            self::class,
            DoctrineSetup::class
        );

        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new AttributeDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration with an XML metadata driver.
     *
     * @param string[]    $paths
     * @param bool        $isDevMode
     * @param string|null $proxyDir
     *
     * @return Configuration
     */
    public static function createXMLMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, ?Cache $cache = null)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9443',
            '%s is deprecated and will be removed in Doctrine 3.0, please use %s instead.',
            self::class,
            DoctrineSetup::class
        );

        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new XmlDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration without a metadata driver.
     *
     * @param bool        $isDevMode
     * @param string|null $proxyDir
     *
     * @return Configuration
     */
    public static function createConfiguration($isDevMode = false, $proxyDir = null, ?Cache $cache = null)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9443',
            '%s is deprecated and will be removed in Doctrine 3.0, please use %s instead.',
            self::class,
            DoctrineSetup::class
        );

        $proxyDir = $proxyDir ?: sys_get_temp_dir();

        $cache = self::createCacheConfiguration($isDevMode, $proxyDir, $cache);

        $config = new Configuration();

        $config->setMetadataCache(CacheAdapter::wrap($cache));
        $config->setQueryCache(CacheAdapter::wrap($cache));
        $config->setResultCache(CacheAdapter::wrap($cache));
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace('DoctrineProxies');
        $config->setAutoGenerateProxyClasses($isDevMode);

        return $config;
    }

    private static function createCacheConfiguration(bool $isDevMode, string $proxyDir, ?Cache $cache): Cache
    {
        $cache = self::createCacheInstance($isDevMode, $cache);

        if (! $cache instanceof CacheProvider) {
            return $cache;
        }

        $namespace = $cache->getNamespace();

        if ($namespace !== '') {
            $namespace .= ':';
        }

        $cache->setNamespace($namespace . 'dc2_' . md5($proxyDir) . '_'); // to avoid collisions

        return $cache;
    }

    private static function createCacheInstance(bool $isDevMode, ?Cache $cache): Cache
    {
        if ($cache !== null) {
            return $cache;
        }

        if (! class_exists(ArrayCache::class) && ! class_exists(ArrayAdapter::class)) {
            throw new RuntimeException('Setup tool cannot configure caches without doctrine/cache 1.11 or symfony/cache. Please add an explicit dependency to either library.');
        }

        if ($isDevMode === true) {
            $cache = class_exists(ArrayCache::class) ? new ArrayCache() : new ArrayAdapter();
        } elseif (extension_loaded('apcu')) {
            $cache = class_exists(ApcuCache::class) ? new ApcuCache() : new ApcuAdapter();
        } elseif (extension_loaded('memcached')) {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            if (class_exists(MemcachedCache::class)) {
                $cache = new MemcachedCache();
                $cache->setMemcached($memcached);
            } else {
                $cache = new MemcachedAdapter($memcached);
            }
        } elseif (extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect('127.0.0.1');

            if (class_exists(RedisCache::class)) {
                $cache = new RedisCache();
                $cache->setRedis($redis);
            } else {
                $cache = new RedisAdapter($redis);
            }
        } else {
            $cache = class_exists(ArrayCache::class) ? new ArrayCache() : new ArrayAdapter();
        }

        return $cache instanceof Cache ? $cache : DoctrineProvider::wrap($cache);
    }
}
