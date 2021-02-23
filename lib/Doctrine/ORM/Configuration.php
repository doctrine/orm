<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache as CacheDriver;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\Exception\MetadataCacheNotConfigured;
use Doctrine\ORM\Cache\Exception\MetadataCacheUsesNonPersistentCache;
use Doctrine\ORM\Cache\Exception\QueryCacheNotConfigured;
use Doctrine\ORM\Cache\Exception\QueryCacheUsesNonPersistentCache;
use Doctrine\ORM\Exception\InvalidEntityRepository;
use Doctrine\ORM\Exception\ProxyClassesAlwaysRegenerating;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use Doctrine\ORM\Proxy\Factory\ProxyFactory;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory;
use ProxyManager\Configuration as ProxyManagerConfiguration;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ReflectionClass;
use function strtolower;

/**
 * Configuration container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM.
 *
 * {@internal When adding a new configuration option just write a getter/setter pair. }}
 */
class Configuration extends DBALConfiguration
{
    /** @var ProxyManagerConfiguration|null */
    private $proxyManagerConfiguration;

    /** @var MappingDriver|null */
    private $metadataDriver;

    /** @var CacheDriver|null */
    private $queryCache;

    /** @var CacheDriver|null */
    private $hydrationCache;

    /** @var CacheDriver|null */
    private $metadataCache;

    /** @var string[][]|ResultSetMapping[][] tuples of [$sqlString, $resultSetMapping] indexed by query name */
    private $customStringFunctions = [];

    /** @var string[][]|ResultSetMapping[][] tuples of [$sqlString, $resultSetMapping] indexed by query name */
    private $customNumericFunctions = [];

    /** @var string[][]|ResultSetMapping[][] tuples of [$sqlString, $resultSetMapping] indexed by query name */
    private $customDatetimeFunctions = [];

    /** @var string[] of hydrator class names, indexed by mode name */
    private $customHydrationModes = [];

    /** @var string */
    private $classMetadataFactoryClassName = ClassMetadataFactory::class;

    /** @var string[] of filter class names, indexed by filter name */
    private $filters;

    /** @var string */
    private $defaultRepositoryClassName = EntityRepository::class;

    /** @var NamingStrategy|null */
    private $namingStrategy;

    /** @var EntityListenerResolver|null */
    private $entityListenerResolver;

    /** @var RepositoryFactory|null */
    private $repositoryFactory;

    /** @var bool */
    private $isSecondLevelCacheEnabled = false;

    /** @var CacheConfiguration|null */
    private $secondLevelCacheConfiguration;

    /** @var mixed[] indexed by hint name */
    private $defaultQueryHints = [];

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     */
    public function setProxyDir(string $directory) : void
    {
        $this->getProxyManagerConfiguration()->setProxiesTargetDir($directory);
        $this->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
    }

