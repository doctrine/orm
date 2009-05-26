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

use Doctrine\ORM\Query\CacheHandler;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;

/**
 * A Query object represents a DQL query.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 3938 $
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
final class Query extends AbstractQuery
{
    /**
     * A query object is in CLEAN state when it has NO unparsed/unprocessed DQL parts.
     */
    const STATE_CLEAN  = 1;

    /**
     * A query object is in state DIRTY when it has DQL parts that have not yet been
     * parsed/processed. This is automatically defined as DIRTY when addDqlQueryPart
     * is called.
     */
    const STATE_DIRTY  = 2;

    /**
     * @var integer $_state   The current state of this query.
     */
    private $_state = self::STATE_CLEAN;

    /**
     * @var string $_dql Cached DQL query.
     */
    private $_dql = null;

    /**
     * @var Doctrine\ORM\Query\ParserResult  The parser result that holds DQL => SQL information.
     */
    private $_parserResult;

    /**
     * @var CacheDriver  The cache driver used for caching queries.
     */
    private $_queryCache;

    /**
     * @var boolean Boolean value that indicates whether or not expire the query cache.
     */
    private $_expireQueryCache = false;

    /**
     * @var int Query Cache lifetime.
     */
    private $_queryCacheTTL;

    // End of Caching Stuff

    /**
     * Initializes a new Query instance.
     *
     * @param Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager);
    }

    /**
     * Gets the SQL query/queries that correspond to this DQL query.
     *
     * @return mixed The built sql query or an array of all sql queries.
     * @override
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
     * _execute
     *
     * @param array $params
     * @return PDOStatement  The executed PDOStatement.
     * @override
     */
    protected function _doExecute(array $params)
    {
        // If there is a CacheDriver associated to cache queries...
        if ($queryCache = $this->getQueryCacheDriver()) {
            // Calculate hash for dql query.
            $hash = md5($this->getDql() . 'DOCTRINE_QUERY_CACHE_SALT');
            $cached = ($this->_expireQueryCache) ? false : $queryCache->fetch($hash);

            if ($cached === false) {
                // Cache miss.
                $executor = $this->parse()->getSqlExecutor();
                $queryCache->save($hash, serialize($this->_parserResult), null);
            } else {
                // Cache hit.
                $this->_parserResult = unserialize($cached);
                $executor = $this->_parserResult->getSqlExecutor();
            }
        } else {
            $executor = $this->parse()->getSqlExecutor();
        }

        // Converting parameters
        $params = $this->_prepareParams($params);

        if ( ! $this->_resultSetMapping) {
            $this->_resultSetMapping = $this->_parserResult->getResultSetMapping();
        }

        // Executing the query and returning statement
        return $executor->execute($this->_em->getConnection(), $params);
    }

    /**
     * Defines a cache driver to be used for caching queries.
     *
     * @param Doctrine_Cache_Interface|null $driver Cache driver
     * @return Doctrine_ORM_Query
     */
    public function setQueryCacheDriver($queryCache)
    {
        $this->_queryCache = $queryCache;
        return $this;
    }

    /**
     * Returns the cache driver used for query caching.
     *
     * @return CacheDriver The cache driver used for query caching or NULL, if this
     * 					   Query does not use query caching.
     */
    public function getQueryCacheDriver()
    {
        if ($this->_queryCache) {
            return $this->_queryCache;
        } else {
            return $this->_em->getConfiguration()->getQueryCacheImpl();
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
        $this->_expireQueryCache = $expire;

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
     * @override
     */
    public function free()
    {
        parent::free();
        $this->_dql = null;
        $this->_state = self::STATE_CLEAN;
    }

    /**
     * Sets a DQL query string.
     *
     * @param string $dqlQuery DQL Query
     */
    public function setDql($dqlQuery)
    {
        $this->free();
        if ($dqlQuery !== null) {
            $this->_dql = $dqlQuery;
            $this->_state = self::STATE_DIRTY;
        }
    }

    /**
     * Returns the DQL query that is represented by this query object.
     *
     * @return string DQL query
     */
    public function getDql()
    {
        return $this->_dql;
    }

    /**
     * Returns the state of this query object
     * By default the type is Doctrine_ORM_Query_Abstract::STATE_CLEAN but if it appears any unprocessed DQL
     * part, it is switched to Doctrine_ORM_Query_Abstract::STATE_DIRTY.
     *
     * @see AbstractQuery::STATE_CLEAN
     * @see AbstractQuery::STATE_DIRTY
     *
     * @return integer Return the query state
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Method to check if an arbitrary piece of DQL exists
     *
     * @param string $dql Arbitrary piece of DQL to check for
     * @return boolean
     */
    public function contains($dql)
    {
      return stripos($this->getDql(), $dql) === false ? false : true;
    }
}