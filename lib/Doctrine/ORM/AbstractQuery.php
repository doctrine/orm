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

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Cache\QueryCacheProfile;

use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Mapping;

/**
 * Base contract for ORM queries. Base class for Query and NativeQuery.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class AbstractQuery
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

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection The parameter map of this query.
     */
    protected $parameters;

    /**
     * @var ResultSetMapping The user-specified ResultSetMapping to use.
     */
    protected $_resultSetMapping;

    /**
     * @var \Doctrine\ORM\EntityManager The entity manager used by this query object.
     */
    protected $_em;

    /**
     * @var array The map of query hints.
     */
    protected $_hints = array();

    /**
     * @var integer The hydration mode.
     */
    protected $_hydrationMode = self::HYDRATE_OBJECT;

    /**
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile
     */
    protected $_queryCacheProfile;

    /**
     * @var boolean Boolean value that indicates whether or not expire the result cache.
     */
    protected $_expireResultCache = false;

    /**
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile
     */
    protected $_hydrationCacheProfile;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractQuery</tt>.
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->parameters = new ArrayCollection();
    }

    /**
     * Gets the SQL query that corresponds to this query object.
     * The returned SQL syntax depends on the connection driver that is used
     * by this query object at the time of this method call.
     *
     * @return string SQL query
     */
    abstract public function getSQL();

    /**
     * Retrieves the associated EntityManager of this Query instance.
     *
     * @return \Doctrine\ORM\EntityManager
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

        $this->_hints = array();
    }

    /**
     * Get all defined parameters.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection The defined query parameters.
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Gets a query parameter.
     *
     * @param mixed $key The key (index or name) of the bound parameter.
     *
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        $filteredParameters = $this->parameters->filter(
            function ($parameter) use ($key)
            {
                // Must not be identical because of string to integer conversion
                return ($key == $parameter->getName());
            }
        );

        return count($filteredParameters) ? $filteredParameters->first() : null;
    }

    /**
     * Sets a collection of query parameters.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|array $parameters
     *
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setParameters($parameters)
    {
        // BC compatibility with 2.3-
        if (is_array($parameters)) {
            $parameterCollection = new ArrayCollection();

            foreach ($parameters as $key => $value) {
                $parameter = new Query\Parameter($key, $value);

                $parameterCollection->add($parameter);
            }

            $parameters = $parameterCollection;
        }

        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Sets a query parameter.
     *
     * @param string|integer $key The parameter position or name.
     * @param mixed $value The parameter value.
     * @param string $type The parameter type. If specified, the given value will be run through
     *                     the type conversion of this type. This is usually not needed for
     *                     strings and numeric types.
     *
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setParameter($key, $value, $type = null)
    {
        $filteredParameters = $this->parameters->filter(
            function ($parameter) use ($key)
            {
                // Must not be identical because of string to integer conversion
                return ($key == $parameter->getName());
            }
        );

        if (count($filteredParameters)) {
            $parameter = $filteredParameters->first();
            $parameter->setValue($value, $type);

            return $this;
        }

        $parameter = new Query\Parameter($key, $value, $type);

        $this->parameters->add($parameter);

        return $this;
    }

    /**
     * Process an individual parameter value
     *
     * @param mixed $value
     * @return array
     */
    public function processParameterValue($value)
    {
        switch (true) {
            case is_array($value):
                foreach ($value as $key => $paramValue) {
                    $paramValue  = $this->processParameterValue($paramValue);
                    $value[$key] = is_array($paramValue) ? $paramValue[key($paramValue)] : $paramValue;
                }

                return $value;

            case is_object($value) && $this->_em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value)):
                return $this->convertObjectParameterToScalarValue($value);

            case ($value instanceof Mapping\ClassMetadata):
                return $value->name;

            default:
                return $value;
        }
    }

    private function convertObjectParameterToScalarValue($value)
    {
        $class = $this->_em->getClassMetadata(get_class($value));

        if ($class->isIdentifierComposite) {
            throw new \InvalidArgumentException(
                "Binding an entity with a composite primary key to a query is not supported. " .
                "You should split the parameter into the explicit fields and bind them seperately."
            );
        }

        $values = ($this->_em->getUnitOfWork()->getEntityState($value) === UnitOfWork::STATE_MANAGED)
            ? $this->_em->getUnitOfWork()->getEntityIdentifier($value)
            : $class->getIdentifierValues($value);

        $value = $values[$class->getSingleIdentifierFieldName()];

        if (null === $value) {
            throw new \InvalidArgumentException(
                "Binding entities to query parameters only allowed for entities that have an identifier."
            );
        }

        return $value;
    }

    /**
     * Sets the ResultSetMapping that should be used for hydration.
     *
     * @param ResultSetMapping $rsm
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setResultSetMapping(Query\ResultSetMapping $rsm)
    {
        $this->_resultSetMapping = $rsm;

        return $this;
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
     * @example
     * $lifetime = 100;
     * $resultKey = "abc";
     * $query->setHydrationCacheProfile(new QueryCacheProfile());
     * $query->setHydrationCacheProfile(new QueryCacheProfile($lifetime, $resultKey));
     *
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile $profile
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setHydrationCacheProfile(QueryCacheProfile $profile = null)
    {
        if ( ! $profile->getResultCacheDriver()) {
            $resultCacheDriver = $this->_em->getConfiguration()->getHydrationCacheImpl();
            $profile = $profile->setResultCacheDriver($resultCacheDriver);
        }

        $this->_hydrationCacheProfile = $profile;

        return $this;
    }

    /**
     * @return \Doctrine\DBAL\Cache\QueryCacheProfile
     */
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
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile $profile
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setResultCacheProfile(QueryCacheProfile $profile = null)
    {
        if ( ! $profile->getResultCacheDriver()) {
            $resultCacheDriver = $this->_em->getConfiguration()->getResultCacheImpl();
            $profile = $profile->setResultCacheDriver($resultCacheDriver);
        }

        $this->_queryCacheProfile = $profile;

        return $this;
    }

    /**
     * Defines a cache driver to be used for caching result sets and implictly enables caching.
     *
     * @param \Doctrine\Common\Cache\Cache $driver Cache driver
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setResultCacheDriver($resultCacheDriver = null)
    {
        if ($resultCacheDriver !== null && ! ($resultCacheDriver instanceof \Doctrine\Common\Cache\Cache)) {
            throw ORMException::invalidResultCacheDriver();
        }

        $this->_queryCacheProfile = $this->_queryCacheProfile
            ? $this->_queryCacheProfile->setResultCacheDriver($resultCacheDriver)
            : new QueryCacheProfile(0, null, $resultCacheDriver);

        return $this;
    }

    /**
     * Returns the cache driver used for caching result sets.
     *
     * @deprecated
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
     * @param boolean $bool
     * @param integer $lifetime
     * @param string $resultCacheId
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function useResultCache($bool, $lifetime = null, $resultCacheId = null)
    {
        if ($bool) {
            $this->setResultCacheLifetime($lifetime);
            $this->setResultCacheId($resultCacheId);

            return $this;
        }

        $this->_queryCacheProfile = null;

        return $this;
    }

    /**
     * Defines how long the result cache will be active before expire.
     *
     * @param integer $lifetime How long the cache entry is valid.
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setResultCacheLifetime($lifetime)
    {
        $lifetime = ($lifetime !== null) ? (int) $lifetime : 0;

        $this->_queryCacheProfile = $this->_queryCacheProfile
            ? $this->_queryCacheProfile->setLifetime($lifetime)
            : new QueryCacheProfile($lifetime, null, $this->_em->getConfiguration()->getResultCacheImpl());

        return $this;
    }

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @deprecated
     * @return integer
     */
    public function getResultCacheLifetime()
    {
        return $this->_queryCacheProfile ? $this->_queryCacheProfile->getLifetime() : 0;
    }

    /**
     * Defines if the result cache is active or not.
     *
     * @param boolean $expire Whether or not to force resultset cache expiration.
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function expireResultCache($expire = true)
    {
        $this->_expireResultCache = $expire;

        return $this;
    }

    /**
     * Retrieves if the resultset cache is active or not.
     *
     * @return boolean
     */
    public function getExpireResultCache()
    {
        return $this->_expireResultCache;
    }

    /**
     * @return QueryCacheProfile
     */
    public function getQueryCacheProfile()
    {
        return $this->_queryCacheProfile;
    }

    /**
     * Change the default fetch mode of an association for this query.
     *
     * $fetchMode can be one of ClassMetadata::FETCH_EAGER or ClassMetadata::FETCH_LAZY
     *
     * @param  string $class
     * @param  string $assocName
     * @param  int $fetchMode
     * @return AbstractQuery
     */
    public function setFetchMode($class, $assocName, $fetchMode)
    {
        if ($fetchMode !== Mapping\ClassMetadata::FETCH_EAGER) {
            $fetchMode = Mapping\ClassMetadata::FETCH_LAZY;
        }

        $this->_hints['fetchMode'][$class][$assocName] = $fetchMode;

        return $this;
    }

    /**
     * Defines the processing mode to be used during hydration / result set transformation.
     *
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Query::HYDRATE_* constants.
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrationMode = $hydrationMode;

        return $this;
    }

    /**
     * Gets the hydration mode currently used by the query.
     *
     * @return integer
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
     * @return array
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
     * @return array
     */
    public function getArrayResult()
    {
        return $this->execute(null, self::HYDRATE_ARRAY);
    }

    /**
     * Gets the scalar results for the query.
     *
     * Alias for execute(null, HYDRATE_SCALAR).
     *
     * @return array
     */
    public function getScalarResult()
    {
        return $this->execute(null, self::HYDRATE_SCALAR);
    }

    /**
     * Get exactly one result or null.
     *
     * @throws NonUniqueResultException
     * @param int $hydrationMode
     * @return mixed
     */
    public function getOneOrNullResult($hydrationMode = null)
    {
        $result = $this->execute(null, $hydrationMode);

        if ($this->_hydrationMode !== self::HYDRATE_SINGLE_SCALAR && ! $result) {
            return null;
        }

        if ( ! is_array($result)) {
            return $result;
        }

        if (count($result) > 1) {
            throw new NonUniqueResultException;
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
     * @param integer $hydrationMode
     * @return mixed
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException If the query returned no result.
     */
    public function getSingleResult($hydrationMode = null)
    {
        $result = $this->execute(null, $hydrationMode);

        if ($this->_hydrationMode !== self::HYDRATE_SINGLE_SCALAR && ! $result) {
            throw new NoResultException;
        }

        if ( ! is_array($result)) {
            return $result;
        }

        if (count($result) > 1) {
            throw new NonUniqueResultException;
        }

        return array_shift($result);
    }

    /**
     * Gets the single scalar result of the query.
     *
     * Alias for getSingleResult(HYDRATE_SINGLE_SCALAR).
     *
     * @return mixed
     * @throws QueryException If the query result is not unique.
     */
    public function getSingleScalarResult()
    {
        return $this->getSingleResult(self::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Sets a query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name The name of the hint.
     * @param mixed $value The value of the hint.
     * @return \Doctrine\ORM\AbstractQuery
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
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getHint($name)
    {
        return isset($this->_hints[$name]) ? $this->_hints[$name] : false;
    }

    /**
     * Check if the query has a hint
     *
     * @param  string $name The name of the hint
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
     * @return array
     */
    public function getHints()
    {
        return $this->_hints;
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterate over the result.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|array $parameters The query parameters.
     * @param integer $hydrationMode The hydration mode to use.
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate($parameters = null, $hydrationMode = null)
    {
        if ($hydrationMode !== null) {
            $this->setHydrationMode($hydrationMode);
        }

        if ( ! empty($parameters)) {
            $this->setParameters($parameters);
        }

        $stmt = $this->_doExecute();

        return $this->_em->newHydrator($this->_hydrationMode)->iterate(
            $stmt, $this->_resultSetMapping, $this->_hints
        );
    }

    /**
     * Executes the query.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|array $parameters Query parameters.
     * @param integer $hydrationMode Processing mode to be used during the hydration process.
     * @return mixed
     */
    public function execute($parameters = null, $hydrationMode = null)
    {
        if ($hydrationMode !== null) {
            $this->setHydrationMode($hydrationMode);
        }

        if ( ! empty($parameters)) {
            $this->setParameters($parameters);
        }

        $setCacheEntry = function() {};

        if ($this->_hydrationCacheProfile !== null) {
            list($cacheKey, $realCacheKey) = $this->getHydrationCacheId();

            $queryCacheProfile = $this->getHydrationCacheProfile();
            $cache             = $queryCacheProfile->getResultCacheDriver();
            $result            = $cache->fetch($cacheKey);

            if (isset($result[$realCacheKey])) {
                return $result[$realCacheKey];
            }

            if ( ! $result) {
                $result = array();
            }

            $setCacheEntry = function($data) use ($cache, $result, $cacheKey, $realCacheKey, $queryCacheProfile) {
                $result[$realCacheKey] = $data;

                $cache->save($cacheKey, $result, $queryCacheProfile->getLifetime());
            };
        }

        $stmt = $this->_doExecute();

        if (is_numeric($stmt)) {
            $setCacheEntry($stmt);

            return $stmt;
        }

        $data = $this->_em->getHydrator($this->_hydrationMode)->hydrateAll(
            $stmt, $this->_resultSetMapping, $this->_hints
        );

        $setCacheEntry($data);

        return $data;
    }

    /**
     * Get the result cache id to use to store the result set cache entry.
     * Will return the configured id if it exists otherwise a hash will be
     * automatically generated for you.
     *
     * @return array ($key, $hash)
     */
    protected function getHydrationCacheId()
    {
        $parameters = array();

        foreach ($this->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $this->processParameterValue($parameter->getValue());
        }

        $sql                    = $this->getSQL();
        $queryCacheProfile      = $this->getHydrationCacheProfile();
        $hints                  = $this->getHints();
        $hints['hydrationMode'] = $this->getHydrationMode();

        ksort($hints);

        return $queryCacheProfile->generateCacheKeys($sql, $parameters, $hints);
    }

    /**
     * Set the result cache id to use to store the result set cache entry.
     * If this is not explicitly set by the developer then a hash is automatically
     * generated for you.
     *
     * @param string $id
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setResultCacheId($id)
    {
        $this->_queryCacheProfile = $this->_queryCacheProfile
            ? $this->_queryCacheProfile->setCacheKey($id)
            : new QueryCacheProfile(0, $id, $this->_em->getConfiguration()->getResultCacheImpl());

        return $this;
    }

    /**
     * Get the result cache id to use to store the result set cache entry if set.
     *
     * @deprecated
     * @return string
     */
    public function getResultCacheId()
    {
        return $this->_queryCacheProfile ? $this->_queryCacheProfile->getCacheKey() : null;
    }

    /**
     * Executes the query and returns a the resulting Statement object.
     *
     * @return \Doctrine\DBAL\Driver\Statement The executed database statement that holds the results.
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

        $this->_hints = array();
    }
}
