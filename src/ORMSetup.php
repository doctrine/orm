<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
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
     * Creates a configuration with an attribute metadata driver.
     *
     * @param string[] $paths
     */
    public static function createAttributeMetadataConfiguration(
        array $paths,
        bool $isDevMode = false,
        string|null $proxyDir = null,
        CacheItemPoolInterface|null $cache = null,
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
        string|null $proxyDir = null,
        CacheItemPoolInterface|null $cache = null,
        bool $isXsdValidationEnabled = true,
    ): Configuration {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new XmlDriver($paths, XmlDriver::DEFAULT_FILE_EXTENSION, $isXsdValidationEnabled));

        return $config;
    }

    /**
     * Creates a configuration without a metadata driver.
     */
    public static function createConfiguration(
        bool $isDevMode = false,
        string|null $proxyDir = null,
        CacheItemPoolInterface|null $cache = null,
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
        CacheItemPoolInterface|null $cache,
    ): CacheItemPoolInterface {
        if ($cache !== null) {
            return $cache;
        }

        if (! class_exists(ArrayAdapter::class)) {
            throw new RuntimeException(
                'The Doctrine setup tool cannot configure caches without symfony/cache.'
                . ' Please add symfony/cache as explicit dependency or pass your own cache implementation.',
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
