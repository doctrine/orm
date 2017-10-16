<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as AnnotationNamespace;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\DDC753\DDC753CustomRepository;
use ReflectionClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Configuration object
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    protected function setUp()
    {
        parent::setUp();
        $this->configuration = new Configuration();
    }

    public function testSetGetProxyDir()
    {
        $this->assertSame(null, $this->configuration->getProxyDir()); // defaults

        $this->configuration->setProxyDir(__DIR__);
        $this->assertSame(__DIR__, $this->configuration->getProxyDir());
    }

    public function testSetGetAutoGenerateProxyClasses()
    {
        $this->assertSame(AbstractProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses()); // defaults

        $this->configuration->setAutoGenerateProxyClasses(false);
        $this->assertSame(AbstractProxyFactory::AUTOGENERATE_NEVER, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(true);
        $this->assertSame(AbstractProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        $this->assertSame(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS, $this->configuration->getAutoGenerateProxyClasses());
    }

    public function testSetGetProxyNamespace()
    {
        $this->assertSame(null, $this->configuration->getProxyNamespace()); // defaults

        $this->configuration->setProxyNamespace(__NAMESPACE__);
        $this->assertSame(__NAMESPACE__, $this->configuration->getProxyNamespace());
    }

    public function testSetGetMetadataDriverImpl()
    {
        $this->assertSame(null, $this->configuration->getMetadataDriverImpl()); // defaults

        $metadataDriver = $this->createMock(MappingDriver::class);
        $this->configuration->setMetadataDriverImpl($metadataDriver);
        $this->assertSame($metadataDriver, $this->configuration->getMetadataDriverImpl());
    }

    public function testNewDefaultAnnotationDriver()
    {
        $paths = [__DIR__];
        $reflectionClass = new ReflectionClass(ConfigurationTestAnnotationReaderChecker::class);

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths, false);
        $reader = $annotationDriver->getReader();
        $annotation = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('namespacedAnnotationMethod'),
            AnnotationNamespace\PrePersist::class
        );
        $this->assertInstanceOf(AnnotationNamespace\PrePersist::class, $annotation);

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths);
        $reader = $annotationDriver->getReader();
        $annotation = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('simpleAnnotationMethod'),
            AnnotationNamespace\PrePersist::class
        );
        $this->assertInstanceOf(AnnotationNamespace\PrePersist::class, $annotation);
    }

    public function testSetGetEntityNamespace()
    {
        $this->configuration->addEntityNamespace('TestNamespace', __NAMESPACE__);
        $this->assertSame(__NAMESPACE__, $this->configuration->getEntityNamespace('TestNamespace'));
        $namespaces = ['OtherNamespace' => __NAMESPACE__];
        $this->configuration->setEntityNamespaces($namespaces);
        $this->assertSame($namespaces, $this->configuration->getEntityNamespaces());
        $this->expectException(ORMException::class);
        $this->configuration->getEntityNamespace('NonExistingNamespace');
    }

    public function testSetGetQueryCacheImpl()
    {
        $this->assertSame(null, $this->configuration->getQueryCacheImpl()); // defaults
        $queryCacheImpl = $this->createMock(Cache::class);
        $this->configuration->setQueryCacheImpl($queryCacheImpl);
        $this->assertSame($queryCacheImpl, $this->configuration->getQueryCacheImpl());
    }

    public function testSetGetHydrationCacheImpl()
    {
        $this->assertSame(null, $this->configuration->getHydrationCacheImpl()); // defaults
        $queryCacheImpl = $this->createMock(Cache::class);
        $this->configuration->setHydrationCacheImpl($queryCacheImpl);
        $this->assertSame($queryCacheImpl, $this->configuration->getHydrationCacheImpl());
    }

    public function testSetGetMetadataCacheImpl()
    {
        $this->assertSame(null, $this->configuration->getMetadataCacheImpl()); // defaults
        $queryCacheImpl = $this->createMock(Cache::class);
        $this->configuration->setMetadataCacheImpl($queryCacheImpl);
        $this->assertSame($queryCacheImpl, $this->configuration->getMetadataCacheImpl());
    }

    public function testAddGetNamedQuery()
    {
        $dql = 'SELECT u FROM User u';
        $this->configuration->addNamedQuery('QueryName', $dql);
        $this->assertSame($dql, $this->configuration->getNamedQuery('QueryName'));
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('a named query');
        $this->configuration->getNamedQuery('NonExistingQuery');
    }

    public function testAddGetNamedNativeQuery()
    {
        $sql = 'SELECT * FROM user';
        $rsm = $this->createMock(ResultSetMapping::class);
        $this->configuration->addNamedNativeQuery('QueryName', $sql, $rsm);
        $fetched = $this->configuration->getNamedNativeQuery('QueryName');
        $this->assertSame($sql, $fetched[0]);
        $this->assertSame($rsm, $fetched[1]);
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('a named native query');
        $this->configuration->getNamedNativeQuery('NonExistingQuery');
    }

    /**
     * Configures $this->configuration to use production settings.
     *
     * @param string $skipCache Do not configure a cache of this type, either "query" or "metadata".
     */
    protected function setProductionSettings($skipCache = false)
    {
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_NEVER);

        $cache = $this->createMock(Cache::class);

        if ('query' !== $skipCache) {
            $this->configuration->setQueryCacheImpl($cache);
        }

        if ('metadata' !== $skipCache) {
            $this->configuration->setMetadataCacheImpl($cache);
        }
    }

    public function testEnsureProductionSettings()
    {
        $this->setProductionSettings();
        $this->configuration->ensureProductionSettings();

        $this->addToAssertionCount(1);
    }

    public function testEnsureProductionSettingsQueryCache()
    {
        $this->setProductionSettings('query');

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Query Cache is not configured.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsMetadataCache()
    {
        $this->setProductionSettings('metadata');

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Metadata Cache is not configured.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsQueryArrayCache()
    {
        $this->setProductionSettings();
        $this->configuration->setQueryCacheImpl(new ArrayCache());

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Query Cache uses a non-persistent cache driver, Doctrine\Common\Cache\ArrayCache.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsMetadataArrayCache()
    {
        $this->setProductionSettings();
        $this->configuration->setMetadataCacheImpl(new ArrayCache());

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Metadata Cache uses a non-persistent cache driver, Doctrine\Common\Cache\ArrayCache.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesAlways()
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_ALWAYS);

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Proxy Classes are always regenerating.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesFileNotExists()
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Proxy Classes are always regenerating.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesEval()
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_EVAL);

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Proxy Classes are always regenerating.');

        $this->configuration->ensureProductionSettings();
    }

    public function testAddGetCustomStringFunction()
    {
        $this->configuration->addCustomStringFunction('FunctionName', __CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getCustomStringFunction('FunctionName'));
        $this->assertSame(null, $this->configuration->getCustomStringFunction('NonExistingFunction'));
        $this->configuration->setCustomStringFunctions(['OtherFunctionName' => __CLASS__]);
        $this->assertSame(__CLASS__, $this->configuration->getCustomStringFunction('OtherFunctionName'));
    }

    public function testAddGetCustomNumericFunction()
    {
        $this->configuration->addCustomNumericFunction('FunctionName', __CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getCustomNumericFunction('FunctionName'));
        $this->assertSame(null, $this->configuration->getCustomNumericFunction('NonExistingFunction'));
        $this->configuration->setCustomNumericFunctions(['OtherFunctionName' => __CLASS__]);
        $this->assertSame(__CLASS__, $this->configuration->getCustomNumericFunction('OtherFunctionName'));
    }

    public function testAddGetCustomDatetimeFunction()
    {
        $this->configuration->addCustomDatetimeFunction('FunctionName', __CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getCustomDatetimeFunction('FunctionName'));
        $this->assertSame(null, $this->configuration->getCustomDatetimeFunction('NonExistingFunction'));
        $this->configuration->setCustomDatetimeFunctions(['OtherFunctionName' => __CLASS__]);
        $this->assertSame(__CLASS__, $this->configuration->getCustomDatetimeFunction('OtherFunctionName'));
    }

    public function testAddGetCustomHydrationMode()
    {
        $this->assertSame(null, $this->configuration->getCustomHydrationMode('NonExisting'));
        $this->configuration->addCustomHydrationMode('HydrationModeName', __CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('HydrationModeName'));
    }

    public function testSetCustomHydrationModes()
    {
        $this->configuration->addCustomHydrationMode('HydrationModeName', __CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('HydrationModeName'));

        $this->configuration->setCustomHydrationModes(
            [
                'AnotherHydrationModeName' => __CLASS__
            ]
        );

        $this->assertNull($this->configuration->getCustomHydrationMode('HydrationModeName'));
        $this->assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('AnotherHydrationModeName'));
    }

    public function testSetGetClassMetadataFactoryName()
    {
        $this->assertSame(AnnotationNamespace\ClassMetadataFactory::class, $this->configuration->getClassMetadataFactoryName());
        $this->configuration->setClassMetadataFactoryName(__CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getClassMetadataFactoryName());
    }

    public function testAddGetFilters()
    {
        $this->assertSame(null, $this->configuration->getFilterClassName('NonExistingFilter'));
        $this->configuration->addFilter('FilterName', __CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getFilterClassName('FilterName'));
    }

    public function setDefaultRepositoryClassName()
    {
        $this->assertSame(EntityRepository::class, $this->configuration->getDefaultRepositoryClassName());
        $this->configuration->setDefaultRepositoryClassName(DDC753CustomRepository::class);
        $this->assertSame(DDC753CustomRepository::class, $this->configuration->getDefaultRepositoryClassName());
        $this->expectException(ORMException::class);
        $this->configuration->setDefaultRepositoryClassName(__CLASS__);
    }

    public function testSetGetNamingStrategy()
    {
        $this->assertInstanceOf(NamingStrategy::class, $this->configuration->getNamingStrategy());
        $namingStrategy = $this->createMock(NamingStrategy::class);
        $this->configuration->setNamingStrategy($namingStrategy);
        $this->assertSame($namingStrategy, $this->configuration->getNamingStrategy());
    }

    public function testSetGetQuoteStrategy()
    {
        $this->assertInstanceOf(QuoteStrategy::class, $this->configuration->getQuoteStrategy());
        $quoteStrategy = $this->createMock(QuoteStrategy::class);
        $this->configuration->setQuoteStrategy($quoteStrategy);
        $this->assertSame($quoteStrategy, $this->configuration->getQuoteStrategy());
    }

    /**
     * @group DDC-1955
     */
    public function testSetGetEntityListenerResolver()
    {
        $this->assertInstanceOf(EntityListenerResolver::class, $this->configuration->getEntityListenerResolver());
        $this->assertInstanceOf(AnnotationNamespace\DefaultEntityListenerResolver::class, $this->configuration->getEntityListenerResolver());
        $resolver = $this->createMock(EntityListenerResolver::class);
        $this->configuration->setEntityListenerResolver($resolver);
        $this->assertSame($resolver, $this->configuration->getEntityListenerResolver());
    }

    /**
     * @group DDC-2183
     */
    public function testSetGetSecondLevelCacheConfig()
    {
        $mockClass = $this->createMock(CacheConfiguration::class);

        $this->assertNull($this->configuration->getSecondLevelCacheConfiguration());
        $this->configuration->setSecondLevelCacheConfiguration($mockClass);
        $this->assertEquals($mockClass, $this->configuration->getSecondLevelCacheConfiguration());
    }
}

class ConfigurationTestAnnotationReaderChecker
{
    /** @PrePersist */
    public function simpleAnnotationMethod()
    {
    }

    /** @AnnotationNamespace\PrePersist */
    public function namespacedAnnotationMethod()
    {
    }
}
