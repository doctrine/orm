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
class Query extends AbstractQuery
{
    /**
     * QUERY TYPE CONSTANTS
     */

    /**
     * Constant for SELECT queries.
     */
    const SELECT = 0;

    /**
     * Constant for DELETE queries.
     */
    const DELETE = 1;

    /**
     * Constant for UPDATE queries.
     */
    const UPDATE = 2;

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
     * @var integer $type Query type.
     *
     * @see Query::* constants
     */
    protected $_type = self::SELECT;

    /**
     * @var integer $_state   The current state of this query.
     */
    protected $_state = self::STATE_CLEAN;

    /**
     * @var array $_dqlParts An array containing all DQL query parts.
     * @see Query::free that initializes this property
     */
    protected $_dqlParts = array();

    /**
     * @var string $_dql Cached DQL query.
     */
    protected $_dql = null;

    /**
     * @var Doctrine\ORM\Query\ParserResult  The parser result that holds DQL => SQL information.
     */
    protected $_parserResult;

    /**
     * @var Doctrine_Cache_Interface  The cache driver used for caching queries.
     */
    //protected $_queryCache;

    /**
     * @var boolean Boolean value that indicates whether or not expire the query cache.
     */
    //protected $_expireQueryCache = false;

    /**
     * @var int Query Cache lifetime.
     */
    //protected $_queryCacheTTL;

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
        if ($queryCache = $this->_em->getConfiguration()->getQueryCacheImpl()) {
            // Calculate hash for dql query.
            $hash = md5($this->getDql() . 'DOCTRINE_QUERY_CACHE_SALT');
            $cached = ($this->_expireQueryCache) ? false : $queryCache->fetch($hash);

            if ($cached === false) {
                // Cache miss.
                $executor = $this->parse()->getSqlExecutor();
                $queryCache->save($hash, $this->_parserResult->toCachedForm(), null);
            } else {
                // Cache hit.
                $this->_parserResult = CacheHandler::fromCachedQuery($this, $cached);
                $executor = $this->_parserResult->getSqlExecutor();
            }
        } else {
            $executor = $this->parse()->getSqlExecutor();
        }

        // Assignments for Enums
        $this->_setEnumParams($this->_parserResult->getEnumParams());

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
    /*public function setQueryCache($queryCache)
    {
        if ($queryCache !== null && ! ($queryCache instanceof \Doctrine\ORM\Cache\Cache)) {
            throw DoctrineException::updateMe(
                'Method setResultCache() accepts only an instance of Doctrine_ORM_Cache_Interface or null.'
            );
        }

        $this->_queryCache = $queryCache;

        return $this;
    }*/

    /**
     * Returns the cache driver used for caching queries.
     *
     * @return Doctrine_Cache_Interface Cache driver
     */
    /*public function getQueryCache()
    {
        if ($this->_queryCache instanceof \Doctrine\ORM\Cache\Cache) {
            return $this->_queryCache;
        } else {
            return $this->_em->getConnection()->getQueryCacheDriver();
        }
    }*/

    /**
     * Defines how long the query cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid
     * @return Doctrine_ORM_Query
     */
    /*public function setQueryCacheLifetime($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }

        $this->_queryCacheTTL = $timeToLive;

        return $this;
    }*/

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @return int
     */
    /*public function getQueryCacheLifetime()
    {
        return $this->_queryCacheTTL;
    }*/

    /**
     * Defines if the query cache is active or not.
     *
     * @param boolean $expire Whether or not to force query cache expiration.
     * @return Doctrine_ORM_Query
     */
    /*public function setExpireQueryCache($expire = true)
    {
        $this->_expireQueryCache = (bool) $expire;

        return $this;
    }*/

    /**
     * Retrieves if the query cache is active or not.
     *
     * @return bool
     */
    /*public function getExpireQueryCache()
    {
        return $this->_expireQueryCache;
    }*/

