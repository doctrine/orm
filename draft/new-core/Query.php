<?php
/*
 *  $Id: Query.php 1296 2007-04-26 17:42:03Z zYne $
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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Hydrate');
/**
 * Doctrine_Query2
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1296 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query2 extends Doctrine_Hydrate2 implements Countable 
{
    /**
     * @param array $subqueryAliases        the table aliases needed in some LIMIT subqueries
     */
    private $subqueryAliases  = array();
    /**
     * @param boolean $needsSubquery
     */
    private $needsSubquery    = false;
    /**
     * @param boolean $limitSubqueryUsed
     */
    private $limitSubqueryUsed = false;
    
    
    protected $_status         = array('needsSubquery' => true);
    /**
     * @param boolean $isSubquery           whether or not this query object is a subquery of another 
     *                                      query object
     */
    private $isSubquery;

    private $isDistinct        = false;

    private $neededTables      = array();
    /**
     * @var array $pendingFields
     */
    private $pendingFields     = array();
    /**
     * @var array $pendingSubqueries        SELECT part subqueries, these are called pending subqueries since
     *                                      they cannot be parsed directly (some queries might be correlated)
     */
    private $pendingSubqueries = array();
    /**
     * @var boolean $subqueriesProcessed    Whether or not pending subqueries have already been processed.
     *                                      Consequent calls to getQuery would result badly constructed queries
     *                                      without this variable
     */
    private $subqueriesProcessed = false;



    /**
     * create
     * returns a new Doctrine_Query object
     *
     * @return Doctrine_Query
     */
    public static function create()
    {
        return new Doctrine_Query();
    }
    /**
     * isSubquery
     * if $bool parameter is set this method sets the value of
     * Doctrine_Query::$isSubquery. If this value is set to true
     * the query object will not load the primary key fields of the selected
     * components.
     *
     * If null is given as the first parameter this method retrieves the current
     * value of Doctrine_Query::$isSubquery.
     *
     * @param boolean $bool     whether or not this query acts as a subquery
     * @return Doctrine_Query|bool
     */
    public function isSubquery($bool = null)
    {
        if ($bool === null) {
            return $this->isSubquery;
        }

        $this->isSubquery = (bool) $bool;
        return $this;
    }

    /**
     * getAggregateAlias
     * 
     * @return string
     */
    public function getAggregateAlias($dqlAlias)
    {
        if(isset($this->aggregateMap[$dqlAlias])) {
            return $this->aggregateMap[$dqlAlias];
        }
        
        return null;
    }

    public function isDistinct($distinct = null)
    {
        if(isset($distinct))
            $this->isDistinct = (bool) $distinct;

        return $this->isDistinct;
    }

	/**
 	 * count
     *
     * @param array $params
	 * @return integer
     */
	public function count($params = array())
    {
		$oldParts = $this->parts;

		$this->remove('select');
		$join  = $this->join;
		$where = $this->where;
		$having = $this->having;
		$table  = reset($this->tables);

		$q  = 'SELECT COUNT(DISTINCT ' . $this->aliasHandler->getShortAlias($table->getTableName())
            . '.' . $table->getIdentifier()
            . ') FROM ' . $table->getTableName() . ' ' . $this->aliasHandler->getShortAlias($table->getTableName());

		foreach($join as $j) {
            $q .= ' '.implode(' ',$j);
		}
        $string = $this->applyInheritance();

        if( ! empty($where)) {
            $q .= ' WHERE ' . implode(' AND ', $where);
            if( ! empty($string))
                $q .= ' AND (' . $string . ')';
        } else {
            if( ! empty($string))
                $q .= ' WHERE (' . $string . ')';
        }
			
		if( ! empty($having))
			$q .= ' HAVING ' . implode(' AND ',$having);

        if( ! is_array($params)) {
            $params = array($params);
        }

        $params = array_merge($this->params, $params);

		$this->parts = $oldParts;
		return (int) $this->getConnection()->fetchOne($q, $params);
	}
    /**
     * @return boolean
     */
    public function isLimitSubqueryUsed() {
        return $this->limitSubqueryUsed;
    }

    /**
     * query
     * query the database with DQL (Doctrine Query Language)
     *
     * @param string $query     DQL query
     * @param array $params     prepared statement parameters
     * @see Doctrine::FETCH_* constants
     * @return mixed
     */
    public function query($query, $params = array())
    {
        $this->_parser->parseQuery($query);

        return $this->execute($params);
    }
    /**
     * getShortAlias
     * some database such as Oracle need the identifier lengths to be < ~30 chars
     * hence Doctrine creates as short identifier aliases as possible
     *
     * this method is used for the creation of short table aliases, its also
     * smart enough to check if an alias already exists for given component (componentAlias)
     *
     * @param string $componentAlias    the alias for the query component to search table alias for
     * @param string $tableName         the table name from which the table alias is being created
     * @return string                   the generated / fetched short alias
     */
    public function getShortAlias($componentAlias, $tableName)
    {
        return $this->aliasHandler->getShortAlias($componentAlias, $tableName);
    }
    /**
     * addSelect
     * adds fields to the SELECT part of the query
     *
     * @param string $select        DQL SELECT part
     * @return Doctrine_Query
     */
    public function addSelect($select)
    {
        return $this->getParser('select')->parse($select, true);
    }
    /**
     * addWhere
     * adds conditions to the WHERE part of the query
     *
     * @param string $where         DQL WHERE part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addWhere($where, $params = array())
    {
        if(is_array($params)) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params[] = $params;
        }
        return $this->getParser('where')->parse($where, true);
    }
    /**
     * addGroupBy
     * adds fields to the GROUP BY part of the query
     *
     * @param string $groupby       DQL GROUP BY part
     * @return Doctrine_Query
     */
    public function addGroupBy($groupby)
    {
        return $this->getParser('groupby')->parse($groupby, true);
    }
    /**
     * addHaving
     * adds conditions to the HAVING part of the query
     *
     * @param string $having        DQL HAVING part
     * @return Doctrine_Query
     */
    public function addHaving($having)
    {
        return $this->getParser('having')->parse($having, true);
    }
    /**
     * addOrderBy
     * adds fields to the ORDER BY part of the query
     *
     * @param string $orderby       DQL ORDER BY part
     * @return Doctrine_Query
     */
    public function addOrderBy($orderby)
    {
        return $this->getParser('orderby')->parse($orderby, true);
    }
    /**
     * select
     * sets the SELECT part of the query
     *
     * @param string $select        DQL SELECT part
     * @return Doctrine_Query
     */
    public function select($select)
    {
        return $this->getParser('from')->parse($select);
    }
    /**
     * from
     * sets the FROM part of the query
     *
     * @param string $from          DQL FROM part
     * @return Doctrine_Query
     */
    public function from($from)
    {
        return $this->getParser('from')->parse($from);
    }
    /**
     * innerJoin
     * appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join         DQL INNER JOIN
     * @return Doctrine_Query
     */
    public function innerJoin($join)
    {
        return $this->getParser('from')->parse('INNER JOIN ' . $join);
    }
    /**
     * leftJoin
     * appends a LEFT JOIN to the FROM part of the query
     *
     * @param string $join         DQL LEFT JOIN
     * @return Doctrine_Query
     */
    public function leftJoin($join)
    {
        return $this->getParser('from')->parse('LERT JOIN ' . $join);
    }
    /**
     * groupBy
     * sets the GROUP BY part of the query
     *
     * @param string $groupby      DQL GROUP BY part
     * @return Doctrine_Query
     */
    public function groupBy($groupby)
    {
        return $this->getParser('groupby')->parse($groupby);
    }
    /**
     * where
     * sets the WHERE part of the query
     *
     * @param string $join         DQL WHERE part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function where($where, $params = array())
    {
        if(is_array($params)) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params[] = $params;
        }
        return $this->getParser('where')->parse($where);
    }
    /**
     * having
     * sets the HAVING part of the query
     *
     * @param string $having       DQL HAVING part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function having($having, $params)
    {
        if(is_array($params)) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params[] = $params;
        }
        return $this->getParser('having')->parse($having);
    }
    /**
     * orderBy
     * sets the ORDER BY part of the query
     *
     * @param string $groupby      DQL ORDER BY part
     * @return Doctrine_Query
     */
    public function orderBy($dql)
    {
        return $this->getParser('orderby')->parse($dql);
    }
    /**
     * limit
     * sets the DQL query limit
     *
     * @param integer $limit        limit to be used for limiting the query results
     * @return Doctrine_Query
     */
    public function limit($limit)
    {
        return $this->getParser('limit')->parse($dql);
    }
    /**
     * offset
     * sets the DQL query offset
     *
     * @param integer $offset       offset to be used for paginating the query
     * @return Doctrine_Query
     */
    public function offset($dql)
    {
        return $this->getParser('offset')->parse($dql);
    }
}

