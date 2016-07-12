<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Version;
use Doctrine\Tests\OrmTestCase;

class SetupTest extends OrmTestCase
{
    private $originalAutoloaderCount;
    private $originalIncludePath;

    public function setUp()
    {
        if (strpos(Version::VERSION, "DEV") === false) {
            $this->markTestSkipped("Test only runs in a dev-installation from Github");
        }

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

        self::assertEquals($this->originalAutoloaderCount + 2, count(spl_autoload_functions()));
    }

    public function testAnnotationConfiguration()
    {
        $config = Setup::createAnnotationMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        self::assertEquals('DoctrineProxies', $config->getProxyNamespace());
        self::assertInstanceOf(AnnotationDriver::class, $config->getMetadataDriverImpl());
    }

    public function testXMLConfiguration()
    {
        $config = Setup::createXMLMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertInstanceOf(XmlDriver::class, $config->getMetadataDriverImpl());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureProxyDir()
    {
        $config = Setup::createAnnotationMetadataConfiguration([], true, "/foo");
        self::assertEquals('/foo', $config->getProxyDir());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureCache()
    {
        $cache = new ArrayCache();
        $config = Setup::createAnnotationMetadataConfiguration([], true, null, $cache);

        self::assertSame($cache, $config->getResultCacheImpl());
        self::assertSame($cache, $config->getMetadataCacheImpl());
        self::assertSame($cache, $config->getQueryCacheImpl());
    }

    /**
     * @group DDC-3190
     */
    public function testConfigureCacheCustomInstance()
    {
        $cache  = $this->createMock(Cache::class);
        $config = Setup::createConfiguration([], true, $cache);

        self::assertSame($cache, $config->getResultCacheImpl());
        self::assertSame($cache, $config->getMetadataCacheImpl());
        self::assertSame($cache, $config->getQueryCacheImpl());
    }
}
