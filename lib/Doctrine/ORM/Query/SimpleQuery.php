<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Cache\QueryCacheProfile;

/**
 * A Query object represents a DQL query.
 */
interface SimpleQuery
{
    /* Hydration mode constants */

    /**
     * Hydrates an object graph. This is the default behavior.
     */
    const HYDRATE_OBJECT = 1;

    /**
     * Hydrates an array graph.
     */
    const HYDRATE_ARRAY = 2;

    /**
     * Hydrates a flat, rectangular result set with scalar values.
     */
    const HYDRATE_SCALAR = 3;

    /**
     * Hydrates a single scalar value.
     */
    const HYDRATE_SINGLE_SCALAR = 4;

    /**
     * Very simple object hydrator (optimized for performance).
     */
    const HYDRATE_SIMPLEOBJECT = 5;

    /* Query HINTS */

    /**
     * The refresh hint turns any query into a refresh query with the result that
     * any local changes in entities are overridden with the fetched values.
     *
     * @var string
     */
    const HINT_REFRESH = 'doctrine.refresh';

    /**
     * Internal hint: is set to the proxy entity that is currently triggered for loading
     *
     * @var string
     */
    const HINT_REFRESH_ENTITY = 'doctrine.refresh.entity';

    /**
     * The forcePartialLoad query hint forces a particular query to return
     * partial objects.
     *
     * @var string
     * @todo Rename: HINT_OPTIMIZE
     */
    const HINT_FORCE_PARTIAL_LOAD = 'doctrine.forcePartialLoad';

    /**
     * The includeMetaColumns query hint causes meta columns like foreign keys and
     * discriminator columns to be selected and returned as part of the query result.
     *
     * This hint does only apply to non-object queries.
     *
     * @var string
     */
    const HINT_INCLUDE_META_COLUMNS = 'doctrine.includeMetaColumns';

    /**
     * An array of class names that implement \Doctrine\ORM\Query\TreeWalker and
     * are iterated and executed after the DQL has been parsed into an AST.
     *
     * @var string
     */
    const HINT_CUSTOM_TREE_WALKERS = 'doctrine.customTreeWalkers';

    /**
     * A string with a class name that implements \Doctrine\ORM\Query\TreeWalker
     * and is used for generating the target SQL from any DQL AST tree.
     *
     * @var string
     */
    const HINT_CUSTOM_OUTPUT_WALKER = 'doctrine.customOutputWalker';

    //const HINT_READ_ONLY = 'doctrine.readOnly';

    /**
     * @var string
     */
    const HINT_INTERNAL_ITERATION = 'doctrine.internal.iteration';

    /**
     * Gets the SQL query that corresponds to this query object.
     * The returned SQL syntax depends on the connection driver that is used
     * by this query object at the time of this method call.
     *
     * @return string SQL query
     */
    public function getSQL();

    /**
     * Retrieves the associated EntityManager of this Query instance.
     *
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager();

    /**
     * Get all defined parameters.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection The defined query parameters.
     */
    public function getParameters();

    /**
     * Sets a collection of query parameters.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|array $parameters
     *
     * @return static This query instance.
     */
    public function setParameters($parameters);

    /**
     * Sets a query parameter.
     *
     * @param string|int  $key   The parameter position or name.
     * @param mixed       $value The parameter value.
     * @param string|null $type  The parameter type. If specified, the given value will be run through
     *                           the type conversion of this type. This is usually not needed for
     *                           strings and numeric types.
     *
     * @return static This query instance.
     */
    public function setParameter($key, $value, $type = null);

