<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\Mapping as AnnotationNamespace;
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
        $this->assertSame(null, $this->configuration->getProxyDir()); // defaults

        $this->assertSame($this->configuration, $this->configuration->setProxyDir(__DIR__));
        $this->assertSame(__DIR__, $this->configuration->getProxyDir());
    }

    public function testSetGetAutoGenerateProxyClasses()
    {
        $this->assertSame(true, $this->configuration->getAutoGenerateProxyClasses()); // defaults

        $this->assertSame($this->configuration, $this->configuration->setAutoGenerateProxyClasses(false));
        $this->assertSame(false, $this->configuration->getAutoGenerateProxyClasses());
    }

    public function testSetGetProxyNamespace()
    {
        $this->assertSame(null, $this->configuration->getProxyNamespace()); // defaults

        $this->assertSame($this->configuration, $this->configuration->setProxyNamespace(__NAMESPACE__));
        $this->assertSame(__NAMESPACE__, $this->configuration->getProxyNamespace());
    }

    public function testSetGetMetadataDriverImpl()
    {
        $this->assertSame(null, $this->configuration->getMetadataDriverImpl()); // defaults

        $metadataDriver = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');
        $this->assertSame($this->configuration, $this->configuration->setMetadataDriverImpl($metadataDriver));
        $this->assertSame($metadataDriver, $this->configuration->getMetadataDriverImpl());
    }

    public function testNewDefaultAnnotationDriver()
    {
        $paths = array(__DIR__);
        $reflectionClass = new ReflectionClass(__NAMESPACE__ . '\ConfigurationTestAnnotationReaderChecker');

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths, false);
        $reader = $annotationDriver->getReader();
        $annotation = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('namespacedAnnotationMethod'),
            'Doctrine\ORM\Mapping\PrePersist'
        );
        $this->assertInstanceOf('Doctrine\ORM\Mapping\PrePersist', $annotation);

        $annotationDriver = $this->configuration->newDefaultAnnotationDriver($paths);
        $reader = $annotationDriver->getReader();
        $annotation = $reader->getMethodAnnotation(
            $reflectionClass->getMethod('simpleAnnotationMethod'),
            'Doctrine\ORM\Mapping\PrePersist'
        );
        $this->assertInstanceOf('Doctrine\ORM\Mapping\PrePersist', $annotation);
    }

    public function testSetGetEntityNamespace()
    {
        $this->assertSame($this->configuration, $this->configuration->addEntityNamespace('TestNamespace', __NAMESPACE__));
        $this->assertSame(__NAMESPACE__, $this->configuration->getEntityNamespace('TestNamespace'));
        $namespaces = array('OtherNamespace' => __NAMESPACE__);
        $this->assertSame($this->configuration, $this->configuration->setEntityNamespaces($namespaces));
        $this->assertSame($namespaces, $this->configuration->getEntityNamespaces());
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->getEntityNamespace('NonExistingNamespace');
    }

    public function testSetGetQueryCacheImpl()
    {
        $this->assertSame(null, $this->configuration->getQueryCacheImpl()); // defaults
        $queryCacheImpl = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->assertSame($this->configuration, $this->configuration->setQueryCacheImpl($queryCacheImpl));
        $this->assertSame($queryCacheImpl, $this->configuration->getQueryCacheImpl());
    }

    public function testSetGetHydrationCacheImpl()
    {
        $this->assertSame(null, $this->configuration->getHydrationCacheImpl()); // defaults
        $queryCacheImpl = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->assertSame($this->configuration, $this->configuration->setHydrationCacheImpl($queryCacheImpl));
        $this->assertSame($queryCacheImpl, $this->configuration->getHydrationCacheImpl());
    }

    public function testSetGetMetadataCacheImpl()
    {
        $this->assertSame(null, $this->configuration->getMetadataCacheImpl()); // defaults
        $queryCacheImpl = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->assertSame($this->configuration, $this->configuration->setMetadataCacheImpl($queryCacheImpl));
        $this->assertSame($queryCacheImpl, $this->configuration->getMetadataCacheImpl());
    }

    public function testAddGetNamedQuery()
    {
        $dql = 'SELECT u FROM User u';
        $this->assertSame($this->configuration, $this->configuration->addNamedQuery('QueryName', $dql));
        $this->assertSame($dql, $this->configuration->getNamedQuery('QueryName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->getNamedQuery('NonExistingQuery');
    }

    public function testAddGetNamedNativeQuery()
    {
        $sql = 'SELECT * FROM user';
        $rsm = $this->getMock('Doctrine\ORM\Query\ResultSetMapping');
        $this->assertSame($this->configuration, $this->configuration->addNamedNativeQuery('QueryName', $sql, $rsm));
        $fetched = $this->configuration->getNamedNativeQuery('QueryName');
        $this->assertSame($sql, $fetched[0]);
        $this->assertSame($rsm, $fetched[1]);
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->getNamedQuery('NonExistingQuery');
    }

    public function ensureProductionSettings()
    {
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->configuration->setAutoGenerateProxyClasses(true);

        try {
            $this->configuration->ensureProductionSettings();
            $this->fail('Didn\'t check all production settings');
        } catch (ORMException $e) {}

        $this->configuration->setQueryCacheImpl($cache);

        try {
            $this->configuration->ensureProductionSettings();
            $this->fail('Didn\'t check all production settings');
        } catch (ORMException $e) {}

        $this->configuration->setMetadataCacheImpl($cache);

        try {
            $this->configuration->ensureProductionSettings();
            $this->fail('Didn\'t check all production settings');
        } catch (ORMException $e) {}

        $this->configuration->setAutoGenerateProxyClasses(false);
        $this->configuration->ensureProductionSettings();
    }

    public function testAddGetCustomStringFunction()
    {
        $this->assertSame($this->configuration, $this->configuration->addCustomStringFunction('FunctionName', __CLASS__));
        $this->assertSame(__CLASS__, $this->configuration->getCustomStringFunction('FunctionName'));
        $this->assertSame(null, $this->configuration->getCustomStringFunction('NonExistingFunction'));
        $this->assertSame($this->configuration, $this->configuration->setCustomStringFunctions(array('OtherFunctionName' => __CLASS__)));
        $this->assertSame(__CLASS__, $this->configuration->getCustomStringFunction('OtherFunctionName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->addCustomStringFunction('concat', __CLASS__);
    }

    public function testAddGetCustomNumericFunction()
    {
        $this->assertSame($this->configuration, $this->configuration->addCustomNumericFunction('FunctionName', __CLASS__));
        $this->assertSame(__CLASS__, $this->configuration->getCustomNumericFunction('FunctionName'));
        $this->assertSame(null, $this->configuration->getCustomNumericFunction('NonExistingFunction'));
        $this->assertSame($this->configuration, $this->configuration->setCustomNumericFunctions(array('OtherFunctionName' => __CLASS__)));
        $this->assertSame(__CLASS__, $this->configuration->getCustomNumericFunction('OtherFunctionName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->addCustomNumericFunction('abs', __CLASS__);
    }

    public function testAddGetCustomDatetimeFunction()
    {
        $this->assertSame($this->configuration, $this->configuration->addCustomDatetimeFunction('FunctionName', __CLASS__));
        $this->assertSame(__CLASS__, $this->configuration->getCustomDatetimeFunction('FunctionName'));
        $this->assertSame(null, $this->configuration->getCustomDatetimeFunction('NonExistingFunction'));
        $this->assertSame($this->configuration, $this->configuration->setCustomDatetimeFunctions(array('OtherFunctionName' => __CLASS__)));
        $this->assertSame(__CLASS__, $this->configuration->getCustomDatetimeFunction('OtherFunctionName'));
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->addCustomDatetimeFunction('date_add', __CLASS__);
    }

    public function testAddGetCustomHydrationMode()
    {
        $this->assertSame(null, $this->configuration->getCustomHydrationMode('NonExisting'));
        $this->assertSame($this->configuration, $this->configuration->addCustomHydrationMode('HydrationModeName', __CLASS__));
        $this->assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('HydrationModeName'));
    }

    public function testSetCustomHydrationModes()
    {
        $this->configuration->addCustomHydrationMode('HydrationModeName', __CLASS__);
        $this->assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('HydrationModeName'));

        $this->assertSame($this->configuration, $this->configuration->setCustomHydrationModes(array('AnotherHydrationModeName' => __CLASS__)));

        $this->assertNull($this->configuration->getCustomHydrationMode('HydrationModeName'));
        $this->assertSame(__CLASS__, $this->configuration->getCustomHydrationMode('AnotherHydrationModeName'));
    }

    public function testSetGetClassMetadataFactoryName()
    {
        $this->assertSame('Doctrine\ORM\Mapping\ClassMetadataFactory', $this->configuration->getClassMetadataFactoryName());
        $this->assertSame($this->configuration, $this->configuration->setClassMetadataFactoryName(__CLASS__));
        $this->assertSame(__CLASS__, $this->configuration->getClassMetadataFactoryName());
    }

    public function testAddGetFilters()
    {
        $this->assertSame(null, $this->configuration->getFilterClassName('NonExistingFilter'));
        $this->assertSame($this->configuration, $this->configuration->addFilter('FilterName', __CLASS__));
        $this->assertSame(__CLASS__, $this->configuration->getFilterClassName('FilterName'));
    }

    public function testSetDefaultRepositoryClassName()
    {
        $this->assertSame('Doctrine\ORM\EntityRepository', $this->configuration->getDefaultRepositoryClassName());
        $repositoryClass = 'Doctrine\Tests\Models\DDC753\DDC753CustomRepository';
        $this->assertSame($this->configuration, $this->configuration->setDefaultRepositoryClassName($repositoryClass));
        $this->assertSame($repositoryClass, $this->configuration->getDefaultRepositoryClassName());
        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->configuration->setDefaultRepositoryClassName(__CLASS__);
    }

    public function testSetGetNamingStrategy()
    {
        $this->assertInstanceOf('Doctrine\ORM\Mapping\NamingStrategy', $this->configuration->getNamingStrategy());
        $namingStrategy = $this->getMock('Doctrine\ORM\Mapping\NamingStrategy');
        $this->assertSame($this->configuration, $this->configuration->setNamingStrategy($namingStrategy));
        $this->assertSame($namingStrategy, $this->configuration->getNamingStrategy());
    }

    public function testSetGetQuoteStrategy()
    {
        $this->assertInstanceOf('Doctrine\ORM\Mapping\QuoteStrategy', $this->configuration->getQuoteStrategy());
        $quoteStrategy = $this->getMock('Doctrine\ORM\Mapping\QuoteStrategy');
        $this->assertSame($this->configuration, $this->configuration->setQuoteStrategy($quoteStrategy));
        $this->assertSame($quoteStrategy, $this->configuration->getQuoteStrategy());
    }

    /**
     * @group DDC-1955
     */
    public function testSetGetEntityListenerResolver()
    {
        $this->assertInstanceOf('Doctrine\ORM\Mapping\EntityListenerResolver', $this->configuration->getEntityListenerResolver());
        $this->assertInstanceOf('Doctrine\ORM\Mapping\DefaultEntityListenerResolver', $this->configuration->getEntityListenerResolver());
        $resolver = $this->getMock('Doctrine\ORM\Mapping\EntityListenerResolver');
        $this->assertSame($this->configuration, $this->configuration->setEntityListenerResolver($resolver));
        $this->assertSame($resolver, $this->configuration->getEntityListenerResolver());
    }

    public function testSetGetRepositoryFactory()
    {
        $this->assertInstanceOf('Doctrine\ORM\Repository\RepositoryFactory', $this->configuration->getRepositoryFactory());
        $this->assertInstanceOf('Doctrine\ORM\Repository\DefaultRepositoryFactory', $this->configuration->getRepositoryFactory());
        $factory = $this->getMock('Doctrine\ORM\Repository\RepositoryFactory');
        $this->assertSame($this->configuration, $this->configuration->setRepositoryFactory($factory));
        $this->assertSame($factory, $this->configuration->getRepositoryFactory());
    }

    /**
     * @group DDC-2183
     */
    public function testSetGetSecondLevelCacheConfig()
    {
        $mockClass = $this->getMock('Doctrine\ORM\Cache\CacheConfiguration');

        $this->assertNull($this->configuration->getSecondLevelCacheConfiguration());
        $this->assertSame($this->configuration, $this->configuration->setSecondLevelCacheConfiguration($mockClass));
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
