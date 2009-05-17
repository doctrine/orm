<?php
/*
 *  $Id: Abstract.php 1393 2008-03-06 17:49:16Z guilhermeblanco $
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

/**
 * Base class for Query and NativeQuery.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision: 1393 $
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
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
     * Hydrates nothing.
     */
    const HYDRATE_NONE = 5;

    /**
     * @var array $params Parameters of this query.
     */
    protected $_params = array();

    /**
     * @var array $_enumParams Array containing the keys of the parameters that should be enumerated.
     */
    protected $_enumParams = array();

    /**
     * The user-specified ResultSetMapping to use.
     *
     * @var ResultSetMapping
     */
    protected $_resultSetMapping;

    /**
     * @var Doctrine\ORM\EntityManager The entity manager used by this query object.
     */
    protected $_em;

    /**
     * A set of query hints.
     *
     * @var array
     */
    protected $_hints = array();

    /**
     * @var integer The hydration mode.
     */
    protected $_hydrationMode = self::HYDRATE_OBJECT;

    /**
     * The locally set cache driver used for caching result sets of this query.
     *
     * @var CacheDriver
     */
    protected $_resultCache;

    /**
     * @var boolean Boolean value that indicates whether or not expire the result cache.
     */
    protected $_expireResultCache = false;

    /**
     * @var int Result Cache lifetime.
     */
    protected $_resultCacheTTL;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractQuery</tt>.
     *
     * @param Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->_em = $entityManager;
    }

    /**
     * Retrieves the associated EntityManager of this Query instance.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * Frees the resources used by the query object.
     */
    public function free()
    {
        $this->_params = array();
        $this->_enumParams = array();
    }

    /**
     * Set enumerated parameters
     *
     * @param array $enumParams Enum parameters.
     */
    protected function _setEnumParams($enumParams = array())
    {
        $this->_enumParams = $enumParams;
    }

    /**
     * Get all enumerated parameters
     *
     * @return array All enumerated parameters
     */
    public function getEnumParams()
    {
        return $this->_enumParams;
    }

    /**
     * Convert ENUM parameters to their integer equivalents
     *
     * @param $params Parameters to be converted
     * @return array Converted parameters array
     */
    public function convertEnums($params)
    {
        foreach ($this->_enumParams as $key => $values) {
            if (isset($params[$key]) && ! empty($values)) {
                $params[$key] = $values[0]->enumIndex($values[1], $params[$key]);
            }
        }

        return $params;
    }

    /**
     * Get all defined parameters
     *
     * @return array Defined parameters
     */
    public function getParams($params = array())
    {
        return array_merge($this->_params, $params);
    }

    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams(array $params = array()) {
        $this->_params = $params;
    }

    /**
     * Gets the SQL query that corresponds to this query object.
     * The returned SQL syntax depends on the connection driver that is used
     * by this query object at the time of this method call.
     *
     * @return string SQL query
     */
    abstract public function getSql();
    
    /**
     * Sets a query parameter.
     *
     * @param string|integer $key The parameter position or name.
     * @param mixed $value The parameter value.
     */
    public function setParameter($key, $value)
    {
        $this->_params[$key] = $value;
    }
    
    /**
     * Sets a collection of query parameters.
     *
     * @param array $params
     */
    public function setParameters(array $params)
    {
        foreach ($params as $key => $value) {
            $this->setParameter($key, $value);
        }
    }

    /**
     * Sets the ResultSetMapping that should be used for hydration.
     *
     * @param ResultSetMapping $rsm
     */
    public function setResultSetMapping($rsm)
    {
        $this->_resultSetMapping = $rsm;
    }

    /**
     * Defines a cache driver to be used for caching result sets.
     *
     * @param Doctrine\ORM\Cache\Cache $driver Cache driver
     * @return Doctrine\ORM\Query
     */
    public function setResultCache($resultCache = null)
    {
        if ($resultCache !== null && ! ($resultCache instanceof \Doctrine\ORM\Cache\Cache)) {
            throw DoctrineException::updateMe(
                'Method setResultCache() accepts only an instance of Doctrine_Cache_Interface or null.'
            );
        }
        $this->_resultCache = $resultCache;
        return $this;
    }

    /**
     * Returns the cache driver used for caching result sets.
     *
     * @return Doctrine_Cache_Interface Cache driver
     */
    public function getResultCache()
    {
        if ($this->_resultCache instanceof \Doctrine\ORM\Cache\Cache) {
            return $this->_resultCache;
        } else {
            return $this->_em->getConfiguration()->getResultCacheImpl();
        }
    }

    /**
     * Defines how long the result cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid
     * @return Doctrine\ORM\Query
     */
    public function setResultCacheLifetime($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }

        $this->_resultCacheTTL = $timeToLive;

        return $this;
    }

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @return int
     */
    public function getResultCacheLifetime()
    {
        return $this->_resultCacheTTL;
    }

    /**
     * Defines if the resultset cache is active or not.
     *
     * @param boolean $expire Whether or not to force resultset cache expiration.
     * @return Doctrine_ORM_Query
     */
    public function setExpireResultCache($expire = true)
    {
        $this->_expireResultCache = (bool) $expire;

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

    /**
     * Defines the processing mode to be used during hydration.
     *
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Query::HYDRATE_* constants.
     * @return Doctrine\ORM\Query
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
     * Alias for execute(array(), HYDRATE_OBJECT).
     *
     * @return Collection
     */
    public function getResultList()
    {
        return $this->execute(array(), self::HYDRATE_OBJECT);
    }

    /**
     * Gets the array of results for the query.
     *
     * Alias for execute(array(), HYDRATE_ARRAY).
     *
     * @return array
     */
    public function getResultArray()
    {
        return $this->execute(array(), self::HYDRATE_ARRAY);
    }

    /**
     * Gets the scalar results for the query.
     *
     * Alias for execute(array(), HYDRATE_SCALAR).
     *
     * @return array
     */
    public function getScalarResult()
    {
        return $this->execute(array(), self::HYDRATE_SCALAR);
    }

    /**
     * Gets the single result of the query.
     * Enforces the uniqueness of the result. If the result is not unique,
     * a QueryException is thrown.
     *
     * @param integer $hydrationMode
     * @return mixed
     * @throws QueryException If the query result is not unique.
     */
    public function getSingleResult($hydrationMode = null)
    {
        $result = $this->execute(array(), $hydrationMode);
        if (is_array($result)) {
            if (count($result) > 1) {
                throw QueryException::nonUniqueResult();
            }
            return array_shift($result);
        } else if (is_object($result)) {
            if (count($result) > 1) {
                throw QueryException::nonUniqueResult();
            }
            return $result->first();
        }
        return $result;
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
     * Sets an implementation-specific hint. If the hint name is not recognized,
     * it is silently ignored.
     *
     * @param string $name The name of the hint.
     * @param mixed $value The value of the hint.
     */
    public function setHint($name, $value)
    {
        $this->_hints[$name] = $value;
    }

    /**
     * Gets an implementation-specific hint. If the hint name is not recognized,
     * FALSE is returned.
     *
     * @param string $name The name of the hint.
     * @return mixed The value of the hint or FALSe, if the hint name is not recognized.
     */
    public function getHint($name)
    {
        return isset($this->_hints[$name]) ? $this->_hints[$name] : false;
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterated over the result.
     *
     * @param array $params The query parameters.
     * @param integer $hydrationMode The hydratio mode to use.
     * @return IterableResult
     */
    public function iterate(array $params = array(), $hydrationMode = self::HYDRATE_OBJECT)
    {
        return $this->_em->getHydrator($this->_hydrationMode)->iterate(
            $this->_execute($params, $hydrationMode), $this->_resultSetMapping
        );
    }

    /**
     * Executes the query.
     *
     * @param string $params Any additional query parameters.
     * @param integer $hydrationMode Processing mode to be used during the hydration process.
     * @return mixed
     */
    public function execute($params = array(), $hydrationMode = null)
    {
        if ($this->_em->getUnitOfWork()->hasPendingInsertions()) {
            $this->_em->flush();
        }

        if ($hydrationMode !== null) {
            $this->_hydrationMode = $hydrationMode;
        }

        $params = $this->getParams($params);

        // Check result cache (Only for SELECT queries)
        if ($this->_resultCache && $this->_type === self::SELECT) {
            $cacheDriver = $this->getResultCacheDriver();

            // Calculate hash for DQL query.
            $hash = md5($this->getDql() . var_export($params, true));
            $cached = ($this->_expireResultCache) ? false : $cacheDriver->fetch($hash);

            if ($cached === false) {
                // Cache miss.
                $result = $this->_doExecute($params);
                $queryResult = CacheHandler::fromResultSet($this, $result);
                $cacheDriver->save($hash, $queryResult->toCachedForm(), $this->_resultCacheTTL);

                return $result;
            } else {
                // Cache hit.
                $queryResult = CacheHandler::fromCachedResult($this, $cached);

                return $queryResult->getResultSet();
            }
        }

        $stmt = $this->_doExecute($params);

        if (is_integer($stmt)) {
            return $stmt;
        }

        return $this->_em->getHydrator($this->_hydrationMode)->hydrateAll($stmt, $this->_resultSetMapping);
    }

    /**
     * @nodoc
     */
    protected function _prepareParams(array $params)
    {
        // Convert boolean params
        $params = $this->_em->getConnection()->getDatabasePlatform()->convertBooleans($params);

        // Convert enum params
        return $this->convertEnums($params);
    }

    /**
     * Executes the query and returns a reference to the resulting Statement object.
     *
     * @param array $params
     */
    abstract protected function _doExecute(array $params);
}
