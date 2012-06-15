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

use Doctrine\DBAL\LockMode;

use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;

/**
 * A Query object represents a DQL query.
 *
 * @since   1.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author  Roman Borschel <roman@code-factory.org>
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
    const STATE_DIRTY = 2;

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
     * @var string
     */
    const HINT_LOCK_MODE = 'doctrine.lockMode';


    /**
     * @var integer $_state   The current state of this query.
     */
    private $_state = self::STATE_CLEAN;

    /**
     * @var string $_dql Cached DQL query.
     */
    private $_dql = null;

    /**
     * @var \Doctrine\ORM\Query\ParserResult  The parser result that holds DQL => SQL information.
     */
    private $_parserResult;

    /**
     * @var integer The first result to return (the "offset").
     */
    private $_firstResult = null;

    /**
     * @var integer The maximum number of results to return (the "limit").
     */
    private $_maxResults = null;

    /**
     * @var CacheDriver The cache driver used for caching queries.
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

    /**
     * @var boolean Whether to use a query cache, if available. Defaults to TRUE.
     */
    private $_useQueryCache = true;

    /**
     * Initializes a new Query instance.
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    /*public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager);
    }*/

    /**
     * Gets the SQL query/queries that correspond to this DQL query.
     *
     * @return mixed The built sql query or an array of all sql queries.
     * @override
     */
    public function getSQL()
    {
        return $this->_parse()->getSQLExecutor()->getSQLStatements();
    }

    /**
     * Returns the corresponding AST for this DQL query.
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement |
     *         \Doctrine\ORM\Query\AST\UpdateStatement |
     *         \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function getAST()
    {
        $parser = new Parser($this);

        return $parser->getAST();
    }

    /**
     * Parses the DQL query, if necessary, and stores the parser result.
     *
     * Note: Populates $this->_parserResult as a side-effect.
     *
     * @return \Doctrine\ORM\Query\ParserResult
     */
    private function _parse()
    {
        // Return previous parser result if the query and the filter collection are both clean
        if ($this->_state === self::STATE_CLEAN && $this->_em->isFiltersStateClean()) {
            return $this->_parserResult;
        }

        $this->_state = self::STATE_CLEAN;

        // Check query cache.
        if ( ! ($this->_useQueryCache && ($queryCache = $this->getQueryCacheDriver()))) {
            $parser = new Parser($this);

            $this->_parserResult = $parser->parse();

            return $this->_parserResult;
        }

        $hash   = $this->_getQueryCacheId();
        $cached = $this->_expireQueryCache ? false : $queryCache->fetch($hash);

        if ($cached instanceof ParserResult) {
            // Cache hit.
            $this->_parserResult = $cached;

            return $this->_parserResult;
        }

        // Cache miss.
        $parser = new Parser($this);

        $this->_parserResult = $parser->parse();

        $queryCache->save($hash, $this->_parserResult, $this->_queryCacheTTL);

        return $this->_parserResult;
    }

    /**
     * {@inheritdoc}
     */
    protected function _doExecute()
    {
        $executor = $this->_parse()->getSqlExecutor();

        if ($this->_queryCacheProfile) {
            $executor->setQueryCacheProfile($this->_queryCacheProfile);
        }

        // Prepare parameters
        $paramMappings = $this->_parserResult->getParameterMappings();

        if (count($paramMappings) != count($this->parameters)) {
            throw QueryException::invalidParameterNumber();
        }

        list($sqlParams, $types) = $this->processParameterMappings($paramMappings);

        if ($this->_resultSetMapping === null) {
            $this->_resultSetMapping = $this->_parserResult->getResultSetMapping();
        }

        return $executor->execute($this->_em->getConnection(), $sqlParams, $types);
    }

    /**
     * Processes query parameter mappings
     *
     * @param array $paramMappings
     * @return array
     */
    private function processParameterMappings($paramMappings)
    {
        $sqlParams = array();
        $types     = array();

        foreach ($this->parameters as $parameter) {
            $key = $parameter->getName();

            if ( ! isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            $value = $this->processParameterValue($parameter->getValue());
            $type  = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : Query\ParameterTypeInferer::inferType($value);

            foreach ($paramMappings[$key] as $position) {
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];

            // optimized multi value sql positions away for now,
            // they are not allowed in DQL anyways.
            $value = array($value);
            $countValue = count($value);

            for ($i = 0, $l = count($sqlPositions); $i < $l; $i++) {
                $sqlParams[$sqlPositions[$i]] = $value[($i % $countValue)];
            }
        }

        if (count($sqlParams) != count($types)) {
            throw QueryException::parameterTypeMissmatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return array($sqlParams, $types);
    }

    /**
     * Defines a cache driver to be used for caching queries.
     *
     * @param Doctrine_Cache_Interface|null $driver Cache driver
     * @return Query This query instance.
     */
    public function setQueryCacheDriver($queryCache)
    {
        $this->_queryCache = $queryCache;

        return $this;
    }

    /**
     * Defines whether the query should make use of a query cache, if available.
     *
     * @param boolean $bool
     * @return @return Query This query instance.
     */
    public function useQueryCache($bool)
    {
        $this->_useQueryCache = $bool;

        return $this;
    }

    /**
     * Returns the cache driver used for query caching.
     *
     * @return CacheDriver The cache driver used for query caching or NULL, if
     *                     this Query does not use query caching.
     */
    public function getQueryCacheDriver()
    {
        if ($this->_queryCache) {
            return $this->_queryCache;
        }

        return $this->_em->getConfiguration()->getQueryCacheImpl();
    }

    /**
     * Defines how long the query cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid
     * @return Query This query instance.
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
     * @return Query This query instance.
     */
    public function expireQueryCache($expire = true)
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
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setDQL($dqlQuery)
    {
        if ($dqlQuery !== null) {
            $this->_dql = $dqlQuery;
            $this->_state = self::STATE_DIRTY;
        }

        return $this;
    }

    /**
     * Returns the DQL query that is represented by this query object.
     *
     * @return string DQL query
     */
    public function getDQL()
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
        return stripos($this->getDQL(), $dql) === false ? false : true;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     * @return Query This query object.
     */
    public function setFirstResult($firstResult)
    {
        $this->_firstResult = $firstResult;
        $this->_state       = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this query.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->_firstResult;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults
     * @return Query This query object.
     */
    public function setMaxResults($maxResults)
    {
        $this->_maxResults = $maxResults;
        $this->_state      = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query.
     *
     * @return integer Maximum number of results.
     */
    public function getMaxResults()
    {
        return $this->_maxResults;
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterated over the result.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|array $parameters The query parameters.
     * @param integer $hydrationMode The hydration mode to use.
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate($parameters = null, $hydrationMode = self::HYDRATE_OBJECT)
    {
        $this->setHint(self::HINT_INTERNAL_ITERATION, true);

        return parent::iterate($parameters, $hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function setHint($name, $value)
    {
        $this->_state = self::STATE_DIRTY;

        return parent::setHint($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->_state = self::STATE_DIRTY;

        return parent::setHydrationMode($hydrationMode);
    }

    /**
     * Set the lock mode for this Query.
     *
     * @see \Doctrine\DBAL\LockMode
     * @param  int $lockMode
     * @return Query
     */
    public function setLockMode($lockMode)
    {
        if (in_array($lockMode, array(LockMode::PESSIMISTIC_READ, LockMode::PESSIMISTIC_WRITE))) {
            if ( ! $this->_em->getConnection()->isTransactionActive()) {
                throw TransactionRequiredException::transactionRequired();
            }
        }

        $this->setHint(self::HINT_LOCK_MODE, $lockMode);

        return $this;
    }

    /**
     * Get the current lock mode for this query.
     *
     * @return int
     */
    public function getLockMode()
    {
        $lockMode = $this->getHint(self::HINT_LOCK_MODE);

        if ( ! $lockMode) {
            return LockMode::NONE;
        }

        return $lockMode;
    }

    /**
     * Generate a cache id for the query cache - reusing the Result-Cache-Id generator.
     *
     * The query cache
     *
     * @return string
     */
    protected function _getQueryCacheId()
    {
        ksort($this->_hints);

        return md5(
            $this->getDql() . var_export($this->_hints, true) .
            ($this->_em->hasFilters() ? $this->_em->getFilters()->getHash() : '') .
            '&firstResult=' . $this->_firstResult . '&maxResult=' . $this->_maxResults .
            '&hydrationMode='.$this->_hydrationMode.'DOCTRINE_QUERY_CACHE_SALT'
        );
    }

    /**
     * Cleanup Query resource when clone is called.
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        $this->_state = self::STATE_DIRTY;
    }
}
