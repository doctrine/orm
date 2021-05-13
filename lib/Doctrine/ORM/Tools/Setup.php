<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\ClassLoader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
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
use function method_exists;
use function sys_get_temp_dir;

/**
 * Convenience class for setting up Doctrine from different installations and configurations.
 */
class Setup
{
    /**
     * Use this method to register all autoloads for a downloaded Doctrine library.
     * Pick the directory the library was uncompressed into.
     *
     * @param string $directory
     *
     * @return void
     */
    public static function registerAutoloadDirectory($directory)
    {
        if (! class_exists('Doctrine\Common\ClassLoader', false)) {
            require_once $directory . '/Doctrine/Common/ClassLoader.php';
        }

        $loader = new ClassLoader('Doctrine', $directory);
        $loader->register();

        $loader = new ClassLoader('Symfony\Component', $directory . '/Doctrine');
        $loader->register();
    }

    /**
     * Creates a configuration with an annotation metadata driver.
     *
     * @param mixed[] $paths
     * @param bool    $isDevMode
     * @param string  $proxyDir
     * @param bool    $useSimpleAnnotationReader
     *
     * @return Configuration
     */
    public static function createAnnotationMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, ?Cache $cache = null, $useSimpleAnnotationReader = true)
    {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths, $useSimpleAnnotationReader));

        return $config;
    }

    /**
     * Creates a configuration with a xml metadata driver.
     *
     * @param mixed[] $paths
     * @param bool    $isDevMode
     * @param string  $proxyDir
     *
     * @return Configuration
     */
    public static function createXMLMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, ?Cache $cache = null)
    {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new XmlDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration with a yaml metadata driver.
     *
     * @param mixed[] $paths
     * @param bool    $isDevMode
     * @param string  $proxyDir
     *
     * @return Configuration
     */
    public static function createYAMLMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, ?Cache $cache = null)
    {
        $config = self::createConfiguration($isDevMode, $proxyDir, $cache);
        $config->setMetadataDriverImpl(new YamlDriver($paths));

        return $config;
    }

    /**
     * Creates a configuration without a metadata driver.
     *
     * @param bool   $isDevMode
     * @param string $proxyDir
     *
     * @return Configuration
     */
    public static function createConfiguration($isDevMode = false, $proxyDir = null, ?Cache $cache = null)
    {
        $proxyDir = $proxyDir ?: sys_get_temp_dir();

        $cache = self::createCacheConfiguration($isDevMode, $proxyDir, $cache);

        $config = new Configuration();

        if (method_exists(Configuration::class, 'setMetadataCache')) {
            $config->setMetadataCache(CacheAdapter::wrap($cache));
        } else {
            $config->setMetadataCacheImpl($cache);
        }

        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);
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
