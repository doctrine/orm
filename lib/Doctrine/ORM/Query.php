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

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @var string
     */
    const HINT_CACHE_ENABLED = 'doctrine.cache.enabled';

    /**
     * @var string
     */
    const HINT_CACHE_EVICT = 'doctrine.cache.evict';

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
     * The current state of this query.
     *
     * @var integer
     */
    private $state = self::STATE_CLEAN;

    /**
     * A snapshot of the parameter types the query was parsed with.
     *
     * @var array
     */
    private $parsedTypes = [];

    /**
     * Cached DQL query.
     *
     * @var string
     */
    private $dql = null;

    /**
     * The parser result that holds DQL => SQL information.
     *
     * @var \Doctrine\ORM\Query\ParserResult
     */
    private $parserResult;

    /**
     * The first result to return (the "offset").
     *
     * @var integer
     */
    private $firstResult = null;

    /**
     * The maximum number of results to return (the "limit").
     *
     * @var integer
     */
    private $maxResults = null;

    /**
     * The cache driver used for caching queries.
     *
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private $queryCache;

    /**
     * Whether or not expire the query cache.
     *
     * @var boolean
     */
    private $expireQueryCache = false;

    /**
     * The query cache lifetime.
     *
     * @var int
     */
    private $queryCacheTTL;

    /**
     * Whether to use a query cache, if available. Defaults to TRUE.
     *
     * @var boolean
     */
    private $useQueryCache = true;

    /**
     * Gets the SQL query/queries that correspond to this DQL query.
     *
     * @return mixed The built sql query or an array of all sql queries.
     *
     * @override
     */
    public function getSQL()
    {
        return $this->parse()->getSQLExecutor()->getSQLStatements();
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
     * {@inheritdoc}
     */
    protected function getResultSetMapping()
    {
        // parse query or load from cache
        if ($this->resultSetMapping === null) {
            $this->resultSetMapping = $this->parse()->getResultSetMapping();
        }

        return $this->resultSetMapping;
    }

    /**
     * Parses the DQL query, if necessary, and stores the parser result.
     *
     * Note: Populates $this->parserResult as a side-effect.
     *
     * @return \Doctrine\ORM\Query\ParserResult
     */
    private function parse()
    {
        $types = [];

        foreach ($this->parameters as $parameter) {
            /** @var Query\Parameter $parameter */
            $types[$parameter->getName()] = $parameter->getType();
        }

        // Return previous parser result if the query and the filter collection are both clean
        if ($this->state === self::STATE_CLEAN && $this->parsedTypes === $types && $this->em->isFiltersStateClean()) {
            return $this->parserResult;
        }

        $this->state = self::STATE_CLEAN;
        $this->parsedTypes = $types;

        // Check query cache.
        if ( ! ($this->useQueryCache && ($queryCache = $this->getQueryCacheDriver()))) {
            $parser = new Parser($this);

            $this->parserResult = $parser->parse();

            return $this->parserResult;
        }

        $hash   = $this->getQueryCacheId();
        $cached = $this->expireQueryCache ? false : $queryCache->fetch($hash);

        if ($cached instanceof ParserResult) {
            // Cache hit.
            $this->parserResult = $cached;

            return $this->parserResult;
        }

        // Cache miss.
        $parser = new Parser($this);

        $this->parserResult = $parser->parse();

        $queryCache->save($hash, $this->parserResult, $this->queryCacheTTL);

        return $this->parserResult;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute()
    {
        $executor = $this->parse()->getSqlExecutor();

        if ($this->queryCacheProfile) {
            $executor->setQueryCacheProfile($this->queryCacheProfile);
        } else {
            $executor->removeQueryCacheProfile();
        }

        if ($this->resultSetMapping === null) {
            $this->resultSetMapping = $this->parserResult->getResultSetMapping();
        }

        // Prepare parameters
        $paramMappings = $this->parserResult->getParameterMappings();
        $paramCount = count($this->parameters);
        $mappingCount = count($paramMappings);

        if ($paramCount > $mappingCount) {
            throw QueryException::tooManyParameters($mappingCount, $paramCount);
        }

        if ($paramCount < $mappingCount) {
            throw QueryException::tooFewParameters($mappingCount, $paramCount);
        }

        // evict all cache for the entity region
        if ($this->hasCache && isset($this->hints[self::HINT_CACHE_EVICT]) && $this->hints[self::HINT_CACHE_EVICT]) {
            $this->evictEntityCacheRegion();
        }

        list($sqlParams, $types) = $this->processParameterMappings($paramMappings);

        $this->evictResultSetCache(
            $executor,
            $sqlParams,
            $types,
            $this->em->getConnection()->getParams()
        );

        return $executor->execute($this->em->getConnection(), $sqlParams, $types);
    }

    private function evictResultSetCache(
        AbstractSqlExecutor $executor,
        array $sqlParams,
        array $types,
        array $connectionParams
    ) {
        if (null === $this->_queryCacheProfile || ! $this->getExpireResultCache()) {
            return;
        }

        $cacheDriver = $this->_queryCacheProfile->getResultCacheDriver();
        $statements  = (array) $executor->getSqlStatements(); // Type casted since it can either be a string or an array

        foreach ($statements as $statement) {
            $cacheKeys = $this->_queryCacheProfile->generateCacheKeys($statement, $sqlParams, $types, $connectionParams);

            $cacheDriver->delete(reset($cacheKeys));
        }
    }

    /**
     * Evict entity cache region
     */
    private function evictEntityCacheRegion()
    {
        $AST = $this->getAST();

        if ($AST instanceof \Doctrine\ORM\Query\AST\SelectStatement) {
            throw new QueryException('The hint "HINT_CACHE_EVICT" is not valid for select statements.');
        }

        $className = ($AST instanceof \Doctrine\ORM\Query\AST\DeleteStatement)
            ? $AST->deleteClause->abstractSchemaName
            : $AST->updateClause->abstractSchemaName;

        $this->em->getCache()->evictEntityRegion($className);
    }

    /**
     * Processes query parameter mappings.
     *
     * @param array $paramMappings
     *
     * @return array
     *
     * @throws Query\QueryException
     */
    private function processParameterMappings($paramMappings)
    {
        $sqlParams = [];
        $types     = [];

        foreach ($this->parameters as $parameter) {
            $key   = $parameter->getName();
            $value = $parameter->getValue();

            if ( ! isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            $value = $this->processParameterValue($value);
            $type  = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : ParameterTypeInferer::inferType($value);

            foreach ($paramMappings[$key] as $position) {
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];

            // optimized multi value sql positions away for now,
            // they are not allowed in DQL anyways.
            $value = [$value];
            $countValue = count($value);

            for ($i = 0, $l = count($sqlPositions); $i < $l; $i++) {
                $sqlParams[$sqlPositions[$i]] = $value[($i % $countValue)];
            }
        }

        if (count($sqlParams) != count($types)) {
            throw QueryException::parameterTypeMismatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return [$sqlParams, $types];
    }

    /**
     * Defines a cache driver to be used for caching queries.
     *
     * @param \Doctrine\Common\Cache\Cache|null $queryCache Cache driver.
     *
     * @return Query This query instance.
     */
    public function setQueryCacheDriver($queryCache)
    {
        $this->queryCache = $queryCache;

        return $this;
    }

    /**
     * Defines whether the query should make use of a query cache, if available.
     *
     * @param boolean $bool
     *
     * @return Query This query instance.
     */
    public function useQueryCache($bool)
    {
        $this->useQueryCache = $bool;

        return $this;
    }

    /**
     * Returns the cache driver used for query caching.
     *
     * @return \Doctrine\Common\Cache\Cache|null The cache driver used for query caching or NULL, if
     *                                           this Query does not use query caching.
     */
    public function getQueryCacheDriver()
    {
        if ($this->queryCache) {
            return $this->queryCache;
        }

        return $this->em->getConfiguration()->getQueryCacheImpl();
    }

    /**
     * Defines how long the query cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid.
     *
     * @return Query This query instance.
     */
    public function setQueryCacheLifetime($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }

        $this->queryCacheTTL = $timeToLive;

        return $this;
    }

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @return int
     */
    public function getQueryCacheLifetime()
    {
        return $this->queryCacheTTL;
    }

    /**
     * Defines if the query cache is active or not.
     *
     * @param boolean $expire Whether or not to force query cache expiration.
     *
     * @return Query This query instance.
     */
    public function expireQueryCache($expire = true)
    {
        $this->expireQueryCache = $expire;

        return $this;
    }

    /**
     * Retrieves if the query cache is active or not.
     *
     * @return bool
     */
    public function getExpireQueryCache()
    {
        return $this->expireQueryCache;
    }

    /**
     * @override
     */
    public function free()
    {
        parent::free();

        $this->dql = null;
        $this->state = self::STATE_CLEAN;
    }

    /**
     * Sets a DQL query string.
     *
     * @param string $dqlQuery DQL Query.
     *
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setDQL($dqlQuery)
    {
        if ($dqlQuery !== null) {
            $this->dql = $dqlQuery;
            $this->state = self::STATE_DIRTY;
        }

        return $this;
    }

    /**
     * Returns the DQL query that is represented by this query object.
     *
     * @return string DQL query.
     */
    public function getDQL()
    {
        return $this->dql;
    }

    /**
     * Returns the state of this query object
     * By default the type is Doctrine_ORM_Query_Abstract::STATE_CLEAN but if it appears any unprocessed DQL
     * part, it is switched to Doctrine_ORM_Query_Abstract::STATE_DIRTY.
     *
     * @see AbstractQuery::STATE_CLEAN
     * @see AbstractQuery::STATE_DIRTY
     *
     * @return integer The query state.
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Method to check if an arbitrary piece of DQL exists
     *
     * @param string $dql Arbitrary piece of DQL to check for.
     *
     * @return boolean
     */
    public function contains($dql)
    {
        return stripos($this->getDQL(), $dql) !== false;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     *
     * @return Query This query object.
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
        $this->state       = self::STATE_DIRTY;

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
        return $this->firstResult;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults
     *
     * @return Query This query object.
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
        $this->state      = self::STATE_DIRTY;

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
        return $this->maxResults;
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterated over the result.
     *
     * @param ArrayCollection|array|null $parameters    The query parameters.
     * @param integer                    $hydrationMode The hydration mode to use.
     *
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
        $this->state = self::STATE_DIRTY;

        return parent::setHint($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->state = self::STATE_DIRTY;

        return parent::setHydrationMode($hydrationMode);
    }

    /**
     * Set the lock mode for this Query.
     *
     * @see \Doctrine\DBAL\LockMode
     *
     * @param int $lockMode
     *
     * @return Query
     *
     * @throws TransactionRequiredException
     */
    public function setLockMode($lockMode)
    {
        if (in_array($lockMode, [LockMode::NONE, LockMode::PESSIMISTIC_READ, LockMode::PESSIMISTIC_WRITE], true)) {
            if ( ! $this->em->getConnection()->isTransactionActive()) {
                throw TransactionRequiredException::transactionRequired();
            }
        }

        $this->setHint(self::HINT_LOCK_MODE, $lockMode);

        return $this;
    }

    /**
     * Get the current lock mode for this query.
     *
     * @return int|null The current lock mode of this query or NULL if no specific lock mode is set.
     */
    public function getLockMode()
    {
        $lockMode = $this->getHint(self::HINT_LOCK_MODE);

        if (false === $lockMode) {
            return null;
        }

        return $lockMode;
    }

    /**
     * Generate a cache id for the query cache - reusing the Result-Cache-Id generator.
     *
     * @return string
     */
    protected function getQueryCacheId()
    {
        ksort($this->hints);

        $platform = $this->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform()
            ->getName();

        return md5(
            $this->getDQL() . serialize($this->hints) .
            '&platform=' . $platform .
            ($this->em->hasFilters() ? $this->em->getFilters()->getHash() : '') .
            '&firstResult=' . $this->firstResult . '&maxResult=' . $this->maxResults .
            '&hydrationMode=' . $this->hydrationMode . '&types=' . serialize($this->parsedTypes) . 'DOCTRINE_QUERY_CACHE_SALT'
        );
    }

     /**
     * {@inheritdoc}
     */
    protected function getHash()
    {
        return sha1(parent::getHash(). '-'. $this->firstResult . '-' . $this->maxResults);
    }

    /**
     * Cleanup Query resource when clone is called.
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        $this->state = self::STATE_DIRTY;
    }
}
