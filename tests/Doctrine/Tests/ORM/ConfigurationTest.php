<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\Exception\MetadataCacheNotConfigured;
use Doctrine\ORM\Cache\Exception\MetadataCacheUsesNonPersistentCache;
use Doctrine\ORM\Cache\Exception\QueryCacheNotConfigured;
use Doctrine\ORM\Cache\Exception\QueryCacheUsesNonPersistentCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\InvalidEntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Exception\ProxyClassesAlwaysRegenerating;
use Doctrine\ORM\Mapping as AnnotationNamespace;
use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Tests\DoctrineTestCase;
use Doctrine\Tests\Models\DDC753\DDC753CustomRepository;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;
use stdClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function class_exists;

/**
 * Tests for the Configuration object
 */
class ConfigurationTest extends DoctrineTestCase
{
    use VerifyDeprecations;

    /** @var Configuration */
    private $configuration;

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
        self::assertSame(ProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses()); // defaults

        $this->configuration->setAutoGenerateProxyClasses(false);
        self::assertSame(ProxyFactory::AUTOGENERATE_NEVER, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(true);
        self::assertSame(ProxyFactory::AUTOGENERATE_ALWAYS, $this->configuration->getAutoGenerateProxyClasses());

        $this->configuration->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        self::assertSame(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS, $this->configuration->getAutoGenerateProxyClasses());
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
    }

    public function testNewDefaultAnnotationDriverWithSimpleAnnotationReader(): void
    {
        if (! class_exists(SimpleAnnotationReader::class)) {
            self::markTestSkipped('Requires doctrine/annotations 1.x');
        }

        $paths           = [__DIR__];
        $reflectionClass = new ReflectionClass(ConfigurationTestAnnotationReaderChecker::class);

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
        if (class_exists(PersistentObject::class)) {
            $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8818');
        } else {
            $this->expectException(NotSupported::class);
        }

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

    public function testAddGetNamedQuery(): void
    {
        $dql = 'SELECT u FROM User u';
        $this->configuration->addNamedQuery('QueryName', $dql);
        self::assertSame($dql, $this->configuration->getNamedQuery('QueryName'));
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('a named query');
        $this->configuration->getNamedQuery('NonExistingQuery');
    }

    public function testAddGetNamedNativeQuery(): void
    {
        $sql = 'SELECT * FROM user';
        $rsm = $this->createMock(ResultSetMapping::class);
        $this->configuration->addNamedNativeQuery('QueryName', $sql, $rsm);
        $fetched = $this->configuration->getNamedNativeQuery('QueryName');
        self::assertSame($sql, $fetched[0]);
        self::assertSame($rsm, $fetched[1]);
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('a named native query');
        $this->configuration->getNamedNativeQuery('NonExistingQuery');
    }

    /**
     * Configures $this->configuration to use production settings.
     *
     * @param string|null $skipCache Do not configure a cache of this type, either "query" or "metadata".
     */
    protected function setProductionSettings(?string $skipCache = null): void
    {
        $this->configuration->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_NEVER);

        $cache = $this->createMock(Cache::class);

        if ($skipCache !== 'query') {
            $this->configuration->setQueryCacheImpl($cache);
        }

        if ($skipCache !== 'metadata') {
            $this->configuration->setMetadataCacheImpl($cache);
        }
    }

    public function testEnsureProductionSettings(): void
    {
        $this->setProductionSettings();
        $this->configuration->ensureProductionSettings();

        $this->addToAssertionCount(1);
    }

    public function testEnsureProductionSettingsWithNewMetadataCache(): void
    {
        $this->setProductionSettings('metadata');
        $this->configuration->setMetadataCache(new ArrayAdapter());

        $this->configuration->ensureProductionSettings();

        $this->addToAssertionCount(1);
    }

