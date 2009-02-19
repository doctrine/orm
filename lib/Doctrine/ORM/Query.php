<?php
/*
 *  $Id$
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

use Doctrine\ORM\Query\Parser;

/**
 * A Doctrine_ORM_Query object represents a DQL query. It is used to query databases for
 * data in an object-oriented fashion. A DQL query understands relations and inheritance
 * and is to a large degree dbms independant.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 3938 $
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Query extends AbstractQuery
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
     * @var Doctrine\ORM\EntityManager The entity manager used by this query object.
     */
    protected $_em;

    /**
     * @var integer The hydration mode.
     */
    protected $_hydrationMode = self::HYDRATE_OBJECT;

    /**
     * @var Doctrine\ORM\Query\ParserResult  The parser result that holds DQL => SQL information.
     */
    protected $_parserResult;

    /**
     * A set of query hints.
     *
     * @var array
     */
    protected $_hints = array();


    // Caching Stuff

    /**
     * @var Doctrine_Cache_Interface  The cache driver used for caching result sets.
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
     * @var Doctrine_Cache_Interface  The cache driver used for caching queries.
     */
    protected $_queryCache;

    /**
     * @var boolean Boolean value that indicates whether or not expire the query cache.
     */
    protected $_expireQueryCache = false;

    /**
     * @var int Query Cache lifetime.
     */
    protected $_queryCacheTTL;

    // End of Caching Stuff

    /**
     * Initializes a new instance of the Query class.
     *
     * @param Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->_em = $entityManager;
        $this->free();
    }

    /**
     * Retrieves the assocated EntityManager to this Query instance.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * Convenience method to execute using array fetching as hydration mode.
     *
     * @param string $params
     * @return array
     */
    public function fetchArray($params = array())
    {
        return $this->execute($params, self::HYDRATE_ARRAY);
    }

    /**
     * Convenience method to execute the query and return the first item
     * of the collection.
     *
     * @param string $params Parameters
     * @param int $hydrationMode Hydration mode
     * @return mixed Array or Doctrine\Common\Collection or false if no result.
     */
    public function fetchOne($params = array(), $hydrationMode = null)
    {
        $collection = $this->limit(1)->execute($params, $hydrationMode);

        if (count($collection) === 0) {
            return false;
        }

        if ($collection instanceof Collection) {
            return $collection->getFirst();
        } else if (is_array($collection)) {
            return array_shift($collection);
        }

        return false;
    }

    /**
     * Query the database with DQL (Doctrine Query Language).
     *
     * @param string $query      The DQL query.
     * @param array $params      The query parameters.
     * @param int $hydrationMode
     * @return mixed
     */
    public function query($query, $params = array(), $hydrationMode = null)
    {
        $this->setDql($query);
        return $this->execute($params, $hydrationMode);
    }

    /**
     * Gets the SQL query/queries that correspond to this DQL query.
     *
     * @return mixed The built sql query or an array of all sql queries.
     */
    public function getSql()
    {
        return $this->parse()->getSqlExecutor()->getSqlStatements();
    }

    /**
     * Parses the DQL query, if necessary, and stores the parser result.
     * 
     * Note: Populates $this->_parserResult as a side-effect.
     *
     * @return Doctrine\ORM\Query\ParserResult
     */
    public function parse()
    {
        if ($this->_state === self::STATE_DIRTY) {
            $parser = new Parser($this);
            $this->_parserResult = $parser->parse();
            $this->_state = self::STATE_CLEAN;
        }

        return $this->_parserResult;
    }

    /**
     * Executes the query.
     *
     * @param string $params Parameters to be sent to query.
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Query::HYDRATE_* constants.
     * @return mixed
     */
    public function execute($params = array(), $hydrationMode = null)
    {
        if ($hydrationMode !== null) {
            $this->_hydrationMode = $hydrationMode;
        }

        $params = $this->getParams($params);

        // Check result cache
        if ($this->_resultCache && $this->_type === self::SELECT) { // Only executes if "SELECT"
            $cacheDriver = $this->getResultCacheDriver();

            // Calculate hash for dql query.
            $hash = md5($this->getDql() . var_export($params, true));
            $cached = ($this->_expireResultCache) ? false : $cacheDriver->fetch($hash);

            if ($cached === false) {
                // Cache does not exist, we have to create it.
                $result = $this->_execute($params, self::HYDRATE_ARRAY);
                $queryResult = \Doctrine\ORM\Query\CacheHandler::fromResultSet($this, $result);
                $cacheDriver->save($hash, $queryResult->toCachedForm(), $this->_resultCacheTTL);

                return $result;
            } else {
                // Cache exists, recover it and return the results.
                $queryResult = \Doctrine\ORM\Query\CacheHandler::fromCachedResult($this, $cached);

                return $queryResult->getResultSet();
            }
        }
        
        $stmt = $this->_execute($params);

        if (is_integer($stmt)) {
            return $stmt;
        }

        return $this->_em->getHydrator($this->_hydrationMode)->hydrateAll($stmt, $this->_parserResult);
    }

    /**
     * _execute
     *
     * @param array $params
     * @return PDOStatement  The executed PDOStatement.
     */
    protected function _execute(array $params)
    {
        // If there is a CacheDriver associated to cache queries...
        if ($this->_queryCache || $this->_em->getConfiguration()->getQueryCacheImpl()) {
            $queryCacheDriver = $this->getQueryCacheDriver();

            // Calculate hash for dql query.
            $hash = md5($this->getDql() . 'DOCTRINE_QUERY_CACHE_SALT');
            $cached = ($this->_expireQueryCache) ? false : $queryCacheDriver->fetch($hash);

            if ($cached === false) {
                // Cache does not exist, we have to create it.
                $executor = $this->parse()->getSqlExecutor();

                // To-be cached item is parserResult
                $cacheDriver->save($hash, $this->_parserResult->toCachedForm(), $this->_queryCacheTTL);
            } else {
                // Cache exists, recover it and return the results.
                $this->_parserResult = Doctrine\ORM\Query\CacheHandler::fromCachedQuery($this, $cached);

                $executor = $this->_parserResult->getSqlExecutor();
            }
        } else {
            $executor = $this->parse()->getSqlExecutor();
        }

        // Assignments for Enums
        $this->_setEnumParams($this->_parserResult->getEnumParams());

        // Converting parameters
        $params = $this->_prepareParams($params);

        // Executing the query and returning statement
        return $executor->execute($this->_conn, $params);
    }

    /**
     * @nodoc
     */
    protected function _prepareParams(array $params)
    {
        // Convert boolean params
        $params = $this->_em->getConnection()->convertBooleans($params);

        // Convert enum params
        return $this->convertEnums($params);
    }

    /**
     * Defines a cache driver to be used for caching result sets.
     *
     * @param Doctrine\ORM\Cache\Cache $driver Cache driver
     * @return Doctrine\ORM\Query
     */
    public function setResultCache($resultCache)
    {
        if ($resultCache !== null && ! ($resultCache instanceof \Doctrine\ORM\Cache\Cache)) {
            \Doctrine\Common\DoctrineException::updateMe(
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
            return $this->_em->getConnection()->getResultCacheDriver();
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
     * Defines a cache driver to be used for caching queries.
     *
     * @param Doctrine_Cache_Interface|null $driver Cache driver
     * @return Doctrine_ORM_Query
     */
    public function setQueryCache($queryCache)
    {
        if ($queryCache !== null && ! ($queryCache instanceof \Doctrine\ORM\Cache\Cache)) {
            \Doctrine\Common\DoctrineException::updateMe(
                'Method setResultCache() accepts only an instance of Doctrine_ORM_Cache_Interface or null.'
            );
        }

        $this->_queryCache = $queryCache;

        return $this;
    }

    /**
     * Returns the cache driver used for caching queries.
     *
     * @return Doctrine_Cache_Interface Cache driver
     */
    public function getQueryCache()
    {
        if ($this->_queryCache instanceof \Doctrine\ORM\Cache\Cache) {
            return $this->_queryCache;
        } else {
            return $this->_em->getConnection()->getQueryCacheDriver();
        }
    }

    /**
     * Defines how long the query cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid
     * @return Doctrine_ORM_Query
     */
    public function setQueryCacheLifetime($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }

        $this->_queryCacheTTL = $timeToLive;

        return $this;
    }

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @return int
     */
    public function getQueryCacheLifetime()
    {
        return $this->_queryCacheTTL;
    }

    /**
     * Defines if the query cache is active or not.
     *
     * @param boolean $expire Whether or not to force query cache expiration.
     * @return Doctrine_ORM_Query
     */
    public function setExpireQueryCache($expire = true)
    {
        $this->_expireQueryCache = (bool) $expire;

        return $this;
    }

    /**
     * Retrieves if the query cache is active or not.
     *
     * @return bool
     */
    public function getExpireQueryCache()
    {
        return $this->_expireQueryCache;
    }

    /**
     * Defines the processing mode to be used during hydration process.
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
     * Object graphs are represented as nested array structures.
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
     * @throws QueryException  If the query result is not unique.
     */
    public function getSingleResult($hydrationMode = null)
    {
        $result = $this->execute(array(), $hydrationMode);
        if (count($result) > 1) {
            throw QueryException::nonUniqueResult();
        }   
        return is_array($result) ? array_shift($result) : $result->getFirst();
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
     */
    public function getHint($name)
    {
        return isset($this->_hints[$name]) ? $this->_hints[$name] : false;
    }

    /**
     * Executes the query and returns an IterableResult that can be iterated over.
     * Objects in the result are initialized on-demand.
     *
     * @return IterableResult
     */
    public function iterate(array $params = array(), $hydrationMode = self::HYDRATE_OBJECT)
    {
        return $this->_em->getHydrator($this->_hydrationMode)->iterate(
            $this->_execute($params, $hydrationMode), $this->_parserResult
        );
    }
}
