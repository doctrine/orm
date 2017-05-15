<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Cache\QueryCacheProfile;

interface QueryInterface
{
    /**
     * Enable/disable second level query (result) caching for this query.
     *
     * @param boolean $cacheable
     *
     * @return static This query instance.
     */
    public function setCacheable($cacheable);

    /**
     * @return boolean TRUE if the query results are enable for second level cache, FALSE otherwise.
     */
    public function isCacheable();

    /**
     * @param string $cacheRegion
     *
     * @return static This query instance.
     */
    public function setCacheRegion($cacheRegion);

    /**
     * Obtain the name of the second level query cache region in which query results will be stored
     *
     * @return string|null The cache region name; NULL indicates the default region.
     */
    public function getCacheRegion();

    /**
     * @return integer
     */
    public function getLifetime();

    /**
     * Sets the life-time for this query into second level cache.
     *
     * @param integer $lifetime
     *
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setLifetime($lifetime);

    /**
     * @return integer
     */
    public function getCacheMode();

    /**
     * @param integer $cacheMode
     *
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setCacheMode($cacheMode);

    /**
     * Retrieves the associated EntityManager of this Query instance.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager();

    /**
     * Get all defined parameters.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection The defined query parameters.
     */
    public function getParameters();

    /**
     * Gets a query parameter.
     *
     * @param mixed $key The key (index or name) of the bound parameter.
     *
     * @return Query\Parameter|null The value of the bound parameter, or NULL if not available.
     */
    public function getParameter($key);

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
     * @param string|int $key The parameter position or name.
     * @param mixed $value The parameter value.
     * @param string|null $type The parameter type. If specified, the given value will be run through
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
    public function setResultSetMapping(Query\ResultSetMapping $rsm);

    /**
     * Set a cache profile for hydration caching.
     * If no result cache driver is set in the QueryCacheProfile, the default
     * result cache driver is used from the configuration.
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
     * Set a cache profile for the result cache.
     * If no result cache driver is set in the QueryCacheProfile, the default
     * result cache driver is used from the configuration.
     *
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile $profile
     *
     * @return static This query instance.
     */
    public function setResultCacheProfile(QueryCacheProfile $profile = null);

    /**
     * Defines a cache driver to be used for caching result sets and implicitly enables caching.
     *
     * @param \Doctrine\Common\Cache\Cache|null $resultCacheDriver Cache driver
     *
     * @return static This query instance.
     * @throws ORMException
     */
    public function setResultCacheDriver($resultCacheDriver = null);

    /**
     * Returns the cache driver used for caching result sets.
     *
     * @deprecated
     * @return \Doctrine\Common\Cache\Cache Cache driver
     */
    public function getResultCacheDriver();

    /**
     * Set whether or not to cache the results of this query and if so, for
     * how long and which ID to use for the cache entry.
     *
     * @param boolean $bool
     * @param integer $lifetime
     * @param string $resultCacheId
     *
     * @return static This query instance.
     */
    public function useResultCache($bool, $lifetime = null, $resultCacheId = null);

    /**
     * Defines how long the result cache will be active before expire.
     *
     * @param integer $lifetime How long the cache entry is valid.
     *
     * @return static This query instance.
     */
    public function setResultCacheLifetime($lifetime);

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @deprecated
     * @return integer
     */
    public function getResultCacheLifetime();

    /**
     * Defines if the result cache is active or not.
     *
     * @param boolean $expire Whether or not to force resultset cache expiration.
     *
     * @return static This query instance.
     */
    public function expireResultCache($expire = true);

    /**
     * Retrieves if the resultset cache is active or not.
     *
     * @return boolean
     */
    public function getExpireResultCache();

    /**
     * @return QueryCacheProfile
     */
    public function getQueryCacheProfile();

    /**
     * Change the default fetch mode of an association for this query.
     * $fetchMode can be one of ClassMetadata::FETCH_EAGER or ClassMetadata::FETCH_LAZY
     *
     * @param string $class
     * @param string $assocName
     * @param int $fetchMode
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
     * Alias for execute(null, $hydrationMode = HYDRATE_OBJECT).
     *
     * @param int $hydrationMode
     *
     * @return mixed
     */
    public function getResult($hydrationMode = Query::HYDRATE_OBJECT);

    /**
     * Gets the array of results for the query.
     * Alias for execute(null, HYDRATE_ARRAY).
     *
     * @return array
     */
    public function getArrayResult();

    /**
     * Gets the scalar results for the query.
     * Alias for execute(null, HYDRATE_SCALAR).
     *
     * @return array
     */
    public function getScalarResult();

    /**
     * Get exactly one result or null.
     *
     * @param int $hydrationMode
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function getOneOrNullResult($hydrationMode = null);

    /**
     * Gets the single result of the query.
     * Enforces the presence as well as the uniqueness of the result.
     * If the result is not unique, a NonUniqueResultException is thrown.
     * If there is no result, a NoResultException is thrown.
     *
     * @param integer $hydrationMode
     *
     * @return mixed
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException        If the query returned no result.
     */
    public function getSingleResult($hydrationMode = null);

    /**
     * Gets the single scalar result of the query.
     * Alias for getSingleResult(HYDRATE_SINGLE_SCALAR).
     *
     * @return mixed
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException        If the query returned no result.
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
     * @param ArrayCollection|array|null $parameters Query parameters.
     * @param integer|null $hydrationMode Processing mode to be used during the hydration process.
     *
     * @return mixed
     */
    public function execute($parameters = null, $hydrationMode = null);

    /**
     * Set the result cache id to use to store the result set cache entry.
     * If this is not explicitly set by the developer then a hash is automatically
     * generated for you.
     *
     * @param string $id
     *
     * @return static This query instance.
     */
    public function setResultCacheId($id);

    /**
     * Get the result cache id to use to store the result set cache entry if set.
     *
     * @deprecated
     * @return string
     */
    public function getResultCacheId();

    /**
     * Gets the SQL query/queries that correspond to this DQL query.
     *
     * @return mixed The built sql query or an array of all sql queries.
     * @override
     */
    public function getSQL();

    /**
     * Returns the corresponding AST for this DQL query.
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement |
     *         \Doctrine\ORM\Query\AST\UpdateStatement |
     *         \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function getAST();

    /**
     * Defines a cache driver to be used for caching queries.
     *
     * @param \Doctrine\Common\Cache\Cache|null $queryCache Cache driver.
     *
     * @return QueryInterface This query instance.
     */
    public function setQueryCacheDriver($queryCache);

    /**
     * Defines whether the query should make use of a query cache, if available.
     *
     * @param boolean $bool
     *
     * @return QueryInterface This query instance.
     */
    public function useQueryCache($bool);

    /**
     * Returns the cache driver used for query caching.
     *
     * @return \Doctrine\Common\Cache\Cache|null The cache driver used for query caching or NULL, if
     *                                           this Query does not use query caching.
     */
    public function getQueryCacheDriver();

    /**
     * Defines how long the query cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid.
     *
     * @return QueryInterface This query instance.
     */
    public function setQueryCacheLifetime($timeToLive);

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @return int
     */
    public function getQueryCacheLifetime();

    /**
     * Defines if the query cache is active or not.
     *
     * @param boolean $expire Whether or not to force query cache expiration.
     *
     * @return QueryInterface This query instance.
     */
    public function expireQueryCache($expire = true);

    /**
     * Retrieves if the query cache is active or not.
     *
     * @return bool
     */
    public function getExpireQueryCache();

    /**
     * @override
     */
    public function free();

    /**
     * Sets a DQL query string.
     *
     * @param string $dqlQuery DQL Query.
     *
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setDQL($dqlQuery);

    /**
     * Returns the DQL query that is represented by this query object.
     *
     * @return string DQL query.
     */
    public function getDQL();

    /**
     * Returns the state of this query object
     * By default the type is Doctrine_ORM_Query_Abstract::STATE_CLEAN but if it appears any unprocessed DQL
     * part, it is switched to Doctrine_ORM_Query_Abstract::STATE_DIRTY.
     *
     * @see AbstractQuery::STATE_CLEAN
     * @see AbstractQuery::STATE_DIRTY
     * @return integer The query state.
     */
    public function getState();

    /**
     * Method to check if an arbitrary piece of DQL exists
     *
     * @param string $dql Arbitrary piece of DQL to check for.
     *
     * @return boolean
     */
    public function contains($dql);

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     *
     * @return QueryInterface This query object.
     */
    public function setFirstResult($firstResult);

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this query.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult();

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults
     *
     * @return QueryInterface This query object.
     */
    public function setMaxResults($maxResults);

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query.
     *
     * @return integer Maximum number of results.
     */
    public function getMaxResults();

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterated over the result.
     *
     * @param ArrayCollection|array|null $parameters The query parameters.
     * @param integer $hydrationMode The hydration mode to use.
     *
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate($parameters = null, $hydrationMode = Query::HYDRATE_OBJECT);

    /**
     * {@inheritdoc}
     */
    public function setHint($name, $value);

    /**
     * {@inheritdoc}
     */
    public function setHydrationMode($hydrationMode);

    /**
     * Set the lock mode for this Query.
     *
     * @see \Doctrine\DBAL\LockMode
     *
     * @param int $lockMode
     *
     * @return QueryInterface
     * @throws TransactionRequiredException
     */
    public function setLockMode($lockMode);

    /**
     * Get the current lock mode for this query.
     *
     * @return int|null The current lock mode of this query or NULL if no specific lock mode is set.
     */
    public function getLockMode();
}