    /**
     * Sets the strategy for automatically generating proxy classes.
     *
     * @param bool|int $autoGenerate Possible values are constants of Doctrine\ORM\Proxy\Factory\ProxyFactory.
     *                               True is converted to AUTOGENERATE_ALWAYS, false to AUTOGENERATE_NEVER.
     */
    public function setAutoGenerateProxyClasses($autoGenerate) : void
    {
        $proxyManagerConfig = $this->getProxyManagerConfiguration();

        switch ((int) $autoGenerate) {
            case ProxyFactory::AUTOGENERATE_ALWAYS:
            case ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS:
                $proxyManagerConfig->setGeneratorStrategy(new FileWriterGeneratorStrategy(
                    new FileLocator($proxyManagerConfig->getProxiesTargetDir())
                ));

                return;
            case ProxyFactory::AUTOGENERATE_NEVER:
            case ProxyFactory::AUTOGENERATE_EVAL:
            default:
                $proxyManagerConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());

                return;
        }
    }

    /**
     * Sets the namespace where proxy classes reside.
     */
    public function setProxyNamespace(string $namespace) : void
    {
        $this->getProxyManagerConfiguration()->setProxiesNamespace($namespace);
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $metadataDriver) : void
    {
        $this->metadataDriver = $metadataDriver;
    }

    /**
     * Adds a new default annotation driver with a correctly configured annotation reader.
     *
     * @param string[] $paths
     */
    public function newDefaultAnnotationDriver(array $paths = []) : AnnotationDriver
    {
        AnnotationRegistry::registerFile(__DIR__ . '/Annotation/DoctrineAnnotations.php');

        $reader = new CachedReader(new AnnotationReader(), new ArrayCache());

        return new AnnotationDriver($reader, $paths);
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     */
    public function getMetadataDriverImpl() : ?MappingDriver
    {
        return $this->metadataDriver;
    }

    /**
     * Gets the cache driver implementation that is used for the query cache (SQL cache).
     */
    public function getQueryCacheImpl() : ?CacheDriver
    {
        return $this->queryCache;
    }

    /**
     * Sets the cache driver implementation that is used for the query cache (SQL cache).
     */
    public function setQueryCacheImpl(CacheDriver $queryCache) : void
    {
        $this->queryCache = $queryCache;
    }

    /**
     * Gets the cache driver implementation that is used for the hydration cache (SQL cache).
     */
    public function getHydrationCacheImpl() : ?CacheDriver
    {
        return $this->hydrationCache;
    }

    /**
     * Sets the cache driver implementation that is used for the hydration cache (SQL cache).
     */
    public function setHydrationCacheImpl(CacheDriver $hydrationCache) : void
    {
        $this->hydrationCache = $hydrationCache;
    }

    /**
     * Gets the cache driver implementation that is used for metadata caching.
     */
    public function getMetadataCacheImpl() : ?CacheDriver
    {
        return $this->metadataCache;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     */
    public function setMetadataCacheImpl(CacheDriver $metadataCache) : void
    {
        $this->metadataCache = $metadataCache;
    }

    /**
     * Ensures that this Configuration instance contains settings that are
     * suitable for a production environment.
     *
     * @throws ORMException If a configuration setting has a value that is not
     *                      suitable for a production environment.
     */
    public function ensureProductionSettings() : void
    {
        $queryCacheImpl = $this->getQueryCacheImpl();

        if (! $queryCacheImpl) {
            throw QueryCacheNotConfigured::create();
        }

        if ($queryCacheImpl instanceof ArrayCache) {
            throw QueryCacheUsesNonPersistentCache::fromDriver($queryCacheImpl);
        }

        $metadataCacheImpl = $this->getMetadataCacheImpl();

        if (! $metadataCacheImpl) {
            throw MetadataCacheNotConfigured::create();
        }

        if ($metadataCacheImpl instanceof ArrayCache) {
            throw MetadataCacheUsesNonPersistentCache::fromDriver($metadataCacheImpl);
        }

        if ($this->getProxyManagerConfiguration()->getGeneratorStrategy() instanceof EvaluatingGeneratorStrategy) {
            throw ProxyClassesAlwaysRegenerating::create();
        }
    }

    /**
     * Registers a custom DQL function that produces a string value.
     * Such a function can then be used in any DQL statement in any place where string
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string|callable $classNameOrFactory Class name or a callable that returns the function.
     */
    public function addCustomStringFunction(string $functionName, $classNameOrFactory) : void
    {
        $this->customStringFunctions[strtolower($functionName)] = $classNameOrFactory;
    }

    /**
     * Gets the implementation class name of a registered custom string DQL function.
     *
     * @return string|callable|null
     */
    public function getCustomStringFunction(string $functionName)
    {
        return $this->customStringFunctions[strtolower($functionName)] ?? null;
    }

    /**
     * Sets a map of custom DQL string functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added string functions are discarded.
     *
     * @param string[]|callable[] $functions The map of custom DQL string functions.
     */
    public function setCustomStringFunctions(array $functions) : void
    {
        foreach ($functions as $name => $className) {
            $this->addCustomStringFunction($name, $className);
        }
    }

    /**
     * Registers a custom DQL function that produces a numeric value.
     * Such a function can then be used in any DQL statement in any place where numeric
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string|callable $classNameOrFactory Class name or a callable that returns the function.
     */
    public function addCustomNumericFunction(string $functionName, $classNameOrFactory) : void
    {
        $this->customNumericFunctions[strtolower($functionName)] = $classNameOrFactory;
    }

    /**
     * Gets the implementation class name of a registered custom numeric DQL function.
     *
     * @return string|callable|null
     */
    public function getCustomNumericFunction(string $functionName)
    {
        return $this->customNumericFunctions[strtolower($functionName)] ?? null;
    }

    /**
     * Sets a map of custom DQL numeric functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added numeric functions are discarded.
     *
     * @param string[]|callable[] $functions The map of custom DQL numeric functions.
     */
    public function setCustomNumericFunctions(array $functions) : void
    {
        foreach ($functions as $name => $className) {
            $this->addCustomNumericFunction($name, $className);
        }
    }

    /**
     * Registers a custom DQL function that produces a date/time value.
     * Such a function can then be used in any DQL statement in any place where date/time
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string|callable $classNameOrFactory Class name or a callable that returns the function.
     */
    public function addCustomDatetimeFunction(string $functionName, $classNameOrFactory)
    {
        $this->customDatetimeFunctions[strtolower($functionName)] = $classNameOrFactory;
    }

    /**
     * Gets the implementation class name of a registered custom date/time DQL function.
     *
     * @return string|callable|null
     */
    public function getCustomDatetimeFunction(string $functionName)
    {
        return $this->customDatetimeFunctions[strtolower($functionName)] ?? null;
    }

    /**
     * Sets a map of custom DQL date/time functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added date/time functions are discarded.
     *
     * @param iterable|string[] $functions The map of custom DQL date/time functions.
     */
    public function setCustomDatetimeFunctions(array $functions) : void
    {
        foreach ($functions as $name => $className) {
            $this->addCustomDatetimeFunction($name, $className);
        }
    }

    /**
     * Sets the custom hydrator modes in one pass.
     *
     * @param iterable|string[] $modes An iterable of string $modeName => string $hydratorClassName
     */
    public function setCustomHydrationModes(iterable $modes) : void
    {
        $this->customHydrationModes = [];

        foreach ($modes as $modeName => $hydrator) {
            $this->addCustomHydrationMode($modeName, $hydrator);
        }
    }

    /**
     * Gets the hydrator class for the given hydration mode name.
     *
     * @return string|null The hydrator class name.
     */
    public function getCustomHydrationMode(string $modeName) : ?string
    {
        return $this->customHydrationModes[$modeName] ?? null;
    }

    /**
     * Adds a custom hydration mode.
     */
    public function addCustomHydrationMode(string $modeName, string $hydratorClassName) : void
    {
        $this->customHydrationModes[$modeName] = $hydratorClassName;
    }

    /**
     * Sets a class metadata factory.
     */
    public function setClassMetadataFactoryName(string $classMetadataFactoryClassName) : void
    {
        $this->classMetadataFactoryClassName = $classMetadataFactoryClassName;
    }

    public function getClassMetadataFactoryName() : string
    {
        return $this->classMetadataFactoryClassName;
    }

    /**
     * Adds a filter to the list of possible filters.
     */
    public function addFilter(string $filterName, string $filterClassName) : void
    {
        $this->filters[$filterName] = $filterClassName;
    }

    /**
     * Gets the class name for a given filter name.
     *
     * @return string|null The class name of the filter
     */
    public function getFilterClassName(string $filterName) : ?string
    {
        return $this->filters[$filterName] ?? null;
    }

    /**
     * Sets default repository class.
     *
     * @throws ORMException If not is a \Doctrine\Common\Persistence\ObjectRepository implementation.
     */
    public function setDefaultRepositoryClassName(string $repositoryClassName) : void
    {
        $reflectionClass = new ReflectionClass($repositoryClassName);

        if (! $reflectionClass->implementsInterface(ObjectRepository::class)) {
            throw InvalidEntityRepository::fromClassName($repositoryClassName);
        }

        $this->defaultRepositoryClassName = $repositoryClassName;
    }

    /**
     * Get default repository class.
     */
    public function getDefaultRepositoryClassName() : string
    {
        return $this->defaultRepositoryClassName;
    }

    /**
     * Sets naming strategy.
     */
    public function setNamingStrategy(NamingStrategy $namingStrategy) : void
    {
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * Gets naming strategy..
     */
    public function getNamingStrategy() : ?NamingStrategy
    {
        return $this->namingStrategy
            ?? $this->namingStrategy = new DefaultNamingStrategy();
    }

    /**
     * Set the entity listener resolver.
     */
    public function setEntityListenerResolver(EntityListenerResolver $resolver) : void
    {
        $this->entityListenerResolver = $resolver;
    }

    /**
     * Get the entity listener resolver.
     */
    public function getEntityListenerResolver() : EntityListenerResolver
    {
        return $this->entityListenerResolver
            ?? $this->entityListenerResolver = new DefaultEntityListenerResolver();
    }

    /**
     * Set the entity repository factory.
     */
    public function setRepositoryFactory(RepositoryFactory $repositoryFactory) : void
    {
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * Get the entity repository factory.
     */
    public function getRepositoryFactory() : RepositoryFactory
    {
        return $this->repositoryFactory
            ?? $this->repositoryFactory = new DefaultRepositoryFactory();
    }

    public function isSecondLevelCacheEnabled() : bool
    {
        return $this->isSecondLevelCacheEnabled;
    }

    public function setSecondLevelCacheEnabled(bool $flag = true) : void
    {
        $this->isSecondLevelCacheEnabled = $flag;
    }

    public function setSecondLevelCacheConfiguration(CacheConfiguration $cacheConfig) : void
    {
        $this->secondLevelCacheConfiguration = $cacheConfig;
    }

    public function getSecondLevelCacheConfiguration() : ?CacheConfiguration
    {
        if ($this->isSecondLevelCacheEnabled && ! $this->secondLevelCacheConfiguration) {
            $this->secondLevelCacheConfiguration = new CacheConfiguration();
        }

        return $this->secondLevelCacheConfiguration;
    }

    /**
     * Returns query hints, which will be applied to every query in application
     *
     * @return mixed[]
     */
    public function getDefaultQueryHints() : array
    {
        return $this->defaultQueryHints;
    }

    /**
     * Sets array of query hints, which will be applied to every query in application
     *
     * @param mixed[] $defaultQueryHints
     */
    public function setDefaultQueryHints(array $defaultQueryHints) : void
    {
        $this->defaultQueryHints = $defaultQueryHints;
    }

    /**
     * Gets the value of a default query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getDefaultQueryHint(string $hintName)
    {
        return $this->defaultQueryHints[$hintName] ?? false;
    }

    /**
     * Sets a default query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param mixed $value The value of the hint.
     */
    public function setDefaultQueryHint(string $hintName, $value) : void
    {
        $this->defaultQueryHints[$hintName] = $value;
    }

    public function buildGhostObjectFactory() : LazyLoadingGhostFactory
    {
        return new LazyLoadingGhostFactory(clone $this->getProxyManagerConfiguration());
    }

    public function getProxyManagerConfiguration() : ProxyManagerConfiguration
    {
        return $this->proxyManagerConfiguration
            ?? $this->proxyManagerConfiguration = new ProxyManagerConfiguration();
    }
}
