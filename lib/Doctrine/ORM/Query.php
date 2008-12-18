<?php

/*
 *  $Id: Query.php 3938 2008-03-06 19:36:50Z romanb $
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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::ORM;

/**
 * A Doctrine_ORM_Query object represents a DQL query. It is used to query databases for
 * data in an object-oriented fashion. A DQL query understands relations and inheritance
 * and is dbms independant.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 3938 $
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_ORM_Query extends Doctrine_ORM_Query_Abstract
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
     * @var Doctrine_ORM_EntityManager The entity manager used by this query object.
     */
    protected $_entityManager;

    /**
     * @var Doctrine_ORM_Internal_Hydration   The hydrator object used to hydrate query results.
     */
    protected $_hydrator;

    /**
     * @var Doctrine_ORM_Query_ParserResult  The parser result that holds DQL => SQL information.
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
     * @param EntityManager $entityManager
     */
    public function __construct(Doctrine_ORM_EntityManager $entityManager)
    {
        $this->_entityManager = $entityManager;
        $this->_hydrator = new Doctrine_ORM_Internal_Hydration_StandardHydrator($entityManager);

        $this->free();
    }

    /**
     * Retrieves the assocated EntityManager to this Doctrine_ORM_Query
     *
     * @return Doctrine_EntityManager
     */
    public function getEntityManager()
    {
        return $this->_entityManager;
    }

    /**
     * Returns the hydrator associated with this query object
     *
     * @return Doctrine_ORM_Internal_Hydration The hydrator associated with this query object
     */
    public function getHydrator()
    {
        return $this->_hydrator;
    }

    /**
     * Convenience method to execute using array fetching as hydration mode.
     *
     * @param string $params
     * @return array
     */
    public function fetchArray($params = array()) {
        return $this->execute($params, self::HYDRATE_ARRAY);
    }

    /**
     * Convenience method to execute the query and return the first item
     * of the collection.
     *
     * @param string $params Parameters
     * @param int $hydrationMode Hydration mode
     * @return mixed Array or Doctrine_Collection or false if no result.
     */
    public function fetchOne($params = array(), $hydrationMode = null)
    {
        $collection = $this->limit(1)->execute($params, $hydrationMode);

        if (count($collection) === 0) {
            return false;
        }

        if ($collection instanceof Doctrine_ORM_Collection) {
            return $collection->getFirst();
        } else if (is_array($collection)) {
            return array_shift($collection);
        }

        return false;
    }

    /**
     * Query the database with DQL (Doctrine Query Language).
     *
     * @param string $query      DQL query
     * @param array $params      prepared statement parameters
     * @param int $hydrationMode Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
     * @see Doctrine::FETCH_* constants
     * @return mixed
     */
    public function query($query, $params = array(), $hydrationMode = null)
    {
        $this->setDql($query);
        return $this->execute($params, $hydrationMode);
    }

    /**
     * Builds the sql query from the given parameters and applies things such as
     * column aggregation inheritance and limit subqueries if needed
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
     * @return Doctrine_ORM_Query_ParserResult
     */
    public function parse()
    {
        if ($this->_state === self::STATE_DIRTY) {
            $parser = new Doctrine_ORM_Query_Parser($this);
            $this->_parserResult = $parser->parse();
            $this->_state = self::STATE_CLEAN;
        }

        return $this->_parserResult;
    }

    /**
     * Executes the query and populates the data set.
     *
     * @param string $params Parameters to be sent to query.
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Doctrine::HYDRATE_* constants.
     * @return Doctrine_Collection The root collection
     */
    public function execute($params = array(), $hydrationMode = null)
    {
        $params = $this->getParams($params);

        // If there is a CacheDriver associated to cache resultsets...
        if ($this->_resultCache && $this->_type === self::SELECT) { // Only executes if "SELECT"
            $cacheDriver = $this->getResultCacheDriver();

            // Calculate hash for dql query.
            $hash = md5($this->getDql() . var_export($params, true));
            $cached = ($this->_expireResultCache) ? false : $cacheDriver->fetch($hash);

            if ($cached === false) {
                // Cache does not exist, we have to create it.
                $result = $this->_execute($params, self::HYDRATE_ARRAY);
                $queryResult = Doctrine_ORM_Query_CacheHandler::fromResultSet($this, $result);
                $cacheDriver->save($hash, $queryResult->toCachedForm(), $this->_resultCacheTTL);

                return $result;
            } else {
                // Cache exists, recover it and return the results.
                $queryResult = Doctrine_ORM_Query_CacheHandler::fromCachedResult($this, $cached);

                return $queryResult->getResultSet();
            }
        }

        return $this->_execute($params, $hydrationMode);
    }

    /**
     * _execute
     *
     * @param string $params Parameters to be sent to query.
     * @param int $hydrationMode Method of hydration to be used.
     * @return Doctrine_Collection The root collection
     */
    protected function _execute($params, $hydrationMode)
    {
        // preQuery invoking
        $this->preQuery();

        // Query execution
        $stmt = $this->_execute2($params);

        // postQuery invoking
        $this->postQuery();

        if (is_integer($stmt)) {
            return $stmt;
        }

        return $this->_hydrator->hydrateResultSet($stmt, $hydrationMode);
    }

    /**
     * _execute2
     *
     * @param array $params
     * @return PDOStatement  The executed PDOStatement.
     */
    protected function _execute2($params)
    {
        // If there is a CacheDriver associated to cache queries...
        if ($this->_queryCache || $this->_entityManager->getConnection()->getAttribute(Doctrine::ATTR_QUERY_CACHE)) {
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
                $this->_parserResult = Doctrine_ORM_Query_CacheHandler::fromCachedQuery($this, $cached);

                $executor = $this->_parserResult->getSqlExecutor();
            }
        } else {
            $executor = $this->parse()->getSqlExecutor();
        }

        // Assignments for Hydrator and Enums
        $this->_hydrator->setQueryComponents($this->_parserResult->getQueryComponents());
        $this->_hydrator->setTableAliasMap($this->_parserResult->getTableAliasMap());
        $this->_setEnumParams($this->_parserResult->getEnumParams());

        // Converting parameters
        $params = $this->_prepareParams($params);

        // Double the params if we are using limit-subquery algorithm
        // We always have an instance of Doctrine_ORM_Query_ParserResult on hands...
        if ($this->_parserResult->isLimitSubqueryUsed() &&
            $this->_entityManager->getConnection()->getAttribute(Doctrine::ATTR_DRIVER_NAME) !== 'mysql') {
            $params = array_merge($params, $params);
        }

        // Executing the query and assigning PDOStatement        
        return $executor->execute($this->_conn, $params);
    }

    /**
     * @nodoc
     */
    protected function _prepareParams(array $params)
    {
        // Convert boolean params
        $params = $this->_entityManager->getConnection()->convertBooleans($params);

        // Convert enum params
        return $this->convertEnums($params);
    }

    /**
     * Defines a cache driver to be used for caching result sets.
     *
     * @param Doctrine_Cache_Interface|null $driver Cache driver
     * @return Doctrine_ORM_Query
     */
    public function setResultCache($resultCache)
    {
        if ($resultCache !== null && ! ($resultCache instanceof Doctrine_Cache_Interface)) {
            throw new Doctrine_ORM_Query_Exception(
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
        if ($this->_resultCache instanceof Doctrine_ORM_Cache_Interface) {
            return $this->_resultCache;
        } else {
            return $this->_entityManager->getConnection()->getResultCacheDriver();
        }
    }

    /**
     * Defines how long the result cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid
     * @return Doctrine_ORM_Query
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
        if ($queryCache !== null && ! ($queryCache instanceof Doctrine_ORM_Cache_Interface)) {
            throw new Doctrine_ORM_Query_Exception(
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
        if ($this->_queryCache instanceof Doctrine_ORM_Cache_Interface) {
            return $this->_queryCache;
        } else {
            return $this->_entityManager->getConnection()->getQueryCacheDriver();
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
     *                               One of the Doctrine::HYDRATE_* constants.
     * @return Doctrine_ORM_Query
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrator->setHydrationMode($hydrationMode);

        return $this;
    }

    /**
     * Empty template method to provide Query subclasses with the possibility
     * to hook into the query building procedure, doing any custom / specialized
     * query building procedures that are neccessary.
     *
     * @return void
     * @deprecated Should be removed. Extending Query is no good solution. Should
     *             Things like this should be done through listeners.
     */
    public function preQuery()
    {

    }

    /**
     * Empty template method to provide Query subclasses with the possibility
     * to hook into the query building procedure, doing any custom / specialized
     * post query procedures (for example logging) that are neccessary.
     *
     * @return void
     * @deprecated Should be removed. Extending Query is no good solution. Should
     *             Things like this should be done through listeners.
     */
    public function postQuery()
    {

    }
    
    /**
     * Gets the list of results for the query.
     *
     * @param integer $hydrationMode
     * @return mixed
     */
    public function getResultList($hydrationMode = null)
    {
        return $this->execute(array(), $hydrationMode);
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
            throw Doctrine_ORM_Query_Exception::nonUniqueResult();
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
     * This method is automatically called when this Doctrine_Hydrate is serialized.
     *
     * @return array An array of serialized properties
     */
    public function serialize()
    {
        $vars = get_object_vars($this);
    }

    /**
     * This method is automatically called everytime a Doctrine_Hydrate object is unserialized.
     *
     * @param string $serialized Doctrine_Record as serialized string
     * @return void
     */
    public function unserialize($serialized)
    {

    }
}
