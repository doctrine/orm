<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping as MappingNamespace;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RequiresSetting;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function sys_get_temp_dir;

class ORMSetupTest extends TestCase
{
    public function testAttributeConfiguration(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        self::assertEquals('DoctrineProxies', $config->getProxyNamespace());
        self::assertInstanceOf(AttributeDriver::class, $config->getMetadataDriverImpl());
    }

    public function testXMLConfiguration(): void
    {
        $config = ORMSetup::createXMLMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertInstanceOf(XmlDriver::class, $config->getMetadataDriverImpl());
    }

    public function testDisablingXmlValidationIsPossible(): void
    {
        $this->expectNotToPerformAssertions();

        ORMSetup::createXMLMetadataConfiguration(paths: [], isXsdValidationEnabled: false);
    }

    #[RequiresPhpExtension('apcu')]
    #[RequiresSetting('apc.enable_cli', '1')]
    #[RequiresSetting('apc.enabled', '1')]
    public function testCacheNamespaceShouldBeGeneratedForApcu(): void
    {
        $config = ORMSetup::createConfiguration(false, '/foo');
        $cache  = $config->getMetadataCache();

        $namespaceProperty = new ReflectionProperty(AbstractAdapter::class, 'namespace');

        self::assertInstanceOf(ApcuAdapter::class, $cache);
        self::assertSame('dc2_1effb2475fcfba4f9e8b8a1dbc8f3caf:', $namespaceProperty->getValue($cache));
    }

    #[Group('DDC-1350')]
    public function testConfigureProxyDir(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([], true, '/foo');
        self::assertEquals('/foo', $config->getProxyDir());
    }

    #[Group('DDC-1350')]
    public function testConfigureCache(): void
    {
        $cache  = new ArrayAdapter();
        $config = ORMSetup::createAttributeMetadataConfiguration([], true, null, $cache);

        self::assertSame($cache, $config->getResultCache());
        self::assertSame($cache, $config->getQueryCache());
        self::assertSame($cache, $config->getMetadataCache());
    }

    #[Group('DDC-3190')]
    public function testConfigureCacheCustomInstance(): void
    {
        $cache  = new ArrayAdapter();
        $config = ORMSetup::createConfiguration(true, null, $cache);

        self::assertSame($cache, $config->getResultCache());
        self::assertSame($cache, $config->getQueryCache());
        self::assertSame($cache, $config->getMetadataCache());
    }
}

class AnnotatedDummy
{
    #[MappingNamespace\PrePersist]
    public function namespacedAttributeMethod(): void
    {
    }
}
