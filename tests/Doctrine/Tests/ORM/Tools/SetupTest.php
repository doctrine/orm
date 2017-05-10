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
        for ($i = 0; $i < count($loaders); $i++) {
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
    public function testConfigureCacheCustom()
    {
        $cache  = $this->createMock(Cache::class);
        $config = Setup::createConfiguration([], true, $cache);

        $this->assertSame($cache, $config->getResultCacheImpl());
        $this->assertSame($cache, $config->getMetadataCacheImpl());
        $this->assertSame($cache, $config->getQueryCacheImpl());
    }

    public function testDirectoryAutoloadInstance()
    {
        $setup = new Setup;
        $setup->doRegisterAutoloadDirectory(__DIR__ . "/../../../../../vendor/doctrine/common/lib");

        $this->assertEquals($this->originalAutoloaderCount + 2, count(spl_autoload_functions()));
    }

    public function testAnnotationConfigurationInstance()
    {
        $setup = new Setup;
        $config = $setup->doCreateAnnotationMetadataConfiguration(array(), true);

        $this->assertInstanceOf('Doctrine\ORM\Configuration', $config);
        $this->assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        $this->assertEquals('DoctrineProxies', $config->getProxyNamespace());
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\AnnotationDriver', $config->getMetadataDriverImpl());
    }

    public function testXMLConfigurationInstance()
    {
        $setup = new Setup;
        $config = $setup->doCreateXMLMetadataConfiguration(array(), true);

        $this->assertInstanceOf('Doctrine\ORM\Configuration', $config);
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\XmlDriver', $config->getMetadataDriverImpl());
    }

    public function testYAMLConfigurationInstance()
    {
        $setup = new Setup;
        $config = $setup->doCreateYAMLMetadataConfiguration(array(), true);

        $this->assertInstanceOf('Doctrine\ORM\Configuration', $config);
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\YamlDriver', $config->getMetadataDriverImpl());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureProxyDirInstance()
    {
        $setup = new Setup;
        $config = $setup->doCreateAnnotationMetadataConfiguration(array(), true, "/foo");
        $this->assertEquals('/foo', $config->getProxyDir());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureCacheInstance()
    {
        $cache = new ArrayCache();
        $setup = new Setup;
        $config = $setup->doCreateAnnotationMetadataConfiguration(array(), true, null, $cache);

        $this->assertSame($cache, $config->getResultCacheImpl());
        $this->assertSame($cache, $config->getMetadataCacheImpl());
        $this->assertSame($cache, $config->getQueryCacheImpl());
    }

    /**
     * @group DDC-3190
     */
    public function testConfigureCacheCustomInstance()
    {
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');
        $cache->expects($this->never())->method('setNamespace');

        $setup = new Setup;
        $config = $setup->doCreateConfiguration(array(), true, $cache);

        $this->assertSame($cache, $config->getResultCacheImpl());
        $this->assertSame($cache, $config->getMetadataCacheImpl());
        $this->assertSame($cache, $config->getQueryCacheImpl());
    }
}
