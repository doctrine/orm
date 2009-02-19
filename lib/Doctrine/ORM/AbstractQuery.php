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
     * @todo [TODO] Remove these ones (INSERT and CREATE)?
     */

    /**
     * Constant for INSERT queries.
     */
    //const INSERT = 3;

    /**
     * Constant for CREATE queries.
     */
    //const CREATE = 4;


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
     * @todo [TODO] Remove these ones (DIRECT and LOCKED)?
     */

    /**
     * A query is in DIRECT state when ... ?
     */
    //const STATE_DIRECT = 3;

    /**
     * A query object is on LOCKED state when ... ?
     */
    //const STATE_LOCKED = 4;


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
     * @var array $params Parameters of this query.
     * @see Query::free that initializes this property
     */
    protected $_params = array();

    /**
     * @var array $_enumParams Array containing the keys of the parameters that should be enumerated.
     * @see Query::free that initializes this property
     */
    protected $_enumParams = array();

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
     * Frees the resources used by the query object. It especially breaks a
     * cyclic reference between the query object and it's parsers. This enables
     * PHP's current GC to reclaim the memory.
     * This method can therefore be used to reduce memory usage when creating a lot
     * of query objects during a request.
     */
    public function free()
    {
        /**
         * @todo [TODO] What about "forUpdate" support? Remove it?
         */
        $this->_dqlParts = array(
            'select'    => array(),
            'distinct'  => false,
            'forUpdate' => false,
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

        $this->_params = array(
            'join' => array(),
            'set' => array(),
            'where' => array(),
            'having' => array()
        );

        $this->_enumParams = array();

        $this->_dql = null;
        $this->_state = self::STATE_CLEAN;
    }


    /**
     * Defines a complete DQL
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

            /**
             * @todo [TODO] Remove these ones (INSERT and CREATE)?
             */
            /*
            case self::INSERT:
            break;

            case self::CREATE:
            break;
            */

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
     * @see Doctrine_ORM_Query_Abstract::STATE_CLEAN
     * @see Doctrine_ORM_Query_Abstract::STATE_DIRTY
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
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag Whether or not the SELECT is FOR UPDATE (default true).
     * @return Doctrine_ORM_Query
     *
     * @todo [TODO] What about "forUpdate" support? Remove it?
     */
    public function forUpdate($flag = true)
    {
        return $this->_addDqlQueryPart('forUpdate', (bool) $flag);
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
        return array_merge(
            $this->_params['join'],
            $this->_params['set'],
            $this->_params['where'],
            $this->_params['having'],
            $params
        );
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
     * @param string|integer $key
     * @param mixed $value
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

}