    /**
     * Processes an individual parameter value.
     *
     * @param mixed $value
     *
     * @return array|string
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function processParameterValue($value);

    /**
     * Sets the ResultSetMapping that should be used for hydration.
     *
     * @param \Doctrine\ORM\Query\ResultSetMapping $rsm
     *
     * @return static This query instance.
     */
    public function setResultSetMapping(ResultSetMapping $rsm);

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
     * @example
     * $lifetime = 100;
     * $resultKey = "abc";
     * $query->setHydrationCacheProfile(new QueryCacheProfile());
     * $query->setHydrationCacheProfile(new QueryCacheProfile($lifetime, $resultKey));
     *
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile $profile
     *
     * @return static This query instance.
     */
    public function setHydrationCacheProfile(QueryCacheProfile $profile = null);

    /**
     * @return \Doctrine\DBAL\Cache\QueryCacheProfile
     */
    public function getHydrationCacheProfile();

    /**
     * Change the default fetch mode of an association for this query.
     *
     * $fetchMode can be one of FetchMode::EAGER, FetchMode::LAZY or FetchMode::EXTRA_LAZY
     *
     * @param string $class
     * @param string $assocName
     * @param int    $fetchMode
     *
     * @return static This query instance.
     */
    public function setFetchMode($class, $assocName, $fetchMode);

    /**
     * Gets the hydration mode currently used by the query.
     *
     * @return integer
     */
    public function getHydrationMode();

    /**
     * Gets the list of results for the query.
     *
     * Alias for execute(null, $hydrationMode = HYDRATE_OBJECT).
     *
     * @param int $hydrationMode
     *
     * @return mixed
     */
    public function getResult($hydrationMode = self::HYDRATE_OBJECT);

    /**
     * Gets the array of results for the query.
     *
     * Alias for execute(null, HYDRATE_ARRAY).
     *
     * @return array
     */
    public function getArrayResult();

    /**
     * Get exactly one result or null.
     *
     * @param int $hydrationMode
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOneOrNullResult($hydrationMode = null);

    /**
     * Gets the single result of the query.
     *
     * Enforces the presence as well as the uniqueness of the result.
     *
     * If the result is not unique, a NonUniqueResultException is thrown.
     * If there is no result, a NoResultException is thrown.
     *
     * @param integer $hydrationMode
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException If the query result is not unique.
     * @throws \Doctrine\ORM\NoResultException        If the query returned no result and hydration mode is not HYDRATE_SINGLE_SCALAR.
     */
    public function getSingleResult($hydrationMode = null);

    /**
     * Gets the single scalar result of the query.
     *
     * Alias for getSingleResult(HYDRATE_SINGLE_SCALAR).
     *
     * @return mixed The scalar result, or NULL if the query returned no result.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException If the query result is not unique.
     */
    public function getSingleScalarResult();

    /**
     * Gets the value of a query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @param string $name The name of the hint.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getHint($name);

    /**
     * Check if the query has a hint
     *
     * @param string $name The name of the hint
     *
     * @return bool False if the query does not have any hint
     */
    public function hasHint($name);

    /**
     * Return the key value map of query hints that are currently set.
     *
     * @return array
     */
    public function getHints();

    /**
     * Executes the query.
     *
     * @param ArrayCollection|array|null $parameters    Query parameters.
     * @param integer|null               $hydrationMode Processing mode to be used during the hydration process.
     *
     * @return mixed
     */
    public function execute($parameters = null, $hydrationMode = null);

    /**
     * Frees the resources used by the query object.
     *
     * Resets Parameters, Parameter Types and Query Hints.
     *
     * @return void
     */
    public function free();

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterated over the result.
     *
     * @param ArrayCollection|array|null $parameters    The query parameters.
     * @param integer                    $hydrationMode The hydration mode to use.
     *
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate($parameters = null, $hydrationMode = self::HYDRATE_OBJECT);

    /**
     * Sets a query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name  The name of the hint.
     * @param mixed  $value The value of the hint.
     *
     * @return static This query instance.
     */
    public function setHint($name, $value);

    /**
     * Defines the processing mode to be used during hydration / result set transformation.
     *
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Query::HYDRATE_* constants.
     *
     * @return static This query instance.
     */
    public function setHydrationMode($hydrationMode);
}
