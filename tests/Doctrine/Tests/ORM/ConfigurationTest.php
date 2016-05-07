<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Annotation as AnnotationNamespace;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMException;
use ReflectionClass;
use PHPUnit_Framework_TestCase;

/**
 * Tests for the Configuration object
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class ConfigurationTest extends PHPUnit_Framework_TestCase
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
        self::assertSame(null, $this->configuration->getProxyDir()); // defaults

        $this->configuration->setProxyDir(__DIR__);
        self::assertSame(__DIR__, $this->configuration->getProxyDir());
    }

    public function testSetGetAutoGenerateProxyClasses()
    {
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses()); // defaults

        $this->configuration->setAutoGenerateProxyClasses(false);
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_NEVER, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(true);
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS, $this->configuration->getAutoGenerateProxyClasses());
    }

    public function testSetGetProxyNamespace()
    {
        self::assertSame(null, $this->configuration->getProxyNamespace()); // defaults

        $this->configuration->setProxyNamespace(__NAMESPACE__);
        self::assertSame(__NAMESPACE__, $this->configuration->getProxyNamespace());
    }

    public function testSetGetMetadataDriverImpl()
    {
        self::assertSame(null, $this->configuration->getMetadataDriverImpl()); // defaults

        $metadataDriver = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');
        $this->configuration->setMetadataDriverImpl($metadataDriver);
        self::assertSame($metadataDriver, $this->configuration->getMetadataDriverImpl());
    }

    public function testNewDefaultAnnotationDriver()
    {
        $paths = array(__DIR__);
        $reflectionClass = new ReflectionClass(__NAMESPACE__ . '\ConfigurationTestAnnotationReaderChecker');

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths, false);
        $reader = $annotationDriver->getReader();
        $annotation = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('namespacedAnnotationMethod'),
            AnnotationNamespace\PrePersist::class
        );
        self::assertInstanceOf(AnnotationNamespace\PrePersist::class, $annotation);

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths);
        $reader = $annotationDriver->getReader();
        $annotation = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('simpleAnnotationMethod'),
            AnnotationNamespace\PrePersist::class
        );
        self::assertInstanceOf(AnnotationNamespace\PrePersist::class, $annotation);
    }

    public function testSetGetEntityNamespace()
    {
        $this->configuration->addEntityNamespace('TestNamespace', __NAMESPACE__);
        self::assertSame(__NAMESPACE__, $this->configuration->getEntityNamespace('TestNamespace'));
        $namespaces = array('OtherNamespace' => __NAMESPACE__);
        $this->configuration->setEntityNamespaces($namespaces);
        self::assertSame($namespaces, $this->configuration->getEntityNamespaces());
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->getEntityNamespace('NonExistingNamespace');
    }

    public function testSetGetQueryCacheImpl()
    {
        self::assertSame(null, $this->configuration->getQueryCacheImpl()); // defaults
        $queryCacheImpl = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->configuration->setQueryCacheImpl($queryCacheImpl);
        self::assertSame($queryCacheImpl, $this->configuration->getQueryCacheImpl());
    }

    public function testSetGetHydrationCacheImpl()
    {
        self::assertSame(null, $this->configuration->getHydrationCacheImpl()); // defaults
        $queryCacheImpl = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->configuration->setHydrationCacheImpl($queryCacheImpl);
        self::assertSame($queryCacheImpl, $this->configuration->getHydrationCacheImpl());
    }

    public function testSetGetMetadataCacheImpl()
    {
        self::assertSame(null, $this->configuration->getMetadataCacheImpl()); // defaults
        $queryCacheImpl = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->configuration->setMetadataCacheImpl($queryCacheImpl);
        self::assertSame($queryCacheImpl, $this->configuration->getMetadataCacheImpl());
    }

    public function testAddGetNamedQuery()
    {
        $dql = 'SELECT u FROM User u';
        $this->configuration->addNamedQuery('QueryName', $dql);
        self::assertSame($dql, $this->configuration->getNamedQuery('QueryName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->getNamedQuery('NonExistingQuery');
    }

    public function testAddGetNamedNativeQuery()
    {
        $sql = 'SELECT * FROM user';
        $rsm = $this->getMock('Doctrine\ORM\Query\ResultSetMapping');
        $this->configuration->addNamedNativeQuery('QueryName', $sql, $rsm);
        $fetched = $this->configuration->getNamedNativeQuery('QueryName');
        self::assertSame($sql, $fetched[0]);
        self::assertSame($rsm, $fetched[1]);
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->getNamedQuery('NonExistingQuery');
    }

    /**
     * Configures $this->configuration to use production settings.
     *
     * @param string $skipCache Do not configure a cache of this type, either "query" or "metadata".
     */
    protected function setProductionSettings($skipCache = false)
    {
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_NEVER);

        $cache = $this->getMock('Doctrine\Common\Cache\Cache');

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
    }

    public function testEnsureProductionSettingsQueryCache()
    {
        $this->setProductionSettings('query');
        $this->setExpectedException('Doctrine\ORM\ORMException', 'Query Cache is not configured.');
        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsMetadataCache()
    {
        $this->setProductionSettings('metadata');
        $this->setExpectedException('Doctrine\ORM\ORMException', 'Metadata Cache is not configured.');
        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsQueryArrayCache()
    {
        $this->setProductionSettings();
        $this->configuration->setQueryCacheImpl(new ArrayCache());
        $this->setExpectedException(
            'Doctrine\ORM\ORMException',
            'Query Cache uses a non-persistent cache driver, Doctrine\Common\Cache\ArrayCache.');
        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsMetadataArrayCache()
    {
        $this->setProductionSettings();
        $this->configuration->setMetadataCacheImpl(new ArrayCache());
        $this->setExpectedException(
            'Doctrine\ORM\ORMException',
            'Metadata Cache uses a non-persistent cache driver, Doctrine\Common\Cache\ArrayCache.');
        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesAlways()
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_ALWAYS);
        $this->setExpectedException('Doctrine\ORM\ORMException', 'Proxy Classes are always regenerating.');
        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesFileNotExists()
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        $this->setExpectedException('Doctrine\ORM\ORMException', 'Proxy Classes are always regenerating.');
        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesEval()
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_EVAL);
        $this->setExpectedException('Doctrine\ORM\ORMException', 'Proxy Classes are always regenerating.');
        $this->configuration->ensureProductionSettings();
    }

    public function testAddGetCustomStringFunction()
    {
        $this->configuration->addCustomStringFunction('FunctionName', __CLASS__);
        self::assertSame(__CLASS__, $this->configuration->getCustomStringFunction('FunctionName'));
        self::assertSame(null, $this->configuration->getCustomStringFunction('NonExistingFunction'));
        $this->configuration->setCustomStringFunctions(array('OtherFunctionName' => __CLASS__));
        self::assertSame(__CLASS__, $this->configuration->getCustomStringFunction('OtherFunctionName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->addCustomStringFunction('concat', __CLASS__);
    }

    public function testAddGetCustomNumericFunction()
    {
        $this->configuration->addCustomNumericFunction('FunctionName', __CLASS__);
        self::assertSame(__CLASS__, $this->configuration->getCustomNumericFunction('FunctionName'));
        self::assertSame(null, $this->configuration->getCustomNumericFunction('NonExistingFunction'));
        $this->configuration->setCustomNumericFunctions(array('OtherFunctionName' => __CLASS__));
        self::assertSame(__CLASS__, $this->configuration->getCustomNumericFunction('OtherFunctionName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->addCustomNumericFunction('abs', __CLASS__);
    }

    public function testAddGetCustomDatetimeFunction()
    {
        $this->configuration->addCustomDatetimeFunction('FunctionName', __CLASS__);
        self::assertSame(__CLASS__, $this->configuration->getCustomDatetimeFunction('FunctionName'));
        self::assertSame(null, $this->configuration->getCustomDatetimeFunction('NonExistingFunction'));
        $this->configuration->setCustomDatetimeFunctions(array('OtherFunctionName' => __CLASS__));
        self::assertSame(__CLASS__, $this->configuration->getCustomDatetimeFunction('OtherFunctionName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->addCustomDatetimeFunction('date_add', __CLASS__);
    }

    public function testAddGetCustomHydrationMode()
    {
        self::assertSame(null, $this->configuration->getCustomHydrationMode('NonExisting'));
        $this->configuration->addCustomHydrationMode('HydrationModeName', __CLASS__);
        self::assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('HydrationModeName'));
    }

    public function testSetCustomHydrationModes()
    {
        $this->configuration->addCustomHydrationMode('HydrationModeName', __CLASS__);
        self::assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('HydrationModeName'));

        $this->configuration->setCustomHydrationModes(
            array(
                'AnotherHydrationModeName' => __CLASS__
            )
        );

        self::assertNull($this->configuration->getCustomHydrationMode('HydrationModeName'));
        self::assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('AnotherHydrationModeName'));
    }

    public function testSetGetClassMetadataFactoryName()
    {
        self::assertSame('Doctrine\ORM\Mapping\ClassMetadataFactory', $this->configuration->getClassMetadataFactoryName());
        $this->configuration->setClassMetadataFactoryName(__CLASS__);
        self::assertSame(__CLASS__, $this->configuration->getClassMetadataFactoryName());
    }

    public function testAddGetFilters()
    {
        self::assertSame(null, $this->configuration->getFilterClassName('NonExistingFilter'));
        $this->configuration->addFilter('FilterName', __CLASS__);
        self::assertSame(__CLASS__, $this->configuration->getFilterClassName('FilterName'));
    }

    public function setDefaultRepositoryClassName()
    {
        self::assertSame('Doctrine\ORM\EntityRepository', $this->configuration->getDefaultRepositoryClassName());
        $repositoryClass = 'Doctrine\Tests\Models\DDC753\DDC753CustomRepository';
        $this->configuration->setDefaultRepositoryClassName($repositoryClass);
        self::assertSame($repositoryClass, $this->configuration->getDefaultRepositoryClassName());
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->setDefaultRepositoryClassName(__CLASS__);
    }

    public function testSetGetNamingStrategy()
    {
        self::assertInstanceOf('Doctrine\ORM\Mapping\NamingStrategy', $this->configuration->getNamingStrategy());
        $namingStrategy = $this->getMock('Doctrine\ORM\Mapping\NamingStrategy');
        $this->configuration->setNamingStrategy($namingStrategy);
        self::assertSame($namingStrategy, $this->configuration->getNamingStrategy());
    }

    public function testSetGetQuoteStrategy()
    {
        self::assertInstanceOf('Doctrine\ORM\Mapping\QuoteStrategy', $this->configuration->getQuoteStrategy());
        $quoteStrategy = $this->getMock('Doctrine\ORM\Mapping\QuoteStrategy');
        $this->configuration->setQuoteStrategy($quoteStrategy);
        self::assertSame($quoteStrategy, $this->configuration->getQuoteStrategy());
    }

    /**
     * @group DDC-1955
     */
    public function testSetGetEntityListenerResolver()
    {
        self::assertInstanceOf('Doctrine\ORM\Mapping\EntityListenerResolver', $this->configuration->getEntityListenerResolver());
        self::assertInstanceOf('Doctrine\ORM\Mapping\DefaultEntityListenerResolver', $this->configuration->getEntityListenerResolver());
        $resolver = $this->getMock('Doctrine\ORM\Mapping\EntityListenerResolver');
        $this->configuration->setEntityListenerResolver($resolver);
        self::assertSame($resolver, $this->configuration->getEntityListenerResolver());
    }

    /**
     * @group DDC-2183
     */
    public function testSetGetSecondLevelCacheConfig()
    {
        $mockClass = $this->getMock('Doctrine\ORM\Cache\CacheConfiguration');

        self::assertNull($this->configuration->getSecondLevelCacheConfiguration());
        $this->configuration->setSecondLevelCacheConfiguration($mockClass);
        self::assertEquals($mockClass, $this->configuration->getSecondLevelCacheConfiguration());
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