    /**
     * @override
     */
    public function free()
    {
        parent::free();
        $this->_dqlParts = array(
            'select'    => array(),
            'distinct'  => false,
            'from'      => array(),
            'join'      => array(),
            'set'       => array(),
            'where'     => array(),
            'groupby'   => array(),
            'having'    => array(),
            'orderby'   => array(),
            'limit'     => array(),
            'offset'    => array(),
        );
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
        if ($this->_dql !== null) {
            return $this->_dql;
        }

        $dql = '';

        switch ($this->_type) {
            case self::DELETE:
                $dql = $this->_getDqlForDelete();
                break;

            case self::UPDATE:
                $dql = $this->_getDqlForUpdate();
                break;

            case self::SELECT:
            default:
                $dql = $this->_getDqlForSelect();
                break;
        }

        return $dql;
    }

    /**
     * Builds the DQL of DELETE
     */
    protected function _getDqlForDelete()
    {
        /*
         * BNF:
         *
         * DeleteStatement = DeleteClause [WhereClause] [OrderByClause] [LimitClause] [OffsetClause]
         * DeleteClause    = "DELETE" "FROM" RangeVariableDeclaration
         * WhereClause     = "WHERE" ConditionalExpression
         * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
         * LimitClause     = "LIMIT" integer
         * OffsetClause    = "OFFSET" integer
         *
         */
         return 'DELETE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' FROM ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('orderby', array('pre' => ' ORDER BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('limit', array('pre' => ' LIMIT ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('offset', array('pre' => ' OFFSET ', 'separator' => ' '));
    }


    /**
     * Builds the DQL of UPDATE
     */
    protected function _getDqlForUpdate()
    {
        /*
         * BNF:
         *
         * UpdateStatement = UpdateClause [WhereClause] [OrderByClause] [LimitClause] [OffsetClause]
         * UpdateClause    = "UPDATE" RangeVariableDeclaration "SET" UpdateItem {"," UpdateItem}
         * WhereClause     = "WHERE" ConditionalExpression
         * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
         * LimitClause     = "LIMIT" integer
         * OffsetClause    = "OFFSET" integer
         *
         */
         return 'UPDATE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' FROM ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' SET ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('orderby', array('pre' => ' ORDER BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('limit', array('pre' => ' LIMIT ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('offset', array('pre' => ' OFFSET ', 'separator' => ' '));
    }


    /**
     * Builds the DQL of SELECT
     */
    protected function _getDqlForSelect()
    {
        /*
         * BNF:
         *
         * SelectStatement = [SelectClause] FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause] [LimitClause] [OffsetClause]
         * SelectClause    = "SELECT" ["ALL" | "DISTINCT"] SelectExpression {"," SelectExpression}
         * FromClause      = "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
         * WhereClause     = "WHERE" ConditionalExpression
         * GroupByClause   = "GROUP" "BY" GroupByItem {"," GroupByItem}
         * HavingClause    = "HAVING" ConditionalExpression
         * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
         * LimitClause     = "LIMIT" integer
         * OffsetClause    = "OFFSET" integer
         *
         */
         /**
          * @todo [TODO] What about "ALL" support?
          */
         return 'SELECT'
              . (($this->getDqlQueryPart('distinct') === true) ? ' DISTINCT' : '')
              . $this->_getReducedDqlQueryPart('select', array('pre' => ' ', 'separator' => ', ', 'empty' => ' *'))
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' FROM ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('groupby', array('pre' => ' GROUP BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('having', array('pre' => ' HAVING ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('orderby', array('pre' => ' ORDER BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('limit', array('pre' => ' LIMIT ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('offset', array('pre' => ' OFFSET ', 'separator' => ' '));
    }


    /**
     * @nodoc
     */
    protected function _getReducedDqlQueryPart($queryPartName, $options = array())
    {
        if (empty($this->_dqlParts[$queryPartName])) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }

        $str  = (isset($options['pre']) ? $options['pre'] : '');
        $str .= implode($options['separator'], $this->getDqlQueryPart($queryPartName));
        $str .= (isset($options['post']) ? $options['post'] : '');

        return $str;
    }

    /**
     * Returns the type of this query object
     * By default the type is Doctrine_ORM_Query_Abstract::SELECT but if update() or delete()
     * are being called the type is Doctrine_ORM_Query_Abstract::UPDATE and Doctrine_ORM_Query_Abstract::DELETE,
     * respectively.
     *
     * @see Doctrine_ORM_Query_Abstract::SELECT
     * @see Doctrine_ORM_Query_Abstract::UPDATE
     * @see Doctrine_ORM_Query_Abstract::DELETE
     *
     * @return integer Return the query type
     */
    public function getType()
    {
        return $this->_type;
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
     * Adds fields to the SELECT part of the query
     *
     * @param string $select Query SELECT part
     * @return Doctrine_ORM_Query
     */
    public function select($select = '', $override = false)
    {
        if ($select === '') {
            return $this;
        }

        return $this->_addDqlQueryPart('select', $select, ! $override);
    }

    /**
     * Makes the query SELECT DISTINCT.
     *
     * @param bool $flag Whether or not the SELECT is DISTINCT (default true).
     * @return Doctrine_ORM_Query
     */
    public function distinct($flag = true)
    {
        $this->_dqlParts['distinct'] = (bool) $flag;
        return $this;
    }

    /**
     * Sets the query type to DELETE
     *
     * @return Doctrine_ORM_Query
     */
    public function delete()
    {
        $this->_type = self::DELETE;
        return $this;
    }

    /**
     * Sets the UPDATE part of the query
     *
     * @param string $update Query UPDATE part
     * @return Doctrine_ORM_Query
     */
    public function update($update)
    {
        $this->_type = self::UPDATE;
        return $this->_addDqlQueryPart('from', $update);
    }

    /**
     * Sets the SET part of the query
     *
     * @param mixed $key UPDATE keys. Accepts either a string (requiring then $value or $params to be defined)
     *                   or an array of $key => $value pairs.
     * @param string $value UPDATE key value. Optional argument, but required if $key is a string.
     * @return Doctrine_ORM_Query
     */
    public function set($key, $value = null, $params = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, '?', array($v));
            }

            return $this;
        } else {
            if ($params !== null) {
                if (is_array($params)) {
                    $this->_params['set'] = array_merge($this->_params['set'], $params);
                } else {
                    $this->_params['set'][] = $params;
                }
            }

            if ($value === null) {
                throw \Doctrine\Common\DoctrineException::updateMe( 'Cannot try to set \''.$key.'\' without a value.' );
            }

            return $this->_addDqlQueryPart('set', $key . ' = ' . $value, true);
        }
    }

    /**
     * Adds fields to the FROM part of the query
     *
     * @param string $from Query FROM part
     * @return Doctrine_ORM_Query
     */
    public function from($from, $override = false)
    {
        return $this->_addDqlQueryPart('from', $from, ! $override);
    }

    /**
     * Appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join Query INNER JOIN
     * @param mixed $params Optional JOIN params (array of parameters or a simple scalar)
     * @return Doctrine_ORM_Query
     */
    public function innerJoin($join, $params = array())
    {
        if (is_array($params)) {
            $this->_params['join'] = array_merge($this->_params['join'], $params);
        } else {
            $this->_params['join'][] = $params;
        }

        return $this->_addDqlQueryPart('from', 'INNER JOIN ' . $join, true);
    }

    /**
     * Appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join Query INNER JOIN
     * @param mixed $params Optional JOIN params (array of parameters or a simple scalar)
     * @return Doctrine_ORM_Query
     */
    public function join($join, $params = array())
    {
        return $this->innerJoin($join, $params);
    }

    /**
     * Appends a LEFT JOIN to the FROM part of the query
     *
     * @param string $join Query LEFT JOIN
     * @param mixed $params Optional JOIN params (array of parameters or a simple scalar)
     * @return Doctrine_ORM_Query
     */
    public function leftJoin($join, $params = array())
    {
        if (is_array($params)) {
            $this->_params['join'] = array_merge($this->_params['join'], $params);
        } else {
            $this->_params['join'][] = $params;
        }

        return $this->_addDqlQueryPart('from', 'LEFT JOIN ' . $join, true);
    }

    /**
     * Adds conditions to the WHERE part of the query
     *
     * @param string $where Query WHERE part
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function where($where, $params = array(), $override = false)
    {
        if ($override) {
            $this->_params['where'] = array();
        }

        if (is_array($params)) {
            $this->_params['where'] = array_merge($this->_params['where'], $params);
        } else {
            $this->_params['where'][] = $params;
        }

        return $this->_addDqlQueryPart('where', $where, ! $override);
    }

    /**
     * Adds conditions to the WHERE part of the query
     *
     * @param string $where Query WHERE part
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function andWhere($where, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'AND', true);
        }

        return $this->where($where, $params, $override);
    }

    /**
     * Adds conditions to the WHERE part of the query
     *
     * @param string $where Query WHERE part
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function orWhere($where, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'OR', true);
        }

        return $this->where($where, $params, $override);
    }

    /**
     * Adds IN condition to the query WHERE part
     *
     * @param string $expr The operand of the IN
     * @param mixed $params An array of parameters or a simple scalar
     * @param boolean $not Whether or not to use NOT in front of IN
     * @return Doctrine_ORM_Query
     */
    public function whereIn($expr, $params = array(), $override = false, $not = false)
    {
        $params = (array) $params;

        // Must have at least one param, otherwise we'll get an empty IN () => invalid SQL
        if ( ! count($params)) {
            return $this;
        }

        list($sqlPart, $params) = $this->_processWhereInParams($params);

        $where = $expr . ($not === true ? ' NOT' : '') . ' IN (' . $sqlPart . ')';

        return $this->_returnWhereIn($where, $params, $override);
    }

    /**
     * Adds NOT IN condition to the query WHERE part
     *
     * @param string $expr The operand of the NOT IN
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function whereNotIn($expr, $params = array(), $override = false)
    {
        return $this->whereIn($expr, $params, $override, true);
    }

    /**
     * Adds IN condition to the query WHERE part
     *
     * @param string $expr The operand of the IN
     * @param mixed $params An array of parameters or a simple scalar
     * @param boolean $not Whether or not to use NOT in front of IN
     * @return Doctrine_ORM_Query
     */
    public function andWhereIn($expr, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'AND', true);
        }

        return $this->whereIn($expr, $params, $override);
    }

    /**
     * Adds NOT IN condition to the query WHERE part
     *
     * @param string $expr The operand of the NOT IN
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function andWhereNotIn($expr, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'AND', true);
        }

        return $this->whereIn($expr, $params, $override, true);
    }

    /**
     * Adds IN condition to the query WHERE part
     *
     * @param string $expr The operand of the IN
     * @param mixed $params An array of parameters or a simple scalar
     * @param boolean $not Whether or not to use NOT in front of IN
     * @return Doctrine_ORM_Query
     */
    public function orWhereIn($expr, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'OR', true);
        }

        return $this->whereIn($expr, $params, $override);
    }

    /**
     * Adds NOT IN condition to the query WHERE part
     *
     * @param string $expr The operand of the NOT IN
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function orWhereNotIn($expr, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'OR', true);
        }

        return $this->whereIn($expr, $params, $override, true);
    }

    /**
     * Adds fields to the GROUP BY part of the query
     *
     * @param string $groupby Query GROUP BY part
     * @return Doctrine_ORM_Query
     */
    public function groupBy($groupby, $override = false)
    {
        return $this->_addDqlQueryPart('groupby', $groupby, ! $override);
    }

    /**
     * Adds conditions to the HAVING part of the query
     *
     * @param string $having Query HAVING part
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function having($having, $params = array(), $override = false)
    {
        if ($override) {
            $this->_params['having'] = array();
        }

        if (is_array($params)) {
            $this->_params['having'] = array_merge($this->_params['having'], $params);
        } else {
            $this->_params['having'][] = $params;
        }

        return $this->_addDqlQueryPart('having', $having, true);
    }

    /**
     * Adds conditions to the HAVING part of the query
     *
     * @param string $having Query HAVING part
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function andHaving($having, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('having')) > 0) {
            $this->_addDqlQueryPart('having', 'AND', true);
        }

        return $this->having($having, $params, $override);
    }

    /**
     * Adds conditions to the HAVING part of the query
     *
     * @param string $having Query HAVING part
     * @param mixed $params An array of parameters or a simple scalar
     * @return Doctrine_ORM_Query
     */
    public function orHaving($having, $params = array(), $override = false)
    {
        if (count($this->getDqlQueryPart('having')) > 0) {
            $this->_addDqlQueryPart('having', 'OR', true);
        }

        return $this->having($having, $params, $override);
    }

    /**
     * Adds fields to the ORDER BY part of the query
     *
     * @param string $orderby Query ORDER BY part
     * @return Doctrine_ORM_Query
     */
    public function orderBy($orderby, $override = false)
    {
        return $this->_addDqlQueryPart('orderby', $orderby, ! $override);
    }

    /**
     * Sets the Query query limit
     *
     * @param integer $limit Limit to be used for limiting the query results
     * @return Doctrine_ORM_Query
     */
    public function limit($limit)
    {
        return $this->_addDqlQueryPart('limit', $limit);
    }

    /**
     * Sets the Query query offset
     *
     * @param integer $offset Offset to be used for paginating the query
     * @return Doctrine_ORM_Query
     */
    public function offset($offset)
    {
        return $this->_addDqlQueryPart('offset', $offset);
    }

    /**
     * Method to check if a arbitrary piece of DQL exists
     *
     * @param string $dql Arbitrary piece of DQL to check for
     * @return boolean
     */
    public function contains($dql)
    {
      return stripos($this->getDql(), $dql) === false ? false : true;
    }

    /**
     * Retrieve a DQL part for internal purposes
     *
     * @param string $queryPartName  The name of the query part.
     * @return mixed Array related to query part or simple scalar
     */
    public function getDqlQueryPart($queryPartName)
    {
        if ( ! isset($this->_dqlParts[$queryPartName])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Unknown DQL query part \'' . $queryPartName . '\'');
        }

        return $this->_dqlParts[$queryPartName];
    }

