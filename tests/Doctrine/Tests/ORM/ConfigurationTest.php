<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping as AnnotationNamespace;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Tests\DoctrineTestCase;
use Doctrine\Tests\Models\DDC753\DDC753CustomRepository;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;

/**
 * Tests for the Configuration object
 */
class ConfigurationTest extends DoctrineTestCase
{
    use VerifyDeprecations;

    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = new Configuration();
    }

    public function testSetGetProxyDir(): void
    {
        self::assertNull($this->configuration->getProxyDir()); // defaults

        $this->configuration->setProxyDir(__DIR__);
        self::assertSame(__DIR__, $this->configuration->getProxyDir());
    }

    public function testSetGetAutoGenerateProxyClasses(): void
    {
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses()); // defaults

        $this->configuration->setAutoGenerateProxyClasses(false);
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_NEVER, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(true);
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS, $this->configuration->getAutoGenerateProxyClasses());
    }

    public function testSetGetProxyNamespace(): void
    {
        self::assertNull($this->configuration->getProxyNamespace()); // defaults

        $this->configuration->setProxyNamespace(__NAMESPACE__);
        self::assertSame(__NAMESPACE__, $this->configuration->getProxyNamespace());
    }

    public function testSetGetMetadataDriverImpl(): void
    {
        self::assertNull($this->configuration->getMetadataDriverImpl()); // defaults

        $metadataDriver = $this->createMock(MappingDriver::class);
        $this->configuration->setMetadataDriverImpl($metadataDriver);
        self::assertSame($metadataDriver, $this->configuration->getMetadataDriverImpl());
    }

    public function testNewDefaultAnnotationDriver(): void
    {
        $paths           = [__DIR__];
        $reflectionClass = new ReflectionClass(ConfigurationTestAnnotationReaderChecker::class);

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths, false);
        $reader           = $annotationDriver->getReader();
        $annotation       = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('namespacedAnnotationMethod'),
            AnnotationNamespace\PrePersist::class
        );
        self::assertInstanceOf(AnnotationNamespace\PrePersist::class, $annotation);

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths);
        $reader           = $annotationDriver->getReader();
        $annotation       = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('simpleAnnotationMethod'),
            AnnotationNamespace\PrePersist::class
        );
        self::assertInstanceOf(AnnotationNamespace\PrePersist::class, $annotation);
    }

    public function testSetGetEntityNamespace(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8818');

        $this->configuration->addEntityNamespace('TestNamespace', __NAMESPACE__);
        self::assertSame(__NAMESPACE__, $this->configuration->getEntityNamespace('TestNamespace'));
        $namespaces = ['OtherNamespace' => __NAMESPACE__];
        $this->configuration->setEntityNamespaces($namespaces);
        self::assertSame($namespaces, $this->configuration->getEntityNamespaces());
        $this->expectException(ORMException::class);
        $this->configuration->getEntityNamespace('NonExistingNamespace');
    }

    public function testSetGetQueryCacheImpl(): void
    {
        self::assertNull($this->configuration->getQueryCacheImpl()); // defaults
        self::assertNull($this->configuration->getQueryCache()); // defaults
        $queryCacheImpl = $this->createMock(Cache::class);
        $this->configuration->setQueryCacheImpl($queryCacheImpl);
        self::assertSame($queryCacheImpl, $this->configuration->getQueryCacheImpl());
        self::assertNotNull($this->configuration->getQueryCache());
    }

    public function testSetGetQueryCache(): void
    {
        self::assertNull($this->configuration->getQueryCache()); // defaults
        $queryCache = $this->createMock(CacheItemPoolInterface::class);
        $this->configuration->setQueryCache($queryCache);
        self::assertSame($queryCache, $this->configuration->getQueryCache());
        self::assertSame($queryCache, CacheAdapter::wrap($this->configuration->getQueryCacheImpl()));
    }

    public function testSetGetHydrationCacheImpl(): void
    {
        self::assertNull($this->configuration->getHydrationCacheImpl()); // defaults
        $hydrationCacheImpl = $this->createMock(Cache::class);
        $this->configuration->setHydrationCacheImpl($hydrationCacheImpl);
        self::assertSame($hydrationCacheImpl, $this->configuration->getHydrationCacheImpl());
        self::assertNotNull($this->configuration->getHydrationCache());
    }

    public function testSetGetHydrationCache(): void
    {
        self::assertNull($this->configuration->getHydrationCache()); // defaults
        $hydrationCache = $this->createStub(CacheItemPoolInterface::class);
        $this->configuration->setHydrationCache($hydrationCache);
        self::assertSame($hydrationCache, $this->configuration->getHydrationCache());
        self::assertSame($hydrationCache, CacheAdapter::wrap($this->configuration->getHydrationCacheImpl()));
    }

    public function testSetGetMetadataCacheImpl(): void
    {
        self::assertNull($this->configuration->getMetadataCacheImpl()); // defaults
        $queryCacheImpl = $this->createMock(Cache::class);
        $this->configuration->setMetadataCacheImpl($queryCacheImpl);
        self::assertSame($queryCacheImpl, $this->configuration->getMetadataCacheImpl());
        self::assertNotNull($this->configuration->getMetadataCache());
    }

    public function testSetGetMetadataCache(): void
    {
        self::assertNull($this->configuration->getMetadataCache());
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $this->configuration->setMetadataCache($cache);
        self::assertSame($cache, $this->configuration->getMetadataCache());
        self::assertSame($cache, CacheAdapter::wrap($this->configuration->getMetadataCacheImpl()));
    }

    public function testAddGetCustomStringFunction(): void
    {
        $this->configuration->addCustomStringFunction('FunctionName', self::class);
        self::assertSame(self::class, $this->configuration->getCustomStringFunction('FunctionName'));
        self::assertNull($this->configuration->getCustomStringFunction('NonExistingFunction'));
        $this->configuration->setCustomStringFunctions(['OtherFunctionName' => self::class]);
        self::assertSame(self::class, $this->configuration->getCustomStringFunction('OtherFunctionName'));
    }

    public function testAddGetCustomNumericFunction(): void
    {
        $this->configuration->addCustomNumericFunction('FunctionName', self::class);
        self::assertSame(self::class, $this->configuration->getCustomNumericFunction('FunctionName'));
        self::assertNull($this->configuration->getCustomNumericFunction('NonExistingFunction'));
        $this->configuration->setCustomNumericFunctions(['OtherFunctionName' => self::class]);
        self::assertSame(self::class, $this->configuration->getCustomNumericFunction('OtherFunctionName'));
    }

    public function testAddGetCustomDatetimeFunction(): void
    {
        $this->configuration->addCustomDatetimeFunction('FunctionName', self::class);
        self::assertSame(self::class, $this->configuration->getCustomDatetimeFunction('FunctionName'));
        self::assertNull($this->configuration->getCustomDatetimeFunction('NonExistingFunction'));
        $this->configuration->setCustomDatetimeFunctions(['OtherFunctionName' => self::class]);
        self::assertSame(self::class, $this->configuration->getCustomDatetimeFunction('OtherFunctionName'));
    }

    public function testAddGetCustomHydrationMode(): void
    {
        self::assertNull($this->configuration->getCustomHydrationMode('NonExisting'));
        $this->configuration->addCustomHydrationMode('HydrationModeName', self::class);
        self::assertSame(self::class, $this->configuration->getCustomHydrationMode('HydrationModeName'));
    }

    public function testSetCustomHydrationModes(): void
    {
        $this->configuration->addCustomHydrationMode('HydrationModeName', self::class);
        self::assertSame(self::class, $this->configuration->getCustomHydrationMode('HydrationModeName'));

        $this->configuration->setCustomHydrationModes(
            ['AnotherHydrationModeName' => self::class]
        );

        self::assertNull($this->configuration->getCustomHydrationMode('HydrationModeName'));
        self::assertSame(self::class, $this->configuration->getCustomHydrationMode('AnotherHydrationModeName'));
    }

    public function testSetGetClassMetadataFactoryName(): void
    {
        self::assertSame(AnnotationNamespace\ClassMetadataFactory::class, $this->configuration->getClassMetadataFactoryName());
        $this->configuration->setClassMetadataFactoryName(self::class);
        self::assertSame(self::class, $this->configuration->getClassMetadataFactoryName());
    }

    public function testAddGetFilters(): void
    {
        self::assertNull($this->configuration->getFilterClassName('NonExistingFilter'));
        $this->configuration->addFilter('FilterName', self::class);
        self::assertSame(self::class, $this->configuration->getFilterClassName('FilterName'));
    }

    public function setDefaultRepositoryClassName(): void
    {
        self::assertSame(EntityRepository::class, $this->configuration->getDefaultRepositoryClassName());
        $this->configuration->setDefaultRepositoryClassName(DDC753CustomRepository::class);
        self::assertSame(DDC753CustomRepository::class, $this->configuration->getDefaultRepositoryClassName());
        $this->expectException(ORMException::class);
        $this->configuration->setDefaultRepositoryClassName(self::class);
    }

    public function testSetGetNamingStrategy(): void
    {
        self::assertInstanceOf(NamingStrategy::class, $this->configuration->getNamingStrategy());
        $namingStrategy = $this->createMock(NamingStrategy::class);
        $this->configuration->setNamingStrategy($namingStrategy);
        self::assertSame($namingStrategy, $this->configuration->getNamingStrategy());
    }

    public function testSetGetQuoteStrategy(): void
    {
        self::assertInstanceOf(QuoteStrategy::class, $this->configuration->getQuoteStrategy());
        $quoteStrategy = $this->createMock(QuoteStrategy::class);
        $this->configuration->setQuoteStrategy($quoteStrategy);
        self::assertSame($quoteStrategy, $this->configuration->getQuoteStrategy());
    }

    /**
     * @group DDC-1955
     */
    public function testSetGetEntityListenerResolver(): void
    {
        self::assertInstanceOf(EntityListenerResolver::class, $this->configuration->getEntityListenerResolver());
        self::assertInstanceOf(AnnotationNamespace\DefaultEntityListenerResolver::class, $this->configuration->getEntityListenerResolver());
        $resolver = $this->createMock(EntityListenerResolver::class);
        $this->configuration->setEntityListenerResolver($resolver);
        self::assertSame($resolver, $this->configuration->getEntityListenerResolver());
    }

    /**
     * @group DDC-2183
     */
    public function testSetGetSecondLevelCacheConfig(): void
    {
        $mockClass = $this->createMock(CacheConfiguration::class);

        self::assertNull($this->configuration->getSecondLevelCacheConfiguration());
        $this->configuration->setSecondLevelCacheConfiguration($mockClass);
        self::assertEquals($mockClass, $this->configuration->getSecondLevelCacheConfiguration());
    }
}

class ConfigurationTestAnnotationReaderChecker
{
    /** @PrePersist */
    public function simpleAnnotationMethod(): void
    {
    }

    /** @AnnotationNamespace\PrePersist */
    public function namespacedAnnotationMethod(): void
    {
    }
}
