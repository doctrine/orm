<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use BackedEnum;
use Countable;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Result;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Cache\Exception\InvalidResultCacheDriver;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\ORM\Cache\TimestampCacheKey;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\Mapping\MappingException;
use LogicException;
use Psr\Cache\CacheItemPoolInterface;
use Traversable;

use function array_map;
use function array_shift;
use function assert;
use function count;
use function func_num_args;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;
use function is_scalar;
use function is_string;
use function iterator_count;
use function iterator_to_array;
use function ksort;
use function method_exists;
use function reset;
use function serialize;
use function sha1;

/**
 * Base contract for ORM queries. Base class for Query and NativeQuery.
 *
 * @link    www.doctrine-project.org
 */
abstract class AbstractQuery
{
    /* Hydration mode constants */

    /**
     * Hydrates an object graph. This is the default behavior.
     */
    public const HYDRATE_OBJECT = 1;

    /**
     * Hydrates an array graph.
     */
    public const HYDRATE_ARRAY = 2;

    /**
     * Hydrates a flat, rectangular result set with scalar values.
     */
    public const HYDRATE_SCALAR = 3;

    /**
     * Hydrates a single scalar value.
     */
    public const HYDRATE_SINGLE_SCALAR = 4;

    /**
     * Very simple object hydrator (optimized for performance).
     */
    public const HYDRATE_SIMPLEOBJECT = 5;

    /**
     * Hydrates scalar column value.
     */
    public const HYDRATE_SCALAR_COLUMN = 6;

    /**
     * The parameter map of this query.
     *
     * @var ArrayCollection|Parameter[]
     * @psalm-var ArrayCollection<int, Parameter>
     */
    protected $parameters;

    /**
     * The user-specified ResultSetMapping to use.
     *
     * @var ResultSetMapping|null
     */
    protected $_resultSetMapping;

    /**
     * The entity manager used by this query object.
     *
     * @var EntityManagerInterface
     */
    protected $_em;

    /**
     * The map of query hints.
     *
     * @psalm-var array<string, mixed>
     */
    protected $_hints = [];

    /**
     * The hydration mode.
     *
     * @var string|int
     * @psalm-var string|AbstractQuery::HYDRATE_*
     */
    protected $_hydrationMode = self::HYDRATE_OBJECT;

    /** @var QueryCacheProfile|null */
    protected $_queryCacheProfile;

    /**
     * Whether or not expire the result cache.
     *
     * @var bool
     */
    protected $_expireResultCache = false;

    /** @var QueryCacheProfile|null */
    protected $_hydrationCacheProfile;

    /**
     * Whether to use second level cache, if available.
     *
     * @var bool
     */
    protected $cacheable = false;

    /** @var bool */
    protected $hasCache = false;

    /**
     * Second level cache region name.
     *
     * @var string|null
     */
    protected $cacheRegion;

    /**
     * Second level query cache mode.
     *
     * @var int|null
     * @psalm-var Cache::MODE_*|null
     */
    protected $cacheMode;

    /** @var CacheLogger|null */
    protected $cacheLogger;

    /** @var int */
    protected $lifetime = 0;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractQuery</tt>.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->_em        = $em;
        $this->parameters = new ArrayCollection();
        $this->_hints     = $em->getConfiguration()->getDefaultQueryHints();
        $this->hasCache   = $this->_em->getConfiguration()->isSecondLevelCacheEnabled();

