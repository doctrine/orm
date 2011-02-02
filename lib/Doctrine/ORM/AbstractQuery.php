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

use Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Query\QueryException;

/**
 * Base contract for ORM queries. Base class for Query and NativeQuery.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
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
     * @var array The parameter map of this query.
     */
    protected $_params = array();

    /**
     * @var array The parameter type map of this query.
     */
    protected $_paramTypes = array();

    /**
     * @var ResultSetMapping The user-specified ResultSetMapping to use.
     */
    protected $_resultSetMapping;

    /**
     * @var Doctrine\ORM\EntityManager The entity manager used by this query object.
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
     * The locally set cache driver used for caching result sets of this query.
     *
     * @var CacheDriver
     */
    protected $_resultCacheDriver;

    /**
     * Boolean flag for whether or not to cache the results of this query.
     *
     * @var boolean
     */
    protected $_useResultCache;

    /**
     * @var string The id to store the result cache entry under.
     */
    protected $_resultCacheId;

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
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
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
     *
     * Resets Parameters, Parameter Types and Query Hints.
     *
     * @return void
     */
    public function free()
    {
        $this->_params = array();
        $this->_paramTypes = array();
        $this->_hints = array();
    }

    /**
     * Get all defined parameters.
     *
     * @return array The defined query parameters.
     */
    public function getParameters()
    {
        return $this->_params;
    }

    /**
     * Gets a query parameter.
     *
     * @param mixed $key The key (index or name) of the bound parameter.
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : null;
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
     * Sets a query parameter.
     *
     * @param string|integer $key The parameter position or name.
     * @param mixed $value The parameter value.
     * @param string $type The parameter type. If specified, the given value will be run through
     *                     the type conversion of this type. This is usually not needed for
     *                     strings and numeric types.
     * @return Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setParameter($key, $value, $type = null)
    {
        if ($type !== null) {
            $this->_paramTypes[$key] = $type;
        }
        $this->_params[$key] = $value;
        return $this;
    }

    /**
     * Sets a collection of query parameters.
     *
     * @param array $params
     * @param array $types
     * @return Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setParameters(array $params, array $types = array())
    {
        foreach ($params as $key => $value) {
            if (isset($types[$key])) {
                $this->setParameter($key, $value, $types[$key]);
            } else {
                $this->setParameter($key, $value);
            }
        }
        return $this;
    }

    /**
     * Sets the ResultSetMapping that should be used for hydration.
     *
     * @param ResultSetMapping $rsm
     * @return Doctrine\ORM\AbstractQuery
     */
    public function setResultSetMapping(Query\ResultSetMapping $rsm)
    {
        $this->_resultSetMapping = $rsm;
        return $this;
    }

    /**
     * Defines a cache driver to be used for caching result sets.
     *
     * @param Doctrine\Common\Cache\Cache $driver Cache driver
     * @return Doctrine\ORM\AbstractQuery
     */
    public function setResultCacheDriver($resultCacheDriver = null)
    {
        if ($resultCacheDriver !== null && ! ($resultCacheDriver instanceof \Doctrine\Common\Cache\Cache)) {
            throw ORMException::invalidResultCacheDriver();
        }
        $this->_resultCacheDriver = $resultCacheDriver;
        if ($resultCacheDriver) {
            $this->_useResultCache = true;
        }
        return $this;
    }

    /**
     * Returns the cache driver used for caching result sets.
     *
     * @return Doctrine\Common\Cache\Cache Cache driver
     */
    public function getResultCacheDriver()
    {
        if ($this->_resultCacheDriver) {
            return $this->_resultCacheDriver;
        } else {
            return $this->_em->getConfiguration()->getResultCacheImpl();
        }
    }

    /**
     * Set whether or not to cache the results of this query and if so, for
     * how long and which ID to use for the cache entry.
     *
     * @param boolean $bool
     * @param integer $timeToLive
     * @param string $resultCacheId
     * @return This query instance.
     */
    public function useResultCache($bool, $timeToLive = null, $resultCacheId = null)
    {
        $this->_useResultCache = $bool;
        if ($timeToLive) {
            $this->setResultCacheLifetime($timeToLive);
        }
        if ($resultCacheId) {
            $this->_resultCacheId = $resultCacheId;
        }
        return $this;
    }

    /**
     * Defines how long the result cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid.
     * @return Doctrine\ORM\AbstractQuery This query instance.
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
     * @return integer
     */
    public function getResultCacheLifetime()
    {
        return $this->_resultCacheTTL;
    }

    /**
     * Defines if the result cache is active or not.
     *
     * @param boolean $expire Whether or not to force resultset cache expiration.
     * @return Doctrine\ORM\AbstractQuery This query instance.
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
     * Defines the processing mode to be used during hydration / result set transformation.
     *
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Query::HYDRATE_* constants.
     * @return Doctrine\ORM\AbstractQuery This query instance.
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
     * Alias for execute(array(), $hydrationMode = HYDRATE_OBJECT).
     *
     * @return array
     */
    public function getResult($hydrationMode = self::HYDRATE_OBJECT)
    {
        return $this->execute(array(), $hydrationMode);
    }

    /**
     * Gets the array of results for the query.
     *
     * Alias for execute(array(), HYDRATE_ARRAY).
     *
     * @return array
     */
    public function getArrayResult()
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
        $result = $this->execute(array(), $hydrationMode);

        if ($this->_hydrationMode !== self::HYDRATE_SINGLE_SCALAR && ! $result) {
            throw new NoResultException;
        }

        if (is_array($result)) {
            if (count($result) > 1) {
                throw new NonUniqueResultException;
            }
            return array_shift($result);
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
     * Sets a query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name The name of the hint.
     * @param mixed $value The value of the hint.
     * @return Doctrine\ORM\AbstractQuery
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
     * @param array $params The query parameters.
     * @param integer $hydrationMode The hydration mode to use.
     * @return IterableResult
     */
    public function iterate(array $params = array(), $hydrationMode = self::HYDRATE_OBJECT)
    {
        return $this->_em->newHydrator($this->_hydrationMode)->iterate(
            $this->_doExecute($params, $hydrationMode), $this->_resultSetMapping, $this->_hints
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
        if ($hydrationMode !== null) {
            $this->setHydrationMode($hydrationMode);
        }

        if ($params) {
            $this->setParameters($params);
        }

        if (isset($this->_params[0])) {
            throw QueryException::invalidParameterPosition(0);
        }

        // Check result cache
        if ($this->_useResultCache && $cacheDriver = $this->getResultCacheDriver()) {
            list($id, $hash) = $this->getResultCacheId();
            $cached = $this->_expireResultCache ? false : $cacheDriver->fetch($id);

            if ($cached === false || !isset($cached[$id])) {
                // Cache miss.
                $stmt = $this->_doExecute();

                $result = $this->_em->getHydrator($this->_hydrationMode)->hydrateAll(
                        $stmt, $this->_resultSetMapping, $this->_hints
                        );

                $cacheDriver->save($id, $result, $this->_resultCacheTTL);

                return $result;
            } else {
                // Cache hit.
                return $cached[$id];
            }
        }

        $stmt = $this->_doExecute();

        if (is_numeric($stmt)) {
            return $stmt;
        }

        return $this->_em->getHydrator($this->_hydrationMode)->hydrateAll(
                $stmt, $this->_resultSetMapping, $this->_hints
                );
    }

    /**
     * Set the result cache id to use to store the result set cache entry.
     * If this is not explicitely set by the developer then a hash is automatically
     * generated for you.
     *
     * @param string $id
     * @return Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setResultCacheId($id)
    {
        $this->_resultCacheId = $id;
        return $this;
    }

    /**
     * Get the result cache id to use to store the result set cache entry.
     * Will return the configured id if it exists otherwise a hash will be
     * automatically generated for you.
     *
     * @return array ($id, $hash)
     */
    protected function getResultCacheId()
    {
        if ($this->_resultCacheId) {
            return array($this->_resultCacheId, $this->_resultCacheId);
        } else {
            $params = $this->_params;
            foreach ($params AS $key => $value) {
                if (is_object($value) && $this->_em->getMetadataFactory()->hasMetadataFor(get_class($value))) {
                    if ($this->_em->getUnitOfWork()->getEntityState($value) == UnitOfWork::STATE_MANAGED) {
                        $idValues = $this->_em->getUnitOfWork()->getEntityIdentifier($value);
                    } else {
                        $class = $this->_em->getClassMetadata(get_class($value));
                        $idValues = $class->getIdentifierValues($value);
                    }
                    $params[$key] = $idValues;
                } else {
                    $params[$key] = $value;
                }
            }

            $sql = $this->getSql();
            ksort($this->_hints);
            $key = implode(";", (array)$sql) . var_export($params, true) .
                var_export($this->_hints, true)."&hydrationMode=".$this->_hydrationMode;
            return array($key, md5($key));
        }
    }

    /**
     * Executes the query and returns a the resulting Statement object.
     *
     * @return Doctrine\DBAL\Driver\Statement The executed database statement that holds the results.
     */
    abstract protected function _doExecute();

    /**
     * Cleanup Query resource when clone is called.
     *
     * @return void
     */
    public function __clone()
    {
        $this->_params = array();
        $this->_paramTypes = array();
        $this->_hints = array();
    }
}
