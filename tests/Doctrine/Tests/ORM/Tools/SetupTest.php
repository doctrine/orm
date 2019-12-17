<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\VerifyDeprecations;

class SetupTest extends OrmTestCase
{
    use VerifyDeprecations;

    private $originalAutoloaderCount;
    private $originalIncludePath;

    public function setUp()
    {
        $this->originalAutoloaderCount = count(spl_autoload_functions());
        $this->originalIncludePath = get_include_path();
    }

    public function tearDown()
    {
        if ( ! $this->originalIncludePath) {
            return;
        }

        set_include_path($this->originalIncludePath);
        $loaders = spl_autoload_functions();
        $numberOfLoaders = count($loaders);
        for ($i = 0; $i < $numberOfLoaders; $i++) {
            if ($i > $this->originalAutoloaderCount+1) {
                spl_autoload_unregister($loaders[$i]);
            }
        }
    }

    public function testDirectoryAutoload()
    {
        Setup::registerAutoloadDirectory(__DIR__ . "/../../../../../vendor/doctrine/common/lib");

        $this->assertEquals($this->originalAutoloaderCount + 2, count(spl_autoload_functions()));
    }

    public function testAnnotationConfiguration()
    {
        $config = Setup::createAnnotationMetadataConfiguration([], true);

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        $this->assertEquals('DoctrineProxies', $config->getProxyNamespace());
        $this->assertInstanceOf(AnnotationDriver::class, $config->getMetadataDriverImpl());
    }

    public function testXMLConfiguration()
    {
        $config = Setup::createXMLMetadataConfiguration([], true);

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertInstanceOf(XmlDriver::class, $config->getMetadataDriverImpl());
    }

    public function testYAMLConfiguration()
    {
        $config = Setup::createYAMLMetadataConfiguration([], true);

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertInstanceOf(YamlDriver::class, $config->getMetadataDriverImpl());
        $this->assertHasDeprecationMessages();
    }

    /**
     * @group 5904
     */
    public function testCacheNamespaceShouldBeGeneratedWhenCacheIsNotGiven() : void
    {
        $config = Setup::createConfiguration(false, '/foo');
        $cache  = $config->getMetadataCacheImpl();

        self::assertSame('dc2_1effb2475fcfba4f9e8b8a1dbc8f3caf_', $cache->getNamespace());
    }

    /**
     * @group 5904
     */
    public function testCacheNamespaceShouldBeGeneratedWhenCacheIsGivenButHasNoNamespace() : void
    {
        $config = Setup::createConfiguration(false, '/foo', new ArrayCache());
        $cache  = $config->getMetadataCacheImpl();

        self::assertSame('dc2_1effb2475fcfba4f9e8b8a1dbc8f3caf_', $cache->getNamespace());
    }

    /**
     * @group 5904
     */
    public function testConfiguredCacheNamespaceShouldBeUsedAsPrefixOfGeneratedNamespace() : void
    {
        $originalCache = new ArrayCache();
        $originalCache->setNamespace('foo');

        $config = Setup::createConfiguration(false, '/foo', $originalCache);
        $cache  = $config->getMetadataCacheImpl();

        self::assertSame($originalCache, $cache);
        self::assertSame('foo:dc2_1effb2475fcfba4f9e8b8a1dbc8f3caf_', $cache->getNamespace());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureProxyDir()
    {
        $config = Setup::createAnnotationMetadataConfiguration([], true, "/foo");
        $this->assertEquals('/foo', $config->getProxyDir());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureCache()
    {
        $cache = new ArrayCache();
        $config = Setup::createAnnotationMetadataConfiguration([], true, null, $cache);

        $this->assertSame($cache, $config->getResultCacheImpl());
        $this->assertSame($cache, $config->getMetadataCacheImpl());
        $this->assertSame($cache, $config->getQueryCacheImpl());
    }

    /**
     * @group DDC-3190
     */
    public function testConfigureCacheCustomInstance()
    {
        $cache  = $this->createMock(Cache::class);
        $config = Setup::createConfiguration(true, null, $cache);

        $this->assertSame($cache, $config->getResultCacheImpl());
        $this->assertSame($cache, $config->getMetadataCacheImpl());
        $this->assertSame($cache, $config->getQueryCacheImpl());
    }
}
