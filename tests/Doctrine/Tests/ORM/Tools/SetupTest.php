<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\Setup;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function count;
use function get_include_path;
use function set_include_path;
use function spl_autoload_functions;
use function spl_autoload_unregister;
use function sys_get_temp_dir;

class SetupTest extends TestCase
{
    /** @var int */
    private $originalAutoloaderCount;

    /** @var string */
    private $originalIncludePath;

    protected function setUp(): void
    {
        $this->originalAutoloaderCount = count(spl_autoload_functions());
        $this->originalIncludePath     = get_include_path();
    }

    public function tearDown(): void
    {
        if (! $this->originalIncludePath) {
            return;
        }

        set_include_path($this->originalIncludePath);
        $loaders         = spl_autoload_functions();
        $numberOfLoaders = count($loaders);
        for ($i = 0; $i < $numberOfLoaders; $i++) {
            if ($i > $this->originalAutoloaderCount + 1) {
                spl_autoload_unregister($loaders[$i]);
            }
        }
    }

    public function testDirectoryAutoload(): void
    {
        Setup::registerAutoloadDirectory(__DIR__ . '/../../../../../vendor/doctrine/common/lib');

        self::assertCount($this->originalAutoloaderCount + 2, spl_autoload_functions());
    }

    public function testAnnotationConfiguration(): void
    {
        $config = Setup::createAnnotationMetadataConfiguration([], true, null, null, false);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        self::assertEquals('DoctrineProxies', $config->getProxyNamespace());
        self::assertInstanceOf(AnnotationDriver::class, $config->getMetadataDriverImpl());
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testAttributeConfiguration(): void
    {
        $config = Setup::createAttributeMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        self::assertEquals('DoctrineProxies', $config->getProxyNamespace());
        self::assertInstanceOf(AttributeDriver::class, $config->getMetadataDriverImpl());
    }

    /**
     * @requires PHP < 8
     */
    public function testAttributeConfigurationFailsOnPHP7(): void
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage(
            'The attribute metadata driver cannot be enabled on PHP 7. Please upgrade to PHP 8 or choose a different metadata driver.'
        );

        Setup::createAttributeMetadataConfiguration([], true);
    }

    public function testXMLConfiguration(): void
    {
        $config = Setup::createXMLMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertInstanceOf(XmlDriver::class, $config->getMetadataDriverImpl());
    }

    public function testYAMLConfiguration(): void
    {
        $config = Setup::createYAMLMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertInstanceOf(YamlDriver::class, $config->getMetadataDriverImpl());
    }

    /** @group 5904 */
    public function testCacheNamespaceShouldBeGeneratedWhenCacheIsGivenButHasNoNamespace(): void
    {
        $config = Setup::createConfiguration(false, '/foo', DoctrineProvider::wrap(new ArrayAdapter()));
        $cache  = $config->getMetadataCacheImpl();

        self::assertSame('dc2_1effb2475fcfba4f9e8b8a1dbc8f3caf_', $cache->getNamespace());
    }

    /** @group 5904 */
    public function testConfiguredCacheNamespaceShouldBeUsedAsPrefixOfGeneratedNamespace(): void
    {
        $originalCache = DoctrineProvider::wrap(new ArrayAdapter());
        $originalCache->setNamespace('foo');

        $config = Setup::createConfiguration(false, '/foo', $originalCache);
        $cache  = $config->getMetadataCacheImpl();
        self::assertSame($originalCache, $cache);
        self::assertSame('foo:dc2_1effb2475fcfba4f9e8b8a1dbc8f3caf_', $cache->getNamespace());
    }

    /** @group DDC-1350 */
    public function testConfigureProxyDir(): void
    {
        $config = Setup::createAnnotationMetadataConfiguration([], true, '/foo', null, false);
        self::assertEquals('/foo', $config->getProxyDir());
    }

    /** @group DDC-1350 */
    public function testConfigureCache(): void
    {
        $adapter = new ArrayAdapter();
        $cache   = DoctrineProvider::wrap($adapter);
        $config  = Setup::createAnnotationMetadataConfiguration([], true, null, $cache, false);

        self::assertSame($adapter, $config->getResultCache()->getCache()->getPool());
        self::assertSame($cache, $config->getResultCacheImpl());
        self::assertSame($adapter, $config->getQueryCache()->getCache()->getPool());
        self::assertSame($cache, $config->getQueryCacheImpl());

        self::assertSame($adapter, $config->getMetadataCache()->getCache()->getPool());
    }

    /** @group DDC-3190 */
    public function testConfigureCacheCustomInstance(): void
    {
        $adapter = new ArrayAdapter();
        $cache   = DoctrineProvider::wrap($adapter);
        $config  = Setup::createConfiguration(true, null, $cache);

        self::assertSame($adapter, $config->getResultCache()->getCache()->getPool());
        self::assertSame($cache, $config->getResultCacheImpl());
        self::assertSame($adapter, $config->getQueryCache()->getCache()->getPool());
        self::assertSame($cache, $config->getQueryCacheImpl());

        self::assertSame($adapter, $config->getMetadataCache()->getCache()->getPool());
    }
}
