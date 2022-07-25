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
    protected array $_attributes = [];

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     */
    public function setProxyDir(string $dir): void
    {
        $this->_attributes['proxyDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     */
    public function getProxyDir(): string|null
    {
        return $this->_attributes['proxyDir'] ?? null;
    }

    /**
     * Gets the strategy for automatically generating proxy classes.
     *
     * @return int Possible values are constants of Doctrine\Common\Proxy\AbstractProxyFactory.
     * @psalm-return AutogenerateMode
     */
    public function getAutoGenerateProxyClasses(): int
    {
        return $this->_attributes['autoGenerateProxyClasses'] ?? AbstractProxyFactory::AUTOGENERATE_ALWAYS;
    }

    /**
     * Sets the strategy for automatically generating proxy classes.
     *
     * @param bool|int $autoGenerate Possible values are constants of Doctrine\Common\Proxy\AbstractProxyFactory.
     * @psalm-param bool|AutogenerateMode $autoGenerate
     * True is converted to AUTOGENERATE_ALWAYS, false to AUTOGENERATE_NEVER.
     */
    public function setAutoGenerateProxyClasses(bool|int $autoGenerate): void
    {
        $this->_attributes['autoGenerateProxyClasses'] = (int) $autoGenerate;
    }

    /**
     * Gets the namespace where proxy classes reside.
     */
    public function getProxyNamespace(): string|null
    {
        return $this->_attributes['proxyNamespace'] ?? null;
    }

    /**
     * Sets the namespace where proxy classes reside.
     */
    public function setProxyNamespace(string $ns): void
    {
        $this->_attributes['proxyNamespace'] = $ns;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $driverImpl): void
    {
        $this->_attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Sets the entity alias map.
     *
     * @psalm-param array<string, string> $entityNamespaces
     */
    public function setEntityNamespaces(array $entityNamespaces): void
    {
        $this->_attributes['entityNamespaces'] = $entityNamespaces;
    }

    /**
     * Retrieves the list of registered entity namespace aliases.
     *
     * @psalm-return array<string, string>
     */
    public function getEntityNamespaces(): array
    {
        return $this->_attributes['entityNamespaces'];
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     */
    public function getMetadataDriverImpl(): MappingDriver|null
    {
        return $this->_attributes['metadataDriverImpl'] ?? null;
    }

    /**
     * Gets the cache driver implementation that is used for the query cache (SQL cache).
     */
    public function getQueryCache(): CacheItemPoolInterface|null
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

    public function getHydrationCache(): CacheItemPoolInterface|null
    {
        return $this->_attributes['hydrationCache'] ?? null;
    }

    public function setHydrationCache(CacheItemPoolInterface $cache): void
    {
        $this->_attributes['hydrationCache'] = $cache;
    }

    public function getMetadataCache(): CacheItemPoolInterface|null
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
     * @param class-string|callable $className Class name or a callable that returns the function.
     * @psalm-param class-string<FunctionNode>|callable(string):FunctionNode $className
     */
    public function addCustomStringFunction(string $name, string|callable $className): void
    {
        $this->_attributes['customStringFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom string DQL function.
     *
     * @psalm-return class-string<FunctionNode>|callable(string):FunctionNode|null
     */
    public function getCustomStringFunction(string $name): string|callable|null
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
     */
    public function setCustomStringFunctions(array $functions): void
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
     * @param class-string|callable $className Class name or a callable that returns the function.
     * @psalm-param class-string<FunctionNode>|callable(string):FunctionNode $className
     */
    public function addCustomNumericFunction(string $name, string|callable $className): void
    {
        $this->_attributes['customNumericFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom numeric DQL function.
     *
     * @psalm-return ?class-string<FunctionNode>|callable(string):FunctionNode
     */
    public function getCustomNumericFunction(string $name): string|callable|null
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
     */
    public function setCustomNumericFunctions(array $functions): void
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
     * @param string|callable $className Class name or a callable that returns the function.
     * @psalm-param class-string<FunctionNode>|callable(string):FunctionNode $className
     */
    public function addCustomDatetimeFunction(string $name, string|callable $className): void
    {
        $this->_attributes['customDatetimeFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom date/time DQL function.
     *
     * @psalm-return class-string|callable|null $name
     */
    public function getCustomDatetimeFunction(string $name): string|callable|null
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
     * @psalm-param array<string, class-string<FunctionNode>|callable(string):FunctionNode> $functions
     */
    public function setCustomDatetimeFunctions(array $functions): void
    {
        foreach ($functions as $name => $className) {
            $this->addCustomDatetimeFunction($name, $className);
        }
    }

    /**
     * Sets the custom hydrator modes in one pass.
     *
     * @param array<string, class-string<AbstractHydrator>> $modes An array of ($modeName => $hydrator).
     */
    public function setCustomHydrationModes(array $modes): void
    {
        $this->_attributes['customHydrationModes'] = [];

        foreach ($modes as $modeName => $hydrator) {
            $this->addCustomHydrationMode($modeName, $hydrator);
        }
    }

    /**
     * Gets the hydrator class for the given hydration mode name.
     *
     * @psalm-return class-string<AbstractHydrator>|null
     */
    public function getCustomHydrationMode(string $modeName): string|null
    {
        return $this->_attributes['customHydrationModes'][$modeName] ?? null;
    }

    /**
     * Adds a custom hydration mode.
     *
     * @psalm-param class-string<AbstractHydrator> $hydrator
     */
    public function addCustomHydrationMode(string $modeName, string $hydrator): void
    {
        $this->_attributes['customHydrationModes'][$modeName] = $hydrator;
    }

    /**
     * Sets a class metadata factory.
     *
     * @psalm-param class-string $cmfName
     */
    public function setClassMetadataFactoryName(string $cmfName): void
    {
        $this->_attributes['classMetadataFactoryName'] = $cmfName;
    }

    /** @psalm-return class-string */
    public function getClassMetadataFactoryName(): string
    {
        if (! isset($this->_attributes['classMetadataFactoryName'])) {
            $this->_attributes['classMetadataFactoryName'] = ClassMetadataFactory::class;
        }

        return $this->_attributes['classMetadataFactoryName'];
    }

    /**
     * Adds a filter to the list of possible filters.
     *
     * @param string $className The class name of the filter.
     * @psalm-param class-string<SQLFilter> $className
     *
     * @return void
     */
    public function addFilter(string $name, string $className)
    {
        $this->_attributes['filters'][$name] = $className;
    }

    /**
     * Gets the class name for a given filter name.
     *
     * @return string|null The class name of the filter, or null if it is not
     *  defined.
     * @psalm-return class-string<SQLFilter>|null
     */
    public function getFilterClassName(string $name): string|null
    {
        return $this->_attributes['filters'][$name] ?? null;
    }

    /**
     * Sets default repository class.
     *
     * @psalm-param class-string<EntityRepository> $className
     *
     * @throws InvalidEntityRepository If $classname is not an ObjectRepository.
     */
    public function setDefaultRepositoryClassName(string $className): void
    {
        if (! class_exists($className) || ! is_a($className, EntityRepository::class, true)) {
            throw InvalidEntityRepository::fromClassName($className);
        }

        $this->_attributes['defaultRepositoryClassName'] = $className;
    }

    /**
     * Get default repository class.
     *
     * @psalm-return class-string<EntityRepository>
     */
    public function getDefaultRepositoryClassName(): string
    {
        return $this->_attributes['defaultRepositoryClassName'] ?? EntityRepository::class;
    }

    /**
     * Sets naming strategy.
     */
    public function setNamingStrategy(NamingStrategy $namingStrategy): void
    {
        $this->_attributes['namingStrategy'] = $namingStrategy;
    }

    /**
     * Gets naming strategy..
     */
    public function getNamingStrategy(): NamingStrategy
    {
        if (! isset($this->_attributes['namingStrategy'])) {
            $this->_attributes['namingStrategy'] = new DefaultNamingStrategy();
        }

        return $this->_attributes['namingStrategy'];
    }

    /**
     * Sets quote strategy.
     */
    public function setQuoteStrategy(QuoteStrategy $quoteStrategy): void
    {
        $this->_attributes['quoteStrategy'] = $quoteStrategy;
    }

    /**
     * Gets quote strategy.
     */
    public function getQuoteStrategy(): QuoteStrategy
    {
        if (! isset($this->_attributes['quoteStrategy'])) {
            $this->_attributes['quoteStrategy'] = new DefaultQuoteStrategy();
        }

        return $this->_attributes['quoteStrategy'];
    }

    /**
     * Set the entity listener resolver.
     */
    public function setEntityListenerResolver(EntityListenerResolver $resolver): void
    {
        $this->_attributes['entityListenerResolver'] = $resolver;
    }

    /**
     * Get the entity listener resolver.
     */
    public function getEntityListenerResolver(): EntityListenerResolver
    {
        if (! isset($this->_attributes['entityListenerResolver'])) {
            $this->_attributes['entityListenerResolver'] = new DefaultEntityListenerResolver();
        }

        return $this->_attributes['entityListenerResolver'];
    }

    /**
     * Set the entity repository factory.
     */
    public function setRepositoryFactory(RepositoryFactory $repositoryFactory): void
    {
        $this->_attributes['repositoryFactory'] = $repositoryFactory;
    }

    /**
     * Get the entity repository factory.
     */
    public function getRepositoryFactory(): RepositoryFactory
    {
        return $this->_attributes['repositoryFactory'] ?? new DefaultRepositoryFactory();
    }

    public function isSecondLevelCacheEnabled(): bool
    {
        return $this->_attributes['isSecondLevelCacheEnabled'] ?? false;
    }

    public function setSecondLevelCacheEnabled(bool $flag = true): void
    {
        $this->_attributes['isSecondLevelCacheEnabled'] = $flag;
    }

    public function setSecondLevelCacheConfiguration(CacheConfiguration $cacheConfig): void
    {
        $this->_attributes['secondLevelCacheConfiguration'] = $cacheConfig;
    }

    public function getSecondLevelCacheConfiguration(): CacheConfiguration|null
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
    public function getDefaultQueryHints(): array
    {
        return $this->_attributes['defaultQueryHints'] ?? [];
    }

    /**
     * Sets array of query hints, which will be applied to every query in application
     *
     * @psalm-param array<string, mixed> $defaultQueryHints
     */
    public function setDefaultQueryHints(array $defaultQueryHints): void
    {
        $this->_attributes['defaultQueryHints'] = $defaultQueryHints;
    }

    /**
     * Gets the value of a default query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getDefaultQueryHint(string $name): mixed
    {
        return $this->_attributes['defaultQueryHints'][$name] ?? false;
    }

    /**
     * Sets a default query hint. If the hint name is not recognized, it is silently ignored.
     */
    public function setDefaultQueryHint(string $name, mixed $value): void
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
