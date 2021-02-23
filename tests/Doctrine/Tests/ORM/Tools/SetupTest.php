<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Tests\OrmTestCase;
use function count;
use function get_include_path;
use function md5;
use function mkdir;
use function set_include_path;
use function spl_autoload_functions;
use function spl_autoload_unregister;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class SetupTest extends OrmTestCase
{
    private $originalAutoloaderCount;
    private $originalIncludePath;

    public function setUp() : void
    {
        $this->originalAutoloaderCount = count(spl_autoload_functions());
        $this->originalIncludePath     = get_include_path();
    }

    public function tearDown() : void
    {
        if (! $this->originalIncludePath) {
            return;
        }

        set_include_path($this->originalIncludePath);

        foreach (spl_autoload_functions() as $i => $loader) {
            if ($i > $this->originalAutoloaderCount + 1) {
                spl_autoload_unregister($loader);
            }
        }
    }

    public function testAnnotationConfiguration() : void
    {
        $config = Setup::createAnnotationMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(sys_get_temp_dir(), $config->getProxyManagerConfiguration()->getProxiesTargetDir());
        self::assertEquals('DoctrineProxies', $config->getProxyManagerConfiguration()->getProxiesNamespace());
        self::assertInstanceOf(AnnotationDriver::class, $config->getMetadataDriverImpl());
    }

    public function testXMLConfiguration() : void
    {
        $config = Setup::createXMLMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertInstanceOf(XmlDriver::class, $config->getMetadataDriverImpl());
    }

    /**
     * @group 5904
     */
    public function testCacheNamespaceShouldBeGeneratedWhenCacheIsNotGiven() : void
    {
        $config = Setup::createConfiguration(false, __DIR__);
        $cache  = $config->getMetadataCacheImpl();

        self::assertSame('dc2_' . md5(__DIR__) . '_', $cache->getNamespace());
    }

    /**
     * @group 5904
     */
    public function testCacheNamespaceShouldBeGeneratedWhenCacheIsGivenButHasNoNamespace() : void
    {
        $config = Setup::createConfiguration(false, __DIR__, new ArrayCache());
        $cache  = $config->getMetadataCacheImpl();

        self::assertSame('dc2_' . md5(__DIR__) . '_', $cache->getNamespace());
    }

    /**
     * @group 5904
     */
    public function testConfiguredCacheNamespaceShouldBeUsedAsPrefixOfGeneratedNamespace() : void
    {
        $originalCache = new ArrayCache();
        $originalCache->setNamespace('foo');

        $config = Setup::createConfiguration(false, __DIR__, $originalCache);
        $cache  = $config->getMetadataCacheImpl();

        self::assertSame($originalCache, $cache);
        self::assertSame('foo:dc2_' . md5(__DIR__) . '_', $cache->getNamespace());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureProxyDir() : void
    {
        $path   = $this->makeTemporaryDirectory();
        $config = Setup::createAnnotationMetadataConfiguration([], true, $path);
        self::assertSame($path, $config->getProxyManagerConfiguration()->getProxiesTargetDir());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureCache() : void
    {
        $cache  = new ArrayCache();
        $config = Setup::createAnnotationMetadataConfiguration([], true, null, $cache);

        self::assertSame($cache, $config->getResultCacheImpl());
        self::assertSame($cache, $config->getMetadataCacheImpl());
        self::assertSame($cache, $config->getQueryCacheImpl());
    }

    /**
     * @group DDC-3190
     */
    public function testConfigureCacheCustomInstance() : void
    {
        $cache  = $this->createMock(Cache::class);
        $config = Setup::createConfiguration(true, null, $cache);

        self::assertSame($cache, $config->getResultCacheImpl());
        self::assertSame($cache, $config->getMetadataCacheImpl());
        self::assertSame($cache, $config->getQueryCacheImpl());
    }

    private function makeTemporaryDirectory() : string
    {
        $path = tempnam(sys_get_temp_dir(), 'foo');

        unlink($path);
        mkdir($path);

        return $path;
    }
}