        if ($this->hasCache) {
            $this->cacheLogger = $em->getConfiguration()
                ->getSecondLevelCacheConfiguration()
                ->getCacheLogger();
        }
    }

    /**
     * Enable/disable second level query (result) caching for this query.
     *
     * @param bool $cacheable
     *
     * @return $this
     */
    public function setCacheable($cacheable)
    {
        $this->cacheable = (bool) $cacheable;

        return $this;
    }

    /** @return bool TRUE if the query results are enabled for second level cache, FALSE otherwise. */
    public function isCacheable()
    {
        return $this->cacheable;
    }

    /**
     * @param string $cacheRegion
     *
     * @return $this
     */
    public function setCacheRegion($cacheRegion)
    {
        $this->cacheRegion = (string) $cacheRegion;

        return $this;
    }

    /**
     * Obtain the name of the second level query cache region in which query results will be stored
     *
     * @return string|null The cache region name; NULL indicates the default region.
     */
    public function getCacheRegion()
    {
        return $this->cacheRegion;
    }

    /** @return bool TRUE if the query cache and second level cache are enabled, FALSE otherwise. */
    protected function isCacheEnabled()
    {
        return $this->cacheable && $this->hasCache;
    }

    /** @return int */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * Sets the life-time for this query into second level cache.
     *
     * @param int $lifetime
     *
     * @return $this
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = (int) $lifetime;

        return $this;
    }

    /**
     * @return int|null
     * @psalm-return Cache::MODE_*|null
     */
    public function getCacheMode()
    {
        return $this->cacheMode;
    }

    /**
     * @param int $cacheMode
     * @psalm-param Cache::MODE_* $cacheMode
     *
     * @return $this
     */
    public function setCacheMode($cacheMode)
    {
        $this->cacheMode = (int) $cacheMode;

        return $this;
    }

    /**
     * Gets the SQL query that corresponds to this query object.
     * The returned SQL syntax depends on the connection driver that is used
     * by this query object at the time of this method call.
     *
     * @return list<string>|string SQL query
     */
    abstract public function getSQL();

    /**
     * Retrieves the associated EntityManager of this Query instance.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * Frees the resources used by the query object.
     *
     * Resets Parameters, Parameter Types and Query Hints.
     *
     * @return void
     */
    public function free()
    {
        $this->parameters = new ArrayCollection();

        $this->_hints = $this->_em->getConfiguration()->getDefaultQueryHints();
    }

    /**
     * Get all defined parameters.
     *
     * @return ArrayCollection The defined query parameters.
     * @psalm-return ArrayCollection<int, Parameter>
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Gets a query parameter.
     *
     * @param int|string $key The key (index or name) of the bound parameter.
     *
     * @return Parameter|null The value of the bound parameter, or NULL if not available.
     */
    public function getParameter($key)
    {
        $key = Query\Parameter::normalizeName($key);

        $filteredParameters = $this->parameters->filter(
            static function (Query\Parameter $parameter) use ($key): bool {
                $parameterName = $parameter->getName();

                return $key === $parameterName;
            }
        );

        return ! $filteredParameters->isEmpty() ? $filteredParameters->first() : null;
    }

    /**
     * Sets a collection of query parameters.
     *
     * @param ArrayCollection|mixed[] $parameters
     * @psalm-param ArrayCollection<int, Parameter>|mixed[] $parameters
     *
     * @return $this
     */
    public function setParameters($parameters)
    {
        if (is_array($parameters)) {
            /** @psalm-var ArrayCollection<int, Parameter> $parameterCollection */
            $parameterCollection = new ArrayCollection();

            foreach ($parameters as $key => $value) {
                $parameterCollection->add(new Parameter($key, $value));
            }

            $parameters = $parameterCollection;
        }

        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Sets a query parameter.
     *
     * @param string|int      $key   The parameter position or name.
     * @param mixed           $value The parameter value.
     * @param string|int|null $type  The parameter type. If specified, the given value will be run through
     *                               the type conversion of this type. This is usually not needed for
     *                               strings and numeric types.
     *
     * @return $this
     */
    public function setParameter($key, $value, $type = null)
    {
        $existingParameter = $this->getParameter($key);

        if ($existingParameter !== null) {
            $existingParameter->setValue($value, $type);

            return $this;
        }

        $this->parameters->add(new Parameter($key, $value, $type));

        return $this;
    }

    /**
     * Processes an individual parameter value.
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @throws ORMInvalidArgumentException
     */
    public function processParameterValue($value)
    {
        if (is_scalar($value)) {
            return $value;
        }

        if ($value instanceof Collection) {
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            $value = $this->processArrayParameterValue($value);

            return $value;
        }

        if ($value instanceof Mapping\ClassMetadata) {
            return $value->name;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (! is_object($value)) {
            return $value;
        }

        try {
            $class = ClassUtils::getClass($value);
            $value = $this->_em->getUnitOfWork()->getSingleIdentifierValue($value);

            if ($value === null) {
                throw ORMInvalidArgumentException::invalidIdentifierBindingEntity($class);
            }
        } catch (MappingException | ORMMappingException $e) {
            /* Silence any mapping exceptions. These can occur if the object in
               question is not a mapped entity, in which case we just don't do
               any preparation on the value.
               Depending on MappingDriver, either MappingException or
               ORMMappingException is thrown. */

            $value = $this->potentiallyProcessIterable($value);
        }

        return $value;
    }

    /**
     * If no mapping is detected, trying to resolve the value as a Traversable
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function potentiallyProcessIterable($value)
    {
        if ($value instanceof Traversable) {
            $value = iterator_to_array($value);
            $value = $this->processArrayParameterValue($value);
        }

        return $value;
    }

    /**
     * Process a parameter value which was previously identified as an array
     *
     * @param mixed[] $value
     *
     * @return mixed[]
     */
    private function processArrayParameterValue(array $value): array
    {
        foreach ($value as $key => $paramValue) {
            $paramValue  = $this->processParameterValue($paramValue);
            $value[$key] = is_array($paramValue) ? reset($paramValue) : $paramValue;
        }

        return $value;
    }

    /**
     * Sets the ResultSetMapping that should be used for hydration.
     *
     * @return $this
     */
    public function setResultSetMapping(Query\ResultSetMapping $rsm)
    {
        $this->translateNamespaces($rsm);
        $this->_resultSetMapping = $rsm;

        return $this;
    }

    /**
     * Gets the ResultSetMapping used for hydration.
     *
     * @return ResultSetMapping|null
     */
    protected function getResultSetMapping()
    {
        return $this->_resultSetMapping;
    }

    /**
     * Allows to translate entity namespaces to full qualified names.
     */
    private function translateNamespaces(Query\ResultSetMapping $rsm): void
    {
        $translate = function ($alias): string {
            return $this->_em->getClassMetadata($alias)->getName();
        };

        $rsm->aliasMap         = array_map($translate, $rsm->aliasMap);
        $rsm->declaringClasses = array_map($translate, $rsm->declaringClasses);
    }

    /**
     * Set a cache profile for hydration caching.
     *
     * If no result cache driver is set in the QueryCacheProfile, the default
     * result cache driver is used from the configuration.
     *
     * Important: Hydration caching does NOT register entities in the
     * UnitOfWork when retrieved from the cache. Never use result cached
     * entities for requests that also flush the EntityManager. If you want
     * some form of caching with UnitOfWork registration you should use
     * {@see AbstractQuery::setResultCacheProfile()}.
     *
     * @return $this
     *
     * @example
     * $lifetime = 100;
     * $resultKey = "abc";
     * $query->setHydrationCacheProfile(new QueryCacheProfile());
     * $query->setHydrationCacheProfile(new QueryCacheProfile($lifetime, $resultKey));
     */
    public function setHydrationCacheProfile(?QueryCacheProfile $profile = null)
    {
        if ($profile === null) {
            if (func_num_args() < 1) {
                Deprecation::trigger(
                    'doctrine/orm',
                    'https://github.com/doctrine/orm/pull/9791',
                    'Calling %s without arguments is deprecated, pass null instead.',
                    __METHOD__
                );
            }

            $this->_hydrationCacheProfile = null;

            return $this;
        }

        // DBAL 2
        if (! method_exists(QueryCacheProfile::class, 'setResultCache')) {
            if (! $profile->getResultCacheDriver()) {
                $defaultHydrationCacheImpl = $this->_em->getConfiguration()->getHydrationCache();
                if ($defaultHydrationCacheImpl) {
                    $profile = $profile->setResultCacheDriver(DoctrineProvider::wrap($defaultHydrationCacheImpl));
                }
            }
        } elseif (! $profile->getResultCache()) {
            $defaultHydrationCacheImpl = $this->_em->getConfiguration()->getHydrationCache();
            if ($defaultHydrationCacheImpl) {
                $profile = $profile->setResultCache($defaultHydrationCacheImpl);
            }
        }

        $this->_hydrationCacheProfile = $profile;

        return $this;
    }

    /** @return QueryCacheProfile|null */
    public function getHydrationCacheProfile()
    {
        return $this->_hydrationCacheProfile;
    }

    /**
     * Set a cache profile for the result cache.
     *
     * If no result cache driver is set in the QueryCacheProfile, the default
     * result cache driver is used from the configuration.
     *
     * @return $this
     */
    public function setResultCacheProfile(?QueryCacheProfile $profile = null)
    {
        if ($profile === null) {
            if (func_num_args() < 1) {
                Deprecation::trigger(
                    'doctrine/orm',
                    'https://github.com/doctrine/orm/pull/9791',
                    'Calling %s without arguments is deprecated, pass null instead.',
                    __METHOD__
                );
            }

            $this->_queryCacheProfile = null;

            return $this;
        }

        // DBAL 2
        if (! method_exists(QueryCacheProfile::class, 'setResultCache')) {
            if (! $profile->getResultCacheDriver()) {
                $defaultResultCacheDriver = $this->_em->getConfiguration()->getResultCache();
                if ($defaultResultCacheDriver) {
                    $profile = $profile->setResultCacheDriver(DoctrineProvider::wrap($defaultResultCacheDriver));
                }
            }
        } elseif (! $profile->getResultCache()) {
            $defaultResultCache = $this->_em->getConfiguration()->getResultCache();
            if ($defaultResultCache) {
                $profile = $profile->setResultCache($defaultResultCache);
            }
        }

        $this->_queryCacheProfile = $profile;

        return $this;
    }

    /**
     * Defines a cache driver to be used for caching result sets and implicitly enables caching.
     *
     * @deprecated Use {@see setResultCache()} instead.
     *
     * @param \Doctrine\Common\Cache\Cache|null $resultCacheDriver Cache driver
     *
     * @return $this
     *
     * @throws InvalidResultCacheDriver
     */
    public function setResultCacheDriver($resultCacheDriver = null)
    {
        /** @phpstan-ignore-next-line */
        if ($resultCacheDriver !== null && ! ($resultCacheDriver instanceof \Doctrine\Common\Cache\Cache)) {
            throw InvalidResultCacheDriver::create();
        }

        return $this->setResultCache($resultCacheDriver ? CacheAdapter::wrap($resultCacheDriver) : null);
    }

    /**
     * Defines a cache driver to be used for caching result sets and implicitly enables caching.
     *
     * @return $this
     */
    public function setResultCache(?CacheItemPoolInterface $resultCache = null)
    {
        if ($resultCache === null) {
            if (func_num_args() < 1) {
                Deprecation::trigger(
                    'doctrine/orm',
                    'https://github.com/doctrine/orm/pull/9791',
                    'Calling %s without arguments is deprecated, pass null instead.',
                    __METHOD__
                );
            }

            if ($this->_queryCacheProfile) {
                $this->_queryCacheProfile = new QueryCacheProfile($this->_queryCacheProfile->getLifetime(), $this->_queryCacheProfile->getCacheKey());
            }

            return $this;
        }

        // DBAL 2
        if (! method_exists(QueryCacheProfile::class, 'setResultCache')) {
            $resultCacheDriver = DoctrineProvider::wrap($resultCache);

            $this->_queryCacheProfile = $this->_queryCacheProfile
                ? $this->_queryCacheProfile->setResultCacheDriver($resultCacheDriver)
                : new QueryCacheProfile(0, null, $resultCacheDriver);

            return $this;
        }

        $this->_queryCacheProfile = $this->_queryCacheProfile
            ? $this->_queryCacheProfile->setResultCache($resultCache)
            : new QueryCacheProfile(0, null, $resultCache);

        return $this;
    }

    /**
     * Returns the cache driver used for caching result sets.
     *
     * @deprecated
     *
     * @return \Doctrine\Common\Cache\Cache Cache driver
     */
    public function getResultCacheDriver()
    {
        if ($this->_queryCacheProfile && $this->_queryCacheProfile->getResultCacheDriver()) {
            return $this->_queryCacheProfile->getResultCacheDriver();
        }

        return $this->_em->getConfiguration()->getResultCacheImpl();
    }

    /**
     * Set whether or not to cache the results of this query and if so, for
     * how long and which ID to use for the cache entry.
     *
     * @deprecated 2.7 Use {@see enableResultCache} and {@see disableResultCache} instead.
     *
     * @param bool   $useCache      Whether or not to cache the results of this query.
     * @param int    $lifetime      How long the cache entry is valid, in seconds.
     * @param string $resultCacheId ID to use for the cache entry.
     *
     * @return $this
     */
    public function useResultCache($useCache, $lifetime = null, $resultCacheId = null)
    {
        return $useCache
            ? $this->enableResultCache($lifetime, $resultCacheId)
            : $this->disableResultCache();
    }

    /**
     * Enables caching of the results of this query, for given or default amount of seconds
     * and optionally specifies which ID to use for the cache entry.
     *
     * @param int|null    $lifetime      How long the cache entry is valid, in seconds.
     * @param string|null $resultCacheId ID to use for the cache entry.
     *
     * @return $this
     */
    public function enableResultCache(?int $lifetime = null, ?string $resultCacheId = null): self
    {
        $this->setResultCacheLifetime($lifetime);
        $this->setResultCacheId($resultCacheId);

        return $this;
    }

    /**
     * Disables caching of the results of this query.
     *
     * @return $this
     */
    public function disableResultCache(): self
    {
        $this->_queryCacheProfile = null;

        return $this;
    }

    /**
     * Defines how long the result cache will be active before expire.
     *
     * @param int|null $lifetime How long the cache entry is valid, in seconds.
     *
     * @return $this
     */
    public function setResultCacheLifetime($lifetime)
    {
        $lifetime = (int) $lifetime;

        if ($this->_queryCacheProfile) {
            $this->_queryCacheProfile = $this->_queryCacheProfile->setLifetime($lifetime);

            return $this;
        }

        $this->_queryCacheProfile = new QueryCacheProfile($lifetime);

        $cache = $this->_em->getConfiguration()->getResultCache();
        if (! $cache) {
            return $this;
        }

        // Compatibility for DBAL 2
        if (! method_exists($this->_queryCacheProfile, 'setResultCache')) {
            $this->_queryCacheProfile = $this->_queryCacheProfile->setResultCacheDriver(DoctrineProvider::wrap($cache));

            return $this;
        }

        $this->_queryCacheProfile = $this->_queryCacheProfile->setResultCache($cache);

        return $this;
    }

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @deprecated
     *
     * @return int
     */
    public function getResultCacheLifetime()
    {
        return $this->_queryCacheProfile ? $this->_queryCacheProfile->getLifetime() : 0;
    }

    /**
     * Defines if the result cache is active or not.
     *
     * @param bool $expire Whether or not to force resultset cache expiration.
     *
     * @return $this
     */
    public function expireResultCache($expire = true)
    {
        $this->_expireResultCache = $expire;

        return $this;
    }

    /**
     * Retrieves if the resultset cache is active or not.
     *
     * @return bool
     */
    public function getExpireResultCache()
    {
        return $this->_expireResultCache;
    }

    /** @return QueryCacheProfile|null */
    public function getQueryCacheProfile()
    {
        return $this->_queryCacheProfile;
    }

    /**
     * Change the default fetch mode of an association for this query.
     *
     * @param class-string $class
     * @param string       $assocName
     * @param int          $fetchMode
     * @psalm-param Mapping\ClassMetadata::FETCH_EAGER|Mapping\ClassMetadata::FETCH_LAZY $fetchMode
     *
     * @return $this
     */
    public function setFetchMode($class, $assocName, $fetchMode)
    {
        if (! in_array($fetchMode, [Mapping\ClassMetadata::FETCH_EAGER, Mapping\ClassMetadata::FETCH_LAZY], true)) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/9777',
                'Calling %s() with something else than ClassMetadata::FETCH_EAGER or ClassMetadata::FETCH_LAZY is deprecated.',
                __METHOD__
            );
            $fetchMode = Mapping\ClassMetadata::FETCH_LAZY;
        }

        $this->_hints['fetchMode'][$class][$assocName] = $fetchMode;

        return $this;
    }

    /**
     * Defines the processing mode to be used during hydration / result set transformation.
     *
     * @param string|int $hydrationMode Doctrine processing mode to be used during hydration process.
     *                                  One of the Query::HYDRATE_* constants.
     * @psalm-param string|AbstractQuery::HYDRATE_* $hydrationMode
     *
     * @return $this
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrationMode = $hydrationMode;

        return $this;
    }

    /**
     * Gets the hydration mode currently used by the query.
     *
     * @return string|int
     * @psalm-return string|AbstractQuery::HYDRATE_*
     */
    public function getHydrationMode()
    {
        return $this->_hydrationMode;
    }

    /**
     * Gets the list of results for the query.
     *
     * Alias for execute(null, $hydrationMode = HYDRATE_OBJECT).
     *
     * @param string|int $hydrationMode
     * @psalm-param string|AbstractQuery::HYDRATE_* $hydrationMode
     *
     * @return mixed
     */
    public function getResult($hydrationMode = self::HYDRATE_OBJECT)
    {
        return $this->execute(null, $hydrationMode);
    }

    /**
     * Gets the array of results for the query.
     *
     * Alias for execute(null, HYDRATE_ARRAY).
     *
     * @return mixed[]
     */
    public function getArrayResult()
    {
        return $this->execute(null, self::HYDRATE_ARRAY);
    }

    /**
     * Gets one-dimensional array of results for the query.
     *
     * Alias for execute(null, HYDRATE_SCALAR_COLUMN).
     *
     * @return mixed[]
     */
    public function getSingleColumnResult()
    {
        return $this->execute(null, self::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * Gets the scalar results for the query.
     *
     * Alias for execute(null, HYDRATE_SCALAR).
     *
     * @return mixed[]
     */
    public function getScalarResult()
    {
        return $this->execute(null, self::HYDRATE_SCALAR);
    }

    /**
     * Get exactly one result or null.
     *
     * @param string|int|null $hydrationMode
     * @psalm-param string|AbstractQuery::HYDRATE_*|null $hydrationMode
     *
     * @return mixed
     *
     * @throws NonUniqueResultException
     */
    public function getOneOrNullResult($hydrationMode = null)
    {
        try {
            $result = $this->execute(null, $hydrationMode);
        } catch (NoResultException $e) {
            return null;
        }

        if ($this->_hydrationMode !== self::HYDRATE_SINGLE_SCALAR && ! $result) {
            return null;
        }

        if (! is_array($result)) {
            return $result;
        }

        if (count($result) > 1) {
            throw new NonUniqueResultException();
        }

        return array_shift($result);
    }

    /**
     * Gets the single result of the query.
     *
     * Enforces the presence as well as the uniqueness of the result.
     *
     * If the result is not unique, a NonUniqueResultException is thrown.
     * If there is no result, a NoResultException is thrown.
     *
     * @param string|int|null $hydrationMode
     * @psalm-param string|AbstractQuery::HYDRATE_*|null $hydrationMode
     *
     * @return mixed
     *
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException        If the query returned no result.
     */
    public function getSingleResult($hydrationMode = null)
    {
        $result = $this->execute(null, $hydrationMode);

        if ($this->_hydrationMode !== self::HYDRATE_SINGLE_SCALAR && ! $result) {
            throw new NoResultException();
        }

        if (! is_array($result)) {
            return $result;
        }

        if (count($result) > 1) {
            throw new NonUniqueResultException();
        }

        return array_shift($result);
    }

    /**
     * Gets the single scalar result of the query.
     *
     * Alias for getSingleResult(HYDRATE_SINGLE_SCALAR).
     *
     * @return mixed The scalar result.
     *
     * @throws NoResultException        If the query returned no result.
     * @throws NonUniqueResultException If the query result is not unique.
     */
    public function getSingleScalarResult()
    {
        return $this->getSingleResult(self::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Sets a query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name  The name of the hint.
     * @param mixed  $value The value of the hint.
     *
     * @return $this
     */
    public function setHint($name, $value)
    {
        $this->_hints[$name] = $value;

        return $this;
    }

    /**
     * Gets the value of a query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @param string $name The name of the hint.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getHint($name)
    {
        return $this->_hints[$name] ?? false;
    }

    /**
     * Check if the query has a hint
     *
     * @param string $name The name of the hint
     *
     * @return bool False if the query does not have any hint
     */
    public function hasHint($name)
    {
        return isset($this->_hints[$name]);
    }

    /**
     * Return the key value map of query hints that are currently set.
     *
     * @return array<string,mixed>
     */
    public function getHints()
    {
        return $this->_hints;
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterate over the result.
     *
     * @deprecated 2.8 Use {@see toIterable} instead. See https://github.com/doctrine/orm/issues/8463
     *
     * @param ArrayCollection|mixed[]|null $parameters    The query parameters.
     * @param string|int|null              $hydrationMode The hydration mode to use.
     * @psalm-param ArrayCollection<int, Parameter>|array<string, mixed>|null $parameters
     * @psalm-param string|AbstractQuery::HYDRATE_*|null                      $hydrationMode The hydration mode to use.
     *
     * @return IterableResult
     */
    public function iterate($parameters = null, $hydrationMode = null)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/8463',
            'Method %s() is deprecated and will be removed in Doctrine ORM 3.0. Use toIterable() instead.',
            __METHOD__
        );

        if ($hydrationMode !== null) {
            $this->setHydrationMode($hydrationMode);
        }

        if (! empty($parameters)) {
            $this->setParameters($parameters);
        }

        $rsm = $this->getResultSetMapping();
        if ($rsm === null) {
            throw new LogicException('Uninitialized result set mapping.');
        }

        $stmt = $this->_doExecute();

        return $this->_em->newHydrator($this->_hydrationMode)->iterate($stmt, $rsm, $this->_hints);
    }

    /**
     * Executes the query and returns an iterable that can be used to incrementally
     * iterate over the result.
     *
     * @param ArrayCollection|array|mixed[] $parameters    The query parameters.
     * @param string|int|null               $hydrationMode The hydration mode to use.
     * @psalm-param ArrayCollection<int, Parameter>|mixed[] $parameters
     * @psalm-param string|AbstractQuery::HYDRATE_*|null    $hydrationMode
     *
     * @return iterable<mixed>
     */
    public function toIterable(iterable $parameters = [], $hydrationMode = null): iterable
    {
        if ($hydrationMode !== null) {
            $this->setHydrationMode($hydrationMode);
        }

        if (
            ($this->isCountable($parameters) && count($parameters) !== 0)
            || ($parameters instanceof Traversable && iterator_count($parameters) !== 0)
        ) {
            $this->setParameters($parameters);
        }

        $rsm = $this->getResultSetMapping();
        if ($rsm === null) {
            throw new LogicException('Uninitialized result set mapping.');
        }

        if ($rsm->isMixed && count($rsm->scalarMappings) > 0) {
            throw QueryException::iterateWithMixedResultNotAllowed();
        }

        $stmt = $this->_doExecute();

        return $this->_em->newHydrator($this->_hydrationMode)->toIterable($stmt, $rsm, $this->_hints);
    }

    /**
     * Executes the query.
     *
     * @param ArrayCollection|mixed[]|null $parameters    Query parameters.
     * @param string|int|null              $hydrationMode Processing mode to be used during the hydration process.
     * @psalm-param ArrayCollection<int, Parameter>|mixed[]|null $parameters
     * @psalm-param string|AbstractQuery::HYDRATE_*|null         $hydrationMode
     *
     * @return mixed
     */
    public function execute($parameters = null, $hydrationMode = null)
    {
        if ($this->cacheable && $this->isCacheEnabled()) {
            return $this->executeUsingQueryCache($parameters, $hydrationMode);
        }

        return $this->executeIgnoreQueryCache($parameters, $hydrationMode);
    }

    /**
     * Execute query ignoring second level cache.
     *
     * @param ArrayCollection|mixed[]|null $parameters
     * @param string|int|null              $hydrationMode
     * @psalm-param ArrayCollection<int, Parameter>|mixed[]|null $parameters
     * @psalm-param string|AbstractQuery::HYDRATE_*|null         $hydrationMode
     *
     * @return mixed
     */
    private function executeIgnoreQueryCache($parameters = null, $hydrationMode = null)
    {
        if ($hydrationMode !== null) {
            $this->setHydrationMode($hydrationMode);
        }

        if (! empty($parameters)) {
            $this->setParameters($parameters);
        }

        $setCacheEntry = static function ($data): void {
        };

        if ($this->_hydrationCacheProfile !== null) {
            [$cacheKey, $realCacheKey] = $this->getHydrationCacheId();

            $cache     = $this->getHydrationCache();
            $cacheItem = $cache->getItem($cacheKey);
            $result    = $cacheItem->isHit() ? $cacheItem->get() : [];

            if (isset($result[$realCacheKey])) {
                return $result[$realCacheKey];
            }

            if (! $result) {
                $result = [];
            }

            $setCacheEntry = static function ($data) use ($cache, $result, $cacheItem, $realCacheKey): void {
                $cache->save($cacheItem->set($result + [$realCacheKey => $data]));
            };
        }

        $stmt = $this->_doExecute();

        if (is_numeric($stmt)) {
            $setCacheEntry($stmt);

            return $stmt;
        }

        $rsm = $this->getResultSetMapping();
        if ($rsm === null) {
            throw new LogicException('Uninitialized result set mapping.');
        }

        $data = $this->_em->newHydrator($this->_hydrationMode)->hydrateAll($stmt, $rsm, $this->_hints);

        $setCacheEntry($data);

        return $data;
    }

    private function getHydrationCache(): CacheItemPoolInterface
    {
        assert($this->_hydrationCacheProfile !== null);

        // Support for DBAL 2
        if (! method_exists($this->_hydrationCacheProfile, 'getResultCache')) {
            $cacheDriver = $this->_hydrationCacheProfile->getResultCacheDriver();
            assert($cacheDriver !== null);

            return CacheAdapter::wrap($cacheDriver);
        }

        $cache = $this->_hydrationCacheProfile->getResultCache();
        assert($cache !== null);

        return $cache;
    }

    /**
     * Load from second level cache or executes the query and put into cache.
     *
     * @param ArrayCollection|mixed[]|null $parameters
     * @param string|int|null              $hydrationMode
     * @psalm-param ArrayCollection<int, Parameter>|mixed[]|null $parameters
     * @psalm-param string|AbstractQuery::HYDRATE_*|null         $hydrationMode
     *
     * @return mixed
     */
    private function executeUsingQueryCache($parameters = null, $hydrationMode = null)
    {
        $rsm = $this->getResultSetMapping();
        if ($rsm === null) {
            throw new LogicException('Uninitialized result set mapping.');
        }

        $queryCache = $this->_em->getCache()->getQueryCache($this->cacheRegion);
        $queryKey   = new QueryCacheKey(
            $this->getHash(),
            $this->lifetime,
            $this->cacheMode ?: Cache::MODE_NORMAL,
            $this->getTimestampKey()
        );

        $result = $queryCache->get($queryKey, $rsm, $this->_hints);

        if ($result !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->queryCacheHit($queryCache->getRegion()->getName(), $queryKey);
            }

            return $result;
        }

        $result = $this->executeIgnoreQueryCache($parameters, $hydrationMode);
        $cached = $queryCache->put($queryKey, $rsm, $result, $this->_hints);

        if ($this->cacheLogger) {
            $this->cacheLogger->queryCacheMiss($queryCache->getRegion()->getName(), $queryKey);

            if ($cached) {
                $this->cacheLogger->queryCachePut($queryCache->getRegion()->getName(), $queryKey);
            }
        }

        return $result;
    }

    private function getTimestampKey(): ?TimestampCacheKey
    {
        assert($this->_resultSetMapping !== null);
        $entityName = reset($this->_resultSetMapping->aliasMap);

        if (empty($entityName)) {
            return null;
        }

        $metadata = $this->_em->getClassMetadata($entityName);

        return new Cache\TimestampCacheKey($metadata->rootEntityName);
    }

    /**
     * Get the result cache id to use to store the result set cache entry.
     * Will return the configured id if it exists otherwise a hash will be
     * automatically generated for you.
     *
     * @return string[] ($key, $hash)
     * @psalm-return array{string, string} ($key, $hash)
     */
    protected function getHydrationCacheId()
    {
        $parameters = [];
        $types      = [];

        foreach ($this->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $this->processParameterValue($parameter->getValue());
            $types[$parameter->getName()]      = $parameter->getType();
        }

        $sql = $this->getSQL();
        assert(is_string($sql));
        $queryCacheProfile      = $this->getHydrationCacheProfile();
        $hints                  = $this->getHints();
        $hints['hydrationMode'] = $this->getHydrationMode();

        ksort($hints);
        assert($queryCacheProfile !== null);

        return $queryCacheProfile->generateCacheKeys($sql, $parameters, $types, $hints);
    }

    /**
     * Set the result cache id to use to store the result set cache entry.
     * If this is not explicitly set by the developer then a hash is automatically
     * generated for you.
     *
     * @param string|null $id
     *
     * @return $this
     */
    public function setResultCacheId($id)
    {
        if (! $this->_queryCacheProfile) {
            return $this->setResultCacheProfile(new QueryCacheProfile(0, $id));
        }

        $this->_queryCacheProfile = $this->_queryCacheProfile->setCacheKey($id);

        return $this;
    }

    /**
     * Get the result cache id to use to store the result set cache entry if set.
     *
     * @deprecated
     *
     * @return string|null
     */
    public function getResultCacheId()
    {
        return $this->_queryCacheProfile ? $this->_queryCacheProfile->getCacheKey() : null;
    }

    /**
     * Executes the query and returns a the resulting Statement object.
     *
     * @return Result|int The executed database statement that holds
     *                    the results, or an integer indicating how
     *                    many rows were affected.
     */
    abstract protected function _doExecute();

    /**
     * Cleanup Query resource when clone is called.
     *
     * @return void
     */
    public function __clone()
    {
        $this->parameters = new ArrayCollection();

        $this->_hints = [];
        $this->_hints = $this->_em->getConfiguration()->getDefaultQueryHints();
    }

    /**
     * Generates a string of currently query to use for the cache second level cache.
     *
     * @return string
     */
    protected function getHash()
    {
        $query = $this->getSQL();
        assert(is_string($query));
        $hints  = $this->getHints();
        $params = array_map(function (Parameter $parameter) {
            $value = $parameter->getValue();

            // Small optimization
            // Does not invoke processParameterValue for scalar value
            if (is_scalar($value)) {
                return $value;
            }

            return $this->processParameterValue($value);
        }, $this->parameters->getValues());

        ksort($hints);

        return sha1($query . '-' . serialize($params) . '-' . serialize($hints));
    }

    /** @param iterable<mixed> $subject */
    private function isCountable(iterable $subject): bool
    {
        return $subject instanceof Countable || is_array($subject);
    }
}
