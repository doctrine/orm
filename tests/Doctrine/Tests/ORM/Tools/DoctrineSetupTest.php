<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping as AnnotationNamespace;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\DoctrineSetup;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function sys_get_temp_dir;

class DoctrineSetupTest extends TestCase
{
    use VerifyDeprecations;

    public function testAnnotationConfiguration(): void
    {
        $config = DoctrineSetup::createAnnotationMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        self::assertEquals('DoctrineProxies', $config->getProxyNamespace());
        self::assertInstanceOf(AnnotationDriver::class, $config->getMetadataDriverImpl());
    }

    public function testNewDefaultAnnotationDriver(): void
    {
        $paths           = [__DIR__];
        $reflectionClass = new ReflectionClass(AnnotatedDummy::class);

        $annotationDriver = DoctrineSetup::createDefaultAnnotationDriver($paths);
        $reader           = $annotationDriver->getReader();
        $annotation       = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('namespacedAnnotationMethod'),
            AnnotationNamespace\PrePersist::class
        );
        self::assertInstanceOf(AnnotationNamespace\PrePersist::class, $annotation);
    }

    /**
     * @requires PHP 8.0
     */
    public function testAttributeConfiguration(): void
    {
        $config = DoctrineSetup::createAttributeMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(sys_get_temp_dir(), $config->getProxyDir());
        self::assertEquals('DoctrineProxies', $config->getProxyNamespace());
        self::assertInstanceOf(AttributeDriver::class, $config->getMetadataDriverImpl());
    }

    public function testXMLConfiguration(): void
    {
        $config = DoctrineSetup::createXMLMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertInstanceOf(XmlDriver::class, $config->getMetadataDriverImpl());
    }

    public function testYAMLConfiguration(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8465');
        $config = DoctrineSetup::createYAMLMetadataConfiguration([], true);

        self::assertInstanceOf(Configuration::class, $config);
        self::assertInstanceOf(YamlDriver::class, $config->getMetadataDriverImpl());
    }

    /**
     * @requires extension apcu
     */
    public function testCacheNamespaceShouldBeGeneratedForApcu(): void
    {
        $config = DoctrineSetup::createConfiguration(false, '/foo');
        $cache  = $config->getMetadataCache();

        $namespaceProperty = new ReflectionProperty(AbstractAdapter::class, 'namespace');
        $namespaceProperty->setAccessible(true);

        self::assertInstanceOf(ApcuAdapter::class, $cache);
        self::assertSame('dc2_1effb2475fcfba4f9e8b8a1dbc8f3caf:', $namespaceProperty->getValue($cache));
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureProxyDir(): void
    {
        $config = DoctrineSetup::createAnnotationMetadataConfiguration([], true, '/foo');
        self::assertEquals('/foo', $config->getProxyDir());
    }

    /**
     * @group DDC-1350
     */
    public function testConfigureCache(): void
    {
        $cache  = new ArrayAdapter();
        $config = DoctrineSetup::createAnnotationMetadataConfiguration([], true, null, $cache);

        self::assertSame($cache, $config->getResultCache());
        self::assertSame($cache, $config->getResultCacheImpl()->getPool());
        self::assertSame($cache, $config->getQueryCache());
        self::assertSame($cache, $config->getQueryCacheImpl()->getPool());
        self::assertSame($cache, $config->getMetadataCache());
        self::assertSame($cache, $config->getMetadataCacheImpl()->getPool());
    }

    /**
     * @group DDC-3190
     */
    public function testConfigureCacheCustomInstance(): void
    {
        $cache  = new ArrayAdapter();
        $config = DoctrineSetup::createConfiguration(true, null, $cache);

        self::assertSame($cache, $config->getResultCache());
        self::assertSame($cache, $config->getResultCacheImpl()->getPool());
        self::assertSame($cache, $config->getQueryCache());
        self::assertSame($cache, $config->getQueryCacheImpl()->getPool());
        self::assertSame($cache, $config->getMetadataCache());
        self::assertSame($cache, $config->getMetadataCacheImpl()->getPool());
    }
}

class AnnotatedDummy
{
    /** @AnnotationNamespace\PrePersist */
    public function namespacedAnnotationMethod(): void
    {
    }
}