    public function testEnsureProductionSettingsMissingQueryCache(): void
    {
        $this->setProductionSettings('query');

        $this->expectException(QueryCacheNotConfigured::class);
        $this->expectExceptionMessage('Query Cache is not configured.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsMissingMetadataCache(): void
    {
        $this->setProductionSettings('metadata');

        $this->expectException(MetadataCacheNotConfigured::class);
        $this->expectExceptionMessage('Metadata Cache is not configured.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsQueryArrayCache(): void
    {
        if (! class_exists(ArrayCache::class)) {
            self::markTestSkipped('Test only applies with doctrine/cache 1.x');
        }

        $this->setProductionSettings();
        $this->configuration->setQueryCacheImpl(new ArrayCache());

        $this->expectException(QueryCacheUsesNonPersistentCache::class);
        $this->expectExceptionMessage('Query Cache uses a non-persistent cache driver, Doctrine\Common\Cache\ArrayCache.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsLegacyMetadataArrayCache(): void
    {
        if (! class_exists(ArrayCache::class)) {
            self::markTestSkipped('Test only applies with doctrine/cache 1.x');
        }

        $this->setProductionSettings();
        $this->configuration->setMetadataCacheImpl(new ArrayCache());

        $this->expectException(MetadataCacheUsesNonPersistentCache::class);
        $this->expectExceptionMessage('Metadata Cache uses a non-persistent cache driver, Doctrine\Common\Cache\ArrayCache.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesAlways(): void
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_ALWAYS);

        $this->expectException(ProxyClassesAlwaysRegenerating::class);
        $this->expectExceptionMessage('Proxy Classes are always regenerating.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesFileNotExists(): void
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Proxy Classes are always regenerating.');

        $this->configuration->ensureProductionSettings();
    }

    public function testEnsureProductionSettingsAutoGenerateProxyClassesEval(): void
    {
        $this->setProductionSettings();
        $this->configuration->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Proxy Classes are always regenerating.');

        $this->configuration->ensureProductionSettings();
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
        $this->expectException(InvalidEntityRepository::class);
        $this->expectExceptionMessage('Invalid repository class \'Doctrine\Tests\ORM\ConfigurationTest\'. It must be a Doctrine\ORM\EntityRepository.');
        $this->configuration->setDefaultRepositoryClassName(self::class);
    }

    public function testSetDeprecatedDefaultRepositoryClassName(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9533');

        $this->configuration->setDefaultRepositoryClassName(DeprecatedRepository::class);
        self::assertSame(DeprecatedRepository::class, $this->configuration->getDefaultRepositoryClassName());
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

    /** @group DDC-1955 */
    public function testSetGetEntityListenerResolver(): void
    {
        self::assertInstanceOf(EntityListenerResolver::class, $this->configuration->getEntityListenerResolver());
        self::assertInstanceOf(AnnotationNamespace\DefaultEntityListenerResolver::class, $this->configuration->getEntityListenerResolver());
        $resolver = $this->createMock(EntityListenerResolver::class);
        $this->configuration->setEntityListenerResolver($resolver);
        self::assertSame($resolver, $this->configuration->getEntityListenerResolver());
    }

    /** @group DDC-2183 */
    public function testSetGetSecondLevelCacheConfig(): void
    {
        $mockClass = $this->createMock(CacheConfiguration::class);

        self::assertNull($this->configuration->getSecondLevelCacheConfiguration());
        $this->configuration->setSecondLevelCacheConfiguration($mockClass);
        self::assertEquals($mockClass, $this->configuration->getSecondLevelCacheConfiguration());
    }

    /** @group GH10313 */
    public function testSetGetTypedFieldMapper(): void
    {
        self::assertEmpty($this->configuration->getTypedFieldMapper());
        $defaultTypedFieldMapper = new DefaultTypedFieldMapper();
        $this->configuration->setTypedFieldMapper($defaultTypedFieldMapper);
        self::assertSame($defaultTypedFieldMapper, $this->configuration->getTypedFieldMapper());
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

class DeprecatedRepository implements ObjectRepository
{
    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return null;
    }

    public function findAll(): array
    {
        return [];
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        return null;
    }

    public function getClassName(): string
    {
        return stdClass::class;
    }
}
