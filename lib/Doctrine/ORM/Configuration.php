<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Exception\InvalidEntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Psr\Cache\CacheItemPoolInterface;

use function class_exists;
use function is_a;
use function strtolower;

/**
 * Configuration container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM.
 *
 * Internal note: When adding a new configuration option just write a getter/setter pair.
 *
 * @psalm-import-type AutogenerateMode from ProxyFactory
 */
class Configuration extends \Doctrine\DBAL\Configuration
{
    /** @var mixed[] */
    protected $_attributes = [];

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     *
     * @param string $dir
     *
     * @return void
     */
    public function setProxyDir($dir)
    {
        $this->_attributes['proxyDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     *
     * @return string|null
     */
    public function getProxyDir()
    {
        return $this->_attributes['proxyDir'] ?? null;
    }

    /**
     * Gets the strategy for automatically generating proxy classes.
     *
     * @return int Possible values are constants of Doctrine\Common\Proxy\AbstractProxyFactory.
     * @psalm-return AutogenerateMode
     */
    public function getAutoGenerateProxyClasses()
    {
        return $this->_attributes['autoGenerateProxyClasses'] ?? AbstractProxyFactory::AUTOGENERATE_ALWAYS;
    }

    /**
     * Sets the strategy for automatically generating proxy classes.
     *
     * @param bool|int $autoGenerate Possible values are constants of Doctrine\Common\Proxy\AbstractProxyFactory.
     * True is converted to AUTOGENERATE_ALWAYS, false to AUTOGENERATE_NEVER.
     *
     * @return void
     */
    public function setAutoGenerateProxyClasses($autoGenerate)
    {
        $this->_attributes['autoGenerateProxyClasses'] = (int) $autoGenerate;
    }

    /**
     * Gets the namespace where proxy classes reside.
     *
     * @return string|null
     */
    public function getProxyNamespace()
    {
        return $this->_attributes['proxyNamespace'] ?? null;
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
        $this->_attributes['proxyNamespace'] = $ns;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @return void
     *
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $driverImpl)
    {
        $this->_attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Sets the entity alias map.
     *
     * @psalm-param array<string, string> $entityNamespaces
     *
     * @return void
     */
    public function setEntityNamespaces(array $entityNamespaces)
    {
        $this->_attributes['entityNamespaces'] = $entityNamespaces;
    }

    /**
     * Retrieves the list of registered entity namespace aliases.
     *
     * @psalm-return array<string, string>
     */
    public function getEntityNamespaces()
    {
        return $this->_attributes['entityNamespaces'];
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     *
     * @return MappingDriver|null
     */
    public function getMetadataDriverImpl()
    {
        return $this->_attributes['metadataDriverImpl'] ?? null;
    }

    /**
     * Gets the cache driver implementation that is used for the query cache (SQL cache).
     */
    public function getQueryCache(): ?CacheItemPoolInterface
    {
        return $this->_attributes['queryCache'] ?? null;
    }

    /**
     * Sets the cache driver implementation that is used for the query cache (SQL cache).
     */
    public function setQueryCache(CacheItemPoolInterface $cache): void
    {
        $this->_attributes['queryCache'] = $cache;
    }

    public function getHydrationCache(): ?CacheItemPoolInterface
    {
        return $this->_attributes['hydrationCache'] ?? null;
    }

    public function setHydrationCache(CacheItemPoolInterface $cache): void
    {
        $this->_attributes['hydrationCache'] = $cache;
    }

    public function getMetadataCache(): ?CacheItemPoolInterface
    {
        return $this->_attributes['metadataCache'] ?? null;
    }

    public function setMetadataCache(CacheItemPoolInterface $cache): void
    {
        $this->_attributes['metadataCache'] = $cache;
    }

    /**
     * Registers a custom DQL function that produces a string value.
     * Such a function can then be used in any DQL statement in any place where string
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string                $name      Function name.
     * @param class-string|callable $className Class name or a callable that returns the function.
     * @psalm-param class-string<FunctionNode>|callable(string):FunctionNode $className
     *
     * @return void
     */
    public function addCustomStringFunction($name, $className)
    {
        $this->_attributes['customStringFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom string DQL function.
     *
     * @param string $name
     *
     * @return string|null
     * @psalm-return class-string<FunctionNode>|callable(string):FunctionNode|null
     */
    public function getCustomStringFunction($name)
    {
        $name = strtolower($name);

        return $this->_attributes['customStringFunctions'][$name] ?? null;
    }

    /**
     * Sets a map of custom DQL string functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added string functions are discarded.
     *
     * @psalm-param array<string, class-string<FunctionNode>|callable(string):FunctionNode> $functions The map of custom
     *                                                     DQL string functions.
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
        $this->_attributes['customNumericFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom numeric DQL function.
     *
     * @param string $name
     *
     * @return string|null
     * @psalm-return ?class-string
     */
    public function getCustomNumericFunction($name)
    {
        $name = strtolower($name);

        return $this->_attributes['customNumericFunctions'][$name] ?? null;
    }

    /**
     * Sets a map of custom DQL numeric functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added numeric functions are discarded.
     *
     * @psalm-param array<string, class-string> $functions The map of custom
     *                                                     DQL numeric functions.
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
     * @psalm-param class-string|callable $className
     *
     * @return void
     */
    public function addCustomDatetimeFunction($name, $className)
    {
        $this->_attributes['customDatetimeFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom date/time DQL function.
     *
     * @param string $name
     *
     * @return string|null
     * @psalm-return ?class-string $name
     */
    public function getCustomDatetimeFunction($name)
    {
        $name = strtolower($name);

        return $this->_attributes['customDatetimeFunctions'][$name] ?? null;
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
     * @psalm-param array<string, string> $functions
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
     * @param array<string, class-string<AbstractHydrator>> $modes An array of ($modeName => $hydrator).
     *
     * @return void
     */
    public function setCustomHydrationModes($modes)
    {
        $this->_attributes['customHydrationModes'] = [];

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
     * @psalm-return class-string<AbstractHydrator>|null
     */
    public function getCustomHydrationMode($modeName)
    {
        return $this->_attributes['customHydrationModes'][$modeName] ?? null;
    }

    /**
     * Adds a custom hydration mode.
     *
     * @param string $modeName The hydration mode name.
     * @param string $hydrator The hydrator class name.
     * @psalm-param class-string<AbstractHydrator> $hydrator
     *
     * @return void
     */
    public function addCustomHydrationMode($modeName, $hydrator)
    {
        $this->_attributes['customHydrationModes'][$modeName] = $hydrator;
    }

    /**
     * Sets a class metadata factory.
     *
     * @param string $cmfName
     * @psalm-param class-string $cmfName
     *
     * @return void
     */
    public function setClassMetadataFactoryName($cmfName)
    {
        $this->_attributes['classMetadataFactoryName'] = $cmfName;
    }

    /**
     * @return string
     * @psalm-return class-string
     */
    public function getClassMetadataFactoryName()
    {
        if (! isset($this->_attributes['classMetadataFactoryName'])) {
            $this->_attributes['classMetadataFactoryName'] = ClassMetadataFactory::class;
        }

        return $this->_attributes['classMetadataFactoryName'];
    }

    /**
     * Adds a filter to the list of possible filters.
     *
     * @param string $name      The name of the filter.
     * @param string $className The class name of the filter.
     * @psalm-param class-string<SQLFilter> $className
     *
     * @return void
     */
    public function addFilter($name, $className)
    {
        $this->_attributes['filters'][$name] = $className;
    }

    /**
     * Gets the class name for a given filter name.
     *
     * @param string $name The name of the filter.
     *
     * @return string|null The class name of the filter, or null if it is not
     *  defined.
     * @psalm-return class-string<SQLFilter>|null
     */
    public function getFilterClassName($name)
    {
        return $this->_attributes['filters'][$name] ?? null;
    }

    /**
     * Sets default repository class.
     *
     * @param string $className
     * @psalm-param class-string<EntityRepository> $className
     *
     * @return void
     *
     * @throws InvalidEntityRepository If $classname is not an ObjectRepository.
     */
    public function setDefaultRepositoryClassName($className)
    {
        if (! class_exists($className) || ! is_a($className, EntityRepository::class, true)) {
            throw InvalidEntityRepository::fromClassName($className);
        }

        $this->_attributes['defaultRepositoryClassName'] = $className;
    }

    /**
     * Get default repository class.
     *
     * @return string
     * @psalm-return class-string<EntityRepository>
     */
    public function getDefaultRepositoryClassName()
    {
        return $this->_attributes['defaultRepositoryClassName'] ?? EntityRepository::class;
    }

    /**
     * Sets naming strategy.
     *
     * @return void
     */
    public function setNamingStrategy(NamingStrategy $namingStrategy)
    {
        $this->_attributes['namingStrategy'] = $namingStrategy;
    }

    /**
     * Gets naming strategy..
     *
     * @return NamingStrategy
     */
    public function getNamingStrategy()
    {
        if (! isset($this->_attributes['namingStrategy'])) {
            $this->_attributes['namingStrategy'] = new DefaultNamingStrategy();
        }

        return $this->_attributes['namingStrategy'];
    }

    /**
     * Sets quote strategy.
     *
     * @return void
     */
    public function setQuoteStrategy(QuoteStrategy $quoteStrategy)
    {
        $this->_attributes['quoteStrategy'] = $quoteStrategy;
    }

    /**
     * Gets quote strategy.
     *
     * @return QuoteStrategy
     */
    public function getQuoteStrategy()
    {
        if (! isset($this->_attributes['quoteStrategy'])) {
            $this->_attributes['quoteStrategy'] = new DefaultQuoteStrategy();
        }

        return $this->_attributes['quoteStrategy'];
    }

    /**
     * Set the entity listener resolver.
     *
     * @return void
     */
    public function setEntityListenerResolver(EntityListenerResolver $resolver)
    {
        $this->_attributes['entityListenerResolver'] = $resolver;
    }

    /**
     * Get the entity listener resolver.
     *
     * @return EntityListenerResolver
     */
    public function getEntityListenerResolver()
    {
        if (! isset($this->_attributes['entityListenerResolver'])) {
            $this->_attributes['entityListenerResolver'] = new DefaultEntityListenerResolver();
        }

        return $this->_attributes['entityListenerResolver'];
    }

    /**
     * Set the entity repository factory.
     *
     * @return void
     */
    public function setRepositoryFactory(RepositoryFactory $repositoryFactory)
    {
        $this->_attributes['repositoryFactory'] = $repositoryFactory;
    }

    /**
     * Get the entity repository factory.
     *
     * @return RepositoryFactory
     */
    public function getRepositoryFactory()
    {
        return $this->_attributes['repositoryFactory'] ?? new DefaultRepositoryFactory();
    }

    /**
     * @return bool
     */
    public function isSecondLevelCacheEnabled()
    {
        return $this->_attributes['isSecondLevelCacheEnabled'] ?? false;
    }

    /**
     * @param bool $flag
     *
     * @return void
     */
    public function setSecondLevelCacheEnabled($flag = true)
    {
        $this->_attributes['isSecondLevelCacheEnabled'] = (bool) $flag;
    }

    /**
     * @return void
     */
    public function setSecondLevelCacheConfiguration(CacheConfiguration $cacheConfig)
    {
        $this->_attributes['secondLevelCacheConfiguration'] = $cacheConfig;
    }

    /**
     * @return CacheConfiguration|null
     */
    public function getSecondLevelCacheConfiguration()
    {
        if (! isset($this->_attributes['secondLevelCacheConfiguration']) && $this->isSecondLevelCacheEnabled()) {
            $this->_attributes['secondLevelCacheConfiguration'] = new CacheConfiguration();
        }

        return $this->_attributes['secondLevelCacheConfiguration'] ?? null;
    }

    /**
     * Returns query hints, which will be applied to every query in application
     *
     * @psalm-return array<string, mixed>
     */
    public function getDefaultQueryHints()
    {
        return $this->_attributes['defaultQueryHints'] ?? [];
    }

    /**
     * Sets array of query hints, which will be applied to every query in application
     *
     * @psalm-param array<string, mixed> $defaultQueryHints
     *
     * @return void
     */
    public function setDefaultQueryHints(array $defaultQueryHints)
    {
        $this->_attributes['defaultQueryHints'] = $defaultQueryHints;
    }

    /**
     * Gets the value of a default query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @param string $name The name of the hint.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getDefaultQueryHint($name)
    {
        return $this->_attributes['defaultQueryHints'][$name] ?? false;
    }

    /**
     * Sets a default query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name  The name of the hint.
     * @param mixed  $value The value of the hint.
     *
     * @return void
     */
    public function setDefaultQueryHint($name, $value)
    {
        $this->_attributes['defaultQueryHints'][$name] = $value;
    }

    /**
     * Gets a list of entity class names to be ignored by the SchemaTool
     *
     * @return list<class-string>
     */
    public function getSchemaIgnoreClasses(): array
    {
        return $this->_attributes['schemaIgnoreClasses'] ?? [];
    }

    /**
     * Sets a list of entity class names to be ignored by the SchemaTool
     *
     * @param list<class-string> $schemaIgnoreClasses List of entity class names
     */
    public function setSchemaIgnoreClasses(array $schemaIgnoreClasses): void
    {
        $this->_attributes['schemaIgnoreClasses'] = $schemaIgnoreClasses;
    }
}