    /**
     * Adds a DQL part to the internal parts collection.
     *
     * @param string $queryPartName  The name of the query part.
     * @param string $queryPart      The actual query part to add.
     * @param boolean $append        Whether to append $queryPart to already existing
     *                               parts under the same $queryPartName. Defaults to FALSE
     *                               (previously added parts with the same name get overridden).
     * @return Doctrine_ORM_Query
     */
    protected function _addDqlQueryPart($queryPartName, $queryPart, $append = false)
    {
        if ($append) {
            $this->_dqlParts[$queryPartName][] = $queryPart;
        } else {
            $this->_dqlParts[$queryPartName] = array($queryPart);
        }

        $this->_state = Doctrine_ORM_Query::STATE_DIRTY;
        return $this;
    }


    /**
     * Processes the WHERE IN () parameters and return an indexed array containing
     * the sqlPart to be placed in SQL statement and the new parameters (that will be
     * bound in SQL execution)
     *
     * @param array $params Parameters to be processed
     * @return array
     */
    protected function _processWhereInParams($params = array())
    {
        return array(
            // [0] => sqlPart
            implode(', ', array_map(array(&$this, '_processWhereInSqlPart'), $params)),
            // [1] => params
            array_filter($params, array(&$this, '_processWhereInParamItem')),
        );
    }

    /**
     * @nodoc
     */
    protected function _processWhereInSqlPart($value)
    {
        // [TODO] Add support to imbricated query (must deliver the hardest effort to Parser)
        return  ($value instanceof Doctrine_Expression) ? $value->getSql() : '?';
    }

    /**
     * @nodoc
     */
    protected function _processWhereInParamItem($value)
    {
        // [TODO] Add support to imbricated query (must deliver the hardest effort to Parser)
        return ( ! ($value instanceof Doctrine_Expression));
    }

    /**
     * Processes a WHERE IN () and build defined stuff to add in DQL
     *
     * @param string $where The WHERE clause to be added
     * @param array $params WHERE clause parameters
     * @param mixed $appender Where this clause may be not be appended, or appended
     *                        (two possible values: AND or OR)
     * @return Doctrine_ORM_Query
     */
    protected function _returnWhereIn($where, $params = array(), $override = false)
    {
        // Parameters inclusion
        $this->_params['where'] = $override ? $params : array_merge($this->_params['where'], $params);

        // WHERE clause definition
        return $this->_addDqlQueryPart('where', $where, ! $override);
    }
}