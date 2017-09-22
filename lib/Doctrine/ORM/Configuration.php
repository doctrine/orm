<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache as CacheDriver;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use Doctrine\ORM\Proxy\Factory\ProxyFactory;
use Doctrine\ORM\Proxy\Factory\StaticProxyFactory;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory;
use ProxyManager\Configuration as ProxyManagerConfiguration;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;

/**
 * Configuration container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM.
 *
 * Internal note: When adding a new configuration option just write a getter/setter pair.
 *
 * @since 2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Configuration extends \Doctrine\DBAL\Configuration
{
    /**
     * @var ProxyManagerConfiguration|null
     */
    private $proxyManagerConfiguration;

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     *
     * @param string $dir
     *
     * @return void
     */
    public function setProxyDir($dir)
    {
        $this->attributes['proxyDir'] = $dir;

        $this->getProxyManagerConfiguration()->setProxiesTargetDir($dir);
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     *
     * @return string|null
     *
     * @deprecated please do not use this anymore
     */
    public function getProxyDir()
    {
        return isset($this->attributes['proxyDir'])
            ? $this->attributes['proxyDir']
            : null;
    }

    /**
     * Gets the strategy for automatically generating proxy classes.
     *
     * @return int Possible values are constants of Doctrine\ORM\Proxy\Factory\StaticProxyFactory.
     *
     * @deprecated please do not use this anymore
     */
    public function getAutoGenerateProxyClasses()
    {
        return isset($this->attributes['autoGenerateProxyClasses'])
            ? $this->attributes['autoGenerateProxyClasses']
            : ProxyFactory::AUTOGENERATE_ALWAYS;
    }

    /**
     * Sets the strategy for automatically generating proxy classes.
     *
     * @param boolean|int $autoGenerate Possible values are constants of Doctrine\ORM\Proxy\Factory\StaticProxyFactory.
     *                                  True is converted to AUTOGENERATE_ALWAYS, false to AUTOGENERATE_NEVER.
     *
     * @return void
     */
    public function setAutoGenerateProxyClasses($autoGenerate)
    {
        $this->attributes['autoGenerateProxyClasses'] = (int) $autoGenerate;

        $proxyManagerConfig = $this->getProxyManagerConfiguration();

        switch ((int) $autoGenerate) {
            case StaticProxyFactory::AUTOGENERATE_ALWAYS:
            case StaticProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS:
                $proxyManagerConfig->setGeneratorStrategy(new FileWriterGeneratorStrategy(
                    new FileLocator($proxyManagerConfig->getProxiesTargetDir())
                ));

                return;
            case StaticProxyFactory::AUTOGENERATE_NEVER:
            case StaticProxyFactory::AUTOGENERATE_EVAL:
            default:
                $proxyManagerConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());

                return;
        }
    }

    /**
     * Gets the namespace where proxy classes reside.
     *
     * @return string|null
     *
     * @deprecated please do not use this anymore
     */
    public function getProxyNamespace()
    {
        return isset($this->attributes['proxyNamespace'])
            ? $this->attributes['proxyNamespace']
            : null;
    }

    /**
     * Sets the namespace where proxy classes reside.
     *
     * @param string $ns
     *
     * @return void
     */
    public function setProxyNamespace($ns)
    {
        $this->attributes['proxyNamespace'] = $ns;

        $this->getProxyManagerConfiguration()->setProxiesNamespace($ns);
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param MappingDriver $driverImpl
     *
     * @return void
     *
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $driverImpl)
    {
        $this->attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Adds a new default annotation driver with a correctly configured annotation reader.
     *
     * @param array $paths
     *
     * @return AnnotationDriver
     */
    public function newDefaultAnnotationDriver($paths = [])
    {
        AnnotationRegistry::registerFile(__DIR__ . '/Annotation/DoctrineAnnotations.php');

        $reader = new CachedReader(new AnnotationReader(), new ArrayCache());

        return new AnnotationDriver($reader, (array) $paths);
    }

    /**
     * Adds a namespace under a certain alias.
     *
     * @param string $alias
     * @param string $namespace
     *
     * @return void
     */
    public function addEntityNamespace($alias, $namespace)
    {
        $this->attributes['entityNamespaces'][$alias] = $namespace;
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @param string $entityNamespaceAlias
     *
     * @return string
     *
     * @throws ORMException
     */
    public function getEntityNamespace($entityNamespaceAlias)
    {
        if ( ! isset($this->attributes['entityNamespaces'][$entityNamespaceAlias])) {
            throw ORMException::unknownEntityNamespace($entityNamespaceAlias);
        }

        return trim($this->attributes['entityNamespaces'][$entityNamespaceAlias], '\\');
    }

    /**
     * Sets the entity alias map.
     *
     * @param array $entityNamespaces
     *
     * @return void
     */
    public function setEntityNamespaces(array $entityNamespaces)
    {
        $this->attributes['entityNamespaces'] = $entityNamespaces;
    }

    /**
     * Retrieves the list of registered entity namespace aliases.
     *
     * @return array
     */
    public function getEntityNamespaces()
    {
        return $this->attributes['entityNamespaces'];
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     *
     * @return MappingDriver|null
     *
     * @throws ORMException
     */
    public function getMetadataDriverImpl()
    {
        return isset($this->attributes['metadataDriverImpl'])
            ? $this->attributes['metadataDriverImpl']
            : null;
    }

    /**
     * Gets the cache driver implementation that is used for the query cache (SQL cache).
     *
     * @return \Doctrine\Common\Cache\Cache|null
     */
    public function getQueryCacheImpl()
    {
        return isset($this->attributes['queryCacheImpl'])
            ? $this->attributes['queryCacheImpl']
            : null;
    }

    /**
     * Sets the cache driver implementation that is used for the query cache (SQL cache).
     *
     * @param \Doctrine\Common\Cache\Cache $cacheImpl
     *
     * @return void
     */
    public function setQueryCacheImpl(CacheDriver $cacheImpl)
    {
        $this->attributes['queryCacheImpl'] = $cacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for the hydration cache (SQL cache).
     *
     * @return \Doctrine\Common\Cache\Cache|null
     */
    public function getHydrationCacheImpl()
    {
        return isset($this->attributes['hydrationCacheImpl'])
            ? $this->attributes['hydrationCacheImpl']
            : null;
    }

    /**
     * Sets the cache driver implementation that is used for the hydration cache (SQL cache).
     *
     * @param \Doctrine\Common\Cache\Cache $cacheImpl
     *
     * @return void
     */
    public function setHydrationCacheImpl(CacheDriver $cacheImpl)
    {
        $this->attributes['hydrationCacheImpl'] = $cacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for metadata caching.
     *
     * @return \Doctrine\Common\Cache\Cache|null
     */
    public function getMetadataCacheImpl()
    {
        return isset($this->attributes['metadataCacheImpl'])
            ? $this->attributes['metadataCacheImpl']
            : null;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param \Doctrine\Common\Cache\Cache $cacheImpl
     *
     * @return void
     */
    public function setMetadataCacheImpl(CacheDriver $cacheImpl)
    {
        $this->attributes['metadataCacheImpl'] = $cacheImpl;
    }

    /**
     * Adds a named DQL query to the configuration.
     *
     * @param string $name The name of the query.
     * @param string $dql  The DQL query string.
     *
     * @return void
     */
    public function addNamedQuery($name, $dql)
    {
        $this->attributes['namedQueries'][$name] = $dql;
    }

    /**
     * Gets a previously registered named DQL query.
     *
     * @param string $name The name of the query.
     *
     * @return string The DQL query.
     *
     * @throws ORMException
     */
    public function getNamedQuery($name)
    {
        if ( ! isset($this->attributes['namedQueries'][$name])) {
            throw ORMException::namedQueryNotFound($name);
        }

        return $this->attributes['namedQueries'][$name];
    }

    /**
     * Adds a named native query to the configuration.
     *
     * @param string                 $name The name of the query.
     * @param string                 $sql  The native SQL query string.
     * @param Query\ResultSetMapping $rsm  The ResultSetMapping used for the results of the SQL query.
     *
     * @return void
     */
    public function addNamedNativeQuery($name, $sql, Query\ResultSetMapping $rsm)
    {
        $this->attributes['namedNativeQueries'][$name] = [$sql, $rsm];
    }

    /**
     * Gets the components of a previously registered named native query.
     *
     * @param string $name The name of the query.
     *
     * @return array A tuple with the first element being the SQL string and the second
     *               element being the ResultSetMapping.
     *
     * @throws ORMException
     */
    public function getNamedNativeQuery($name)
    {
        if ( ! isset($this->attributes['namedNativeQueries'][$name])) {
            throw ORMException::namedNativeQueryNotFound($name);
        }

        return $this->attributes['namedNativeQueries'][$name];
    }

    /**
     * Ensures that this Configuration instance contains settings that are
     * suitable for a production environment.
     *
     * @return void
     *
     * @throws ORMException If a configuration setting has a value that is not
     *                      suitable for a production environment.
     */
    public function ensureProductionSettings()
    {
        $queryCacheImpl = $this->getQueryCacheImpl();

        if ( ! $queryCacheImpl) {
            throw ORMException::queryCacheNotConfigured();
        }

        if ($queryCacheImpl instanceof ArrayCache) {
            throw ORMException::queryCacheUsesNonPersistentCache($queryCacheImpl);
        }

        $metadataCacheImpl = $this->getMetadataCacheImpl();

        if ( ! $metadataCacheImpl) {
            throw ORMException::metadataCacheNotConfigured();
        }

        if ($metadataCacheImpl instanceof ArrayCache) {
            throw ORMException::metadataCacheUsesNonPersistentCache($metadataCacheImpl);
        }

        if ($this->getAutoGenerateProxyClasses()) {
            throw ORMException::proxyClassesAlwaysRegenerating();
        }
    }

    /**
     * Registers a custom DQL function that produces a string value.
     * Such a function can then be used in any DQL statement in any place where string
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string          $name      Function name.
     * @param string|callable $className Class name or a callable that returns the function.
     *
     * @return void
     */
    public function addCustomStringFunction($name, $className)
    {
        $this->attributes['customStringFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom string DQL function.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getCustomStringFunction($name)
    {
        $name = strtolower($name);

        return isset($this->attributes['customStringFunctions'][$name])
            ? $this->attributes['customStringFunctions'][$name]
            : null;
    }

    /**
     * Sets a map of custom DQL string functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added string functions are discarded.
     *
     * @param array $functions The map of custom DQL string functions.
     *
     * @return void
     */
    public function setCustomStringFunctions(array $functions)
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
     * @param string          $name      Function name.
     * @param string|callable $className Class name or a callable that returns the function.
     *
     * @return void
     */
    public function addCustomNumericFunction($name, $className)
    {
        $this->attributes['customNumericFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom numeric DQL function.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getCustomNumericFunction($name)
    {
        $name = strtolower($name);

        return isset($this->attributes['customNumericFunctions'][$name])
            ? $this->attributes['customNumericFunctions'][$name]
            : null;
    }

    /**
     * Sets a map of custom DQL numeric functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added numeric functions are discarded.
     *
     * @param array $functions The map of custom DQL numeric functions.
     *
     * @return void
     */
    public function setCustomNumericFunctions(array $functions)
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
     * @param string          $name      Function name.
     * @param string|callable $className Class name or a callable that returns the function.
     *
     * @return void
     */
    public function addCustomDatetimeFunction($name, $className)
    {
        $this->attributes['customDatetimeFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom date/time DQL function.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getCustomDatetimeFunction($name)
    {
        $name = strtolower($name);

        return isset($this->attributes['customDatetimeFunctions'][$name])
            ? $this->attributes['customDatetimeFunctions'][$name]
            : null;
    }

    /**
     * Sets a map of custom DQL date/time functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added date/time functions are discarded.
     *
     * @param array $functions The map of custom DQL date/time functions.
     *
     * @return void
     */
    public function setCustomDatetimeFunctions(array $functions)
    {
        foreach ($functions as $name => $className) {
            $this->addCustomDatetimeFunction($name, $className);
        }
    }

    /**
     * Sets the custom hydrator modes in one pass.
     *
     * @param array $modes An array of ($modeName => $hydrator).
     *
     * @return void
     */
    public function setCustomHydrationModes($modes)
    {
        $this->attributes['customHydrationModes'] = [];

        foreach ($modes as $modeName => $hydrator) {
            $this->addCustomHydrationMode($modeName, $hydrator);
        }
    }

    /**
     * Gets the hydrator class for the given hydration mode name.
     *
     * @param string $modeName The hydration mode name.
     *
     * @return string|null The hydrator class name.
     */
    public function getCustomHydrationMode($modeName)
    {
        return isset($this->attributes['customHydrationModes'][$modeName])
            ? $this->attributes['customHydrationModes'][$modeName]
            : null;
    }

    /**
     * Adds a custom hydration mode.
     *
     * @param string $modeName The hydration mode name.
     * @param string $hydrator The hydrator class name.
     *
     * @return void
     */
    public function addCustomHydrationMode($modeName, $hydrator)
    {
        $this->attributes['customHydrationModes'][$modeName] = $hydrator;
    }

    /**
     * Sets a class metadata factory.
     *
     * @param string $cmfName
     *
     * @return void
     */
    public function setClassMetadataFactoryName($cmfName)
    {
        $this->attributes['classMetadataFactoryName'] = $cmfName;
    }

    /**
     * @return string
     */
    public function getClassMetadataFactoryName()
    {
        if ( ! isset($this->attributes['classMetadataFactoryName'])) {
            $this->attributes['classMetadataFactoryName'] = ClassMetadataFactory::class;
        }

        return $this->attributes['classMetadataFactoryName'];
    }

    /**
     * Adds a filter to the list of possible filters.
     *
     * @param string $name      The name of the filter.
     * @param string $className The class name of the filter.
     */
    public function addFilter($name, $className)
    {
        $this->attributes['filters'][$name] = $className;
    }

    /**
     * Gets the class name for a given filter name.
     *
     * @param string $name The name of the filter.
     *
     * @return string The class name of the filter, or null if it is not
     *  defined.
     */
    public function getFilterClassName($name)
    {
        return isset($this->attributes['filters'][$name])
            ? $this->attributes['filters'][$name]
            : null;
    }

    /**
     * Sets default repository class.
     *
     * @since 2.2
     *
     * @param string $className
     *
     * @return void
     *
     * @throws ORMException If not is a \Doctrine\Common\Persistence\ObjectRepository
     */
    public function setDefaultRepositoryClassName($className)
    {
        $reflectionClass = new \ReflectionClass($className);

        if ( ! $reflectionClass->implementsInterface(ObjectRepository::class)) {
            throw ORMException::invalidEntityRepository($className);
        }

        $this->attributes['defaultRepositoryClassName'] = $className;
    }

    /**
     * Get default repository class.
     *
     * @since 2.2
     *
     * @return string
     */
    public function getDefaultRepositoryClassName()
    {
        return isset($this->attributes['defaultRepositoryClassName'])
            ? $this->attributes['defaultRepositoryClassName']
            : EntityRepository::class;
    }

    /**
     * Sets naming strategy.
     *
     * @since 2.3
     *
     * @param NamingStrategy $namingStrategy
     *
     * @return void
     */
    public function setNamingStrategy(NamingStrategy $namingStrategy)
    {
        $this->attributes['namingStrategy'] = $namingStrategy;
    }

    /**
     * Gets naming strategy..
     *
     * @since 2.3
     *
     * @return NamingStrategy
     */
    public function getNamingStrategy()
    {
        if ( ! isset($this->attributes['namingStrategy'])) {
            $this->attributes['namingStrategy'] = new DefaultNamingStrategy();
        }

        return $this->attributes['namingStrategy'];
    }

    /**
     * Set the entity listener resolver.
     *
     * @since 2.4
     * @param \Doctrine\ORM\Mapping\EntityListenerResolver $resolver
     */
    public function setEntityListenerResolver(EntityListenerResolver $resolver)
    {
        $this->attributes['entityListenerResolver'] = $resolver;
    }

    /**
     * Get the entity listener resolver.
     *
     * @since 2.4
     * @return \Doctrine\ORM\Mapping\EntityListenerResolver
     */
    public function getEntityListenerResolver()
    {
        if ( ! isset($this->attributes['entityListenerResolver'])) {
            $this->attributes['entityListenerResolver'] = new DefaultEntityListenerResolver();
        }

        return $this->attributes['entityListenerResolver'];
    }

    /**
     * Set the entity repository factory.
     *
     * @since 2.4
     * @param \Doctrine\ORM\Repository\RepositoryFactory $repositoryFactory
     */
    public function setRepositoryFactory(RepositoryFactory $repositoryFactory)
    {
        $this->attributes['repositoryFactory'] = $repositoryFactory;
    }

    /**
     * Get the entity repository factory.
     *
     * @since 2.4
     * @return \Doctrine\ORM\Repository\RepositoryFactory
     */
    public function getRepositoryFactory()
    {
        return isset($this->attributes['repositoryFactory'])
            ? $this->attributes['repositoryFactory']
            : new DefaultRepositoryFactory();
    }

    /**
     * @since 2.5
     *
     * @return boolean
     */
    public function isSecondLevelCacheEnabled()
    {
        return isset($this->attributes['isSecondLevelCacheEnabled'])
            ? $this->attributes['isSecondLevelCacheEnabled']
            : false;
    }

    /**
     * @since 2.5
     *
     * @param boolean $flag
     *
     * @return void
     */
    public function setSecondLevelCacheEnabled($flag = true)
    {
        $this->attributes['isSecondLevelCacheEnabled'] = (boolean) $flag;
    }

    /**
     * @since 2.5
     *
     * @param \Doctrine\ORM\Cache\CacheConfiguration $cacheConfig
     *
     * @return void
     */
    public function setSecondLevelCacheConfiguration(CacheConfiguration $cacheConfig)
    {
        $this->attributes['secondLevelCacheConfiguration'] = $cacheConfig;
    }

    /**
     * @since 2.5
     *
     * @return  \Doctrine\ORM\Cache\CacheConfiguration|null
     */
    public function getSecondLevelCacheConfiguration()
    {
        if ( ! isset($this->attributes['secondLevelCacheConfiguration']) && $this->isSecondLevelCacheEnabled()) {
            $this->attributes['secondLevelCacheConfiguration'] = new CacheConfiguration();
        }

        return isset($this->attributes['secondLevelCacheConfiguration'])
            ? $this->attributes['secondLevelCacheConfiguration']
            : null;
    }

    /**
     * Returns query hints, which will be applied to every query in application
     *
     * @since 2.5
     *
     * @return array
     */
    public function getDefaultQueryHints()
    {
        return isset($this->attributes['defaultQueryHints']) ? $this->attributes['defaultQueryHints'] : [];
    }

    /**
     * Sets array of query hints, which will be applied to every query in application
     *
     * @since 2.5
     *
     * @param array $defaultQueryHints
     */
    public function setDefaultQueryHints(array $defaultQueryHints)
    {
        $this->attributes['defaultQueryHints'] = $defaultQueryHints;
    }

    /**
     * Gets the value of a default query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @since 2.5
     *
     * @param string $name The name of the hint.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getDefaultQueryHint($name)
    {
        return isset($this->attributes['defaultQueryHints'][$name])
            ? $this->attributes['defaultQueryHints'][$name]
            : false;
    }

    /**
     * Sets a default query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @since 2.5
     *
     * @param string $name  The name of the hint.
     * @param mixed  $value The value of the hint.
     */
    public function setDefaultQueryHint($name, $value)
    {
        $this->attributes['defaultQueryHints'][$name] = $value;
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
