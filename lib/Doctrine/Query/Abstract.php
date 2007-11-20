<?php
/*
 *  $Id: Query.php 1393 2007-05-19 17:49:16Z zYne $
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
 * Doctrine_Query_Abstract
 *
 * @package     Doctrine
 * @subpackage  Query
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1393 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Query_Abstract
{
    /**
     * QUERY TYPE CONSTANTS
     */

    /**
     * constant for SELECT queries
     */
    const SELECT = 0;

    /**
     * constant for DELETE queries
     */
    const DELETE = 1;

    /**
     * constant for UPDATE queries
     */
    const UPDATE = 2;

    /**
     * constant for INSERT queries
     */
    const INSERT = 3;

    /**
     * constant for CREATE queries
     */
    const CREATE = 4;
    
    /** @todo document the query states (and the transitions between them). */
    const STATE_CLEAN  = 1;

    const STATE_DIRTY  = 2;

    const STATE_DIRECT = 3;

    const STATE_LOCKED = 4;
    
    
    protected $_tableAliases = array();
    
    protected $_view;

    /**
     * @var array $params                       query input parameters
     */
    protected $_params = array('where' => array(),
                               'set' => array(),
                               'having' => array());
    
    /* Caching properties */
    /**
    * @var array
    */
    protected $_cache;
    
    /**
     * @var boolean $_expireCache  A boolean value that indicates whether or not to force cache expiration.
     */
    protected $_expireCache = false;
    protected $_timeToLive;
    
    /**
     * @var Doctrine_Connection  The connection used by this query object.
     */
    protected $_conn;
    
    /**
     * @var array  The DQL keywords.
     */
    protected static $_keywords  = array('ALL', 
                                         'AND', 
                                         'ANY', 
                                         'AS', 
                                         'ASC', 
                                         'AVG', 
                                         'BETWEEN', 
                                         'BIT_LENGTH', 
                                         'BY', 
                                         'CHARACTER_LENGTH', 
                                         'CHAR_LENGTH', 
                                         'CURRENT_DATE',
                                         'CURRENT_TIME', 
                                         'CURRENT_TIMESTAMP', 
                                         'DELETE', 
                                         'DESC', 
                                         'DISTINCT', 
                                         'EMPTY', 
                                         'EXISTS', 
                                         'FALSE', 
                                         'FETCH', 
                                         'FROM', 
                                         'GROUP', 
                                         'HAVING', 
                                         'IN', 
                                         'INDEXBY', 
                                         'INNER', 
                                         'IS', 
                                         'JOIN',
                                         'LEFT', 
                                         'LIKE', 
                                         'LOWER',
                                         'MEMBER',
                                         'MOD',
                                         'NEW', 
                                         'NOT', 
                                         'NULL', 
                                         'OBJECT', 
                                         'OF', 
                                         'OR', 
                                         'ORDER', 
                                         'OUTER', 
                                         'POSITION', 
                                         'SELECT', 
                                         'SOME',
                                         'TRIM', 
                                         'TRUE', 
                                         'UNKNOWN', 
                                         'UPDATE', 
                                         'WHERE');
    
    /**
     * @var array $parts  The SQL query string parts. Filled during the DQL parsing process.
     */
    protected $parts = array(
            'select'    => array(),
            'distinct'  => false,
            'forUpdate' => false,
            'from'      => array(),
            'set'       => array(),
            'join'      => array(),
            'where'     => array(),
            'groupby'   => array(),
            'having'    => array(),
            'orderby'   => array(),
            'limit'     => false,
            'offset'    => false,
            );
            
    
    /**
     * @var array $_aliasMap                    two dimensional array containing the map for query aliases
     *      Main keys are component aliases
     *
     *          table               table object associated with given alias
     *
     *          relation            the relation object owned by the parent
     *
     *          parent              the alias of the parent
     *
     *          agg                 the aggregates of this component
     *
     *          map                 the name of the column / aggregate value this
     *                              component is mapped to a collection
     */
    protected $_aliasMap         = array();
    
    /**
     * @var integer $type                   the query type
     *
     * @see Doctrine_Query::* constants
     */
    protected $type = self::SELECT;
    
    /**
     * @var Doctrine_Hydrator   The hydrator object used to hydrate query results.
     */
    protected $_hydrator;
    
    /**
     * @var array $_tableAliasSeeds         A simple array keys representing table aliases and values
     *                                      as table alias seeds. The seeds are used for generating short table
     *                                      aliases.
     */
    protected $_tableAliasSeeds = array();
    
    /**
     * @var array $_options                 an array of options
     */
    protected $_options    = array(
                            'fetchMode'      => Doctrine::FETCH_RECORD,
                            'parserCache'    => false,
                            'resultSetCache' => false,
                            );
    
    /**
     * @var array $aggregateMap             an array containing all aggregate aliases, keys as dql aliases
     *                                      and values as sql aliases
     */
    protected $aggregateMap      = array();
    
    protected $pendingAggregates = array();
    
    protected $inheritanceApplied = false;
    
    /**
     * @var array $_enumParams              an array containing the keys of the parameters that should be enumerated
     */
    protected $_enumParams = array();
    
    protected $isLimitSubqueryUsed = false;
    
    
    public function __construct(Doctrine_Connection $connection = null,
            Doctrine_Hydrator_Abstract $hydrator = null)
    {
        if ($connection === null) {
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        }
        if ($hydrator === null) {
            $hydrator = new Doctrine_Hydrator_Default();
        }
        $this->_conn = $connection;
        $this->_hydrator = $hydrator;
    }
    
    /**
     * setOption
     *
     * @param string $name      option name
     * @param string $value     option value
     * @return Doctrine_Query   this object
     */
    public function setOption($name, $value)
    {
        if ( ! isset($this->_options[$name])) {
            throw new Doctrine_Query_Exception('Unknown option ' . $name);
        }
        $this->_options[$name] = $value;
    }
    
    /**
     * hasTableAlias
     * whether or not this object has given tableAlias
     *
     * @param string $tableAlias    the table alias to be checked
     * @return boolean              true if this object has given alias, otherwise false
     */
    public function hasTableAlias($tableAlias)
    {
        return (isset($this->_tableAliases[$tableAlias]));
    }
    
    /**
     * getTableAliases
     * returns all table aliases
     *
     * @return array        table aliases as an array
     */
    public function getTableAliases()
    {
        return $this->_tableAliases;
    }
    
    /**
     * getQueryPart
     * gets a query part from the query part array
     *
     * @param string $name          the name of the query part to be set
     * @param string $part          query part string
     * @throws Doctrine_Hydrate_Exception   if trying to set unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function getQueryPart($part)
    {
        if ( ! isset($this->parts[$part])) {
            throw new Doctrine_Hydrate_Exception('Unknown query part ' . $part);
        }

        return $this->parts[$part];
    }
    
    /**
     * setQueryPart
     * sets a query part in the query part array
     *
     * @param string $name          the name of the query part to be set
     * @param string $part          query part string
     * @throws Doctrine_Hydrate_Exception   if trying to set unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function setQueryPart($name, $part)
    {
        if ( ! isset($this->parts[$name])) {
            throw new Doctrine_Hydrate_Exception('Unknown query part ' . $name);
        }

        if ($name !== 'limit' && $name !== 'offset') {
            if (is_array($part)) {
                $this->parts[$name] = $part;
            } else {
                $this->parts[$name] = array($part);
            }
        } else {
            $this->parts[$name] = $part;
        }

        return $this;
    }
    
    /**
     * addQueryPart
     * adds a query part in the query part array
     *
     * @param string $name          the name of the query part to be added
     * @param string $part          query part string
     * @throws Doctrine_Hydrate_Exception   if trying to add unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function addQueryPart($name, $part)
    {
        if ( ! isset($this->parts[$name])) {
            throw new Doctrine_Hydrate_Exception('Unknown query part ' . $name);
        }
        if (is_array($part)) {
            $this->parts[$name] = array_merge($this->parts[$name], $part);
        } else {
            $this->parts[$name][] = $part;
        }
        return $this;
    }
    
    /**
     * removeQueryPart
     * removes a query part from the query part array
     *
     * @param string $name          the name of the query part to be removed
     * @throws Doctrine_Hydrate_Exception   if trying to remove unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function removeQueryPart($name)
    {
        if ( ! isset($this->parts[$name])) {
            throw new Doctrine_Hydrate_Exception('Unknown query part ' . $name);
        }

        if ($name == 'limit' || $name == 'offset') {
                $this->parts[$name] = false;
        } else {
                $this->parts[$name] = array();
        }
        return $this;
    }
    
    /**
     * setView
     * sets a database view this query object uses
     * this method should only be called internally by doctrine
     *
     * @param Doctrine_View $view       database view
     * @return void
     */
    public function setView(Doctrine_View $view)
    {
        $this->_view = $view;
    }
    
    /**
     * getView
     * returns the view associated with this query object (if any)
     *
     * @return Doctrine_View        the view associated with this query object
     */
    public function getView()
    {
        return $this->_view;
    }
    
    /**
     * limitSubqueryUsed
     *
     * @return boolean
     */
    public function isLimitSubqueryUsed()
    {
        return $this->isLimitSubqueryUsed;
    }
    
    /**
     * convertEnums
     * convert enum parameters to their integer equivalents
     *
     * @return array    converted parameter array
     */
    public function convertEnums($params)
    {
        foreach ($this->_enumParams as $key => $values) {
            if (isset($params[$key])) {
                if ( ! empty($values)) {
                    $params[$key] = $values[0]->enumIndex($values[1], $params[$key]);
                }
            }
        }
        return $params;
    }
    
    /**
     * applyInheritance
     * applies column aggregation inheritance to DQL / SQL query
     *
     * @return string
     */
    public function applyInheritance()
    {
        // get the inheritance maps
        $array = array();

        foreach ($this->_aliasMap as $componentAlias => $data) {
            $tableAlias = $this->getTableAlias($componentAlias);
            $array[$tableAlias][] = $data['table']->inheritanceMap;
        }

        // apply inheritance maps
        $str = '';
        $c = array();

        $index = 0;
        foreach ($array as $tableAlias => $maps) {
            $a = array();

            // don't use table aliases if the query isn't a select query
            if ($this->type !== Doctrine_Query::SELECT) {
                $tableAlias = '';
            } else {
                $tableAlias .= '.';
            }

            foreach ($maps as $map) {
                $b = array();
                foreach ($map as $field => $value) {
                    $identifier = $this->_conn->quoteIdentifier($tableAlias . $field);

                    if ($index > 0) {
                        $b[] = '(' . $identifier . ' = ' . $this->_conn->quote($value)
                             . ' OR ' . $identifier . ' IS NULL)';
                    } else {
                        $b[] = $identifier . ' = ' . $this->_conn->quote($value);
                    }
                }

                if ( ! empty($b)) {
                    $a[] = implode(' AND ', $b);
                }
            }

            if ( ! empty($a)) {
                $c[] = implode(' AND ', $a);
            }
            $index++;
        }

        $str .= implode(' AND ', $c);

        return $str;
    }
    
    /**
     * getTableAlias
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
    public function getTableAlias($componentAlias, $tableName = null)
    {
        $alias = array_search($componentAlias, $this->_tableAliases);

        if ($alias !== false) {
            return $alias;
        }

        if ($tableName === null) {
            throw new Doctrine_Hydrate_Exception("Couldn't get short alias for " . $componentAlias);
        }

        return $this->generateTableAlias($componentAlias, $tableName);
    }
    
    /**
     * generateNewTableAlias
     * generates a new alias from given table alias
     *
     * @param string $tableAlias    table alias from which to generate the new alias from
     * @return string               the created table alias
     */
    public function generateNewTableAlias($tableAlias)
    {
        if (isset($this->_tableAliases[$tableAlias])) {
            // generate a new alias
            $name = substr($tableAlias, 0, 1);
            $i    = ((int) substr($tableAlias, 1));

            if ($i == 0) {
                $i = 1;
            }

            $newIndex  = ($this->_tableAliasSeeds[$name] + $i);

            return $name . $newIndex;
        }

        return $tableAlias;
    }
    
    /**
     * getTableAliasSeed
     * returns the alias seed for given table alias
     *
     * @param string $tableAlias    table alias that identifies the alias seed
     * @return integer              table alias seed
     */
    public function getTableAliasSeed($tableAlias)
    {
        if ( ! isset($this->_tableAliasSeeds[$tableAlias])) {
            return 0;
        }
        return $this->_tableAliasSeeds[$tableAlias];
    }
    
    /**
     * hasAliasDeclaration
     * whether or not this object has a declaration for given component alias
     *
     * @param string $componentAlias    the component alias the retrieve the declaration from
     * @return boolean
     */
    public function hasAliasDeclaration($componentAlias)
    {
        return isset($this->_aliasMap[$componentAlias]);
    }
    
    /**
     * getAliasDeclaration
     * get the declaration for given component alias
     *
     * @param string $componentAlias    the component alias the retrieve the declaration from
     * @return array                    the alias declaration
     */
    public function getAliasDeclaration($componentAlias)
    {
        if ( ! isset($this->_aliasMap[$componentAlias])) {
            throw new Doctrine_Hydrate_Exception('Unknown component alias ' . $componentAlias);
        }

        return $this->_aliasMap[$componentAlias];
    }
    
    /**
     * copyAliases
     * copy aliases from another Hydrate object
     *
     * this method is needed by DQL subqueries which need the aliases
     * of the parent query
     *
     * @param Doctrine_Hydrate $query   the query object from which the
     *                                  aliases are copied from
     * @return Doctrine_Hydrate         this object
     */
    public function copyAliases(Doctrine_Query_Abstract $query)
    {
        $this->_tableAliases = $query->_tableAliases;
        $this->_aliasMap     = $query->_aliasMap;
        $this->_tableAliasSeeds = $query->_tableAliasSeeds;
        return $this;
    }
    
    /**
     * getRootAlias
     * returns the alias of the the root component
     *
     * @return array
     */
    public function getRootAlias()
    {
        if ( ! $this->_aliasMap) {
          $this->getSql();
        }
        reset($this->_aliasMap);

        return key($this->_aliasMap);
    }
    
    /**
     * getRootDeclaration
     * returns the root declaration
     *
     * @return array
     */
    public function getRootDeclaration()
    {
        $map = reset($this->_aliasMap);
        return $map;
    }
    
    /**
     * getRoot
     * returns the root component for this object
     *
     * @return Doctrine_Table       root components table
     */
    public function getRoot()
    {
        $map = reset($this->_aliasMap);

        if ( ! isset($map['table'])) {
            throw new Doctrine_Hydrate_Exception('Root component not initialized.');
        }

        return $map['table'];
    }
    
    /**
     * generateTableAlias
     * generates a table alias from given table name and associates 
     * it with given component alias
     *
     * @param string $componentAlias    the component alias to be associated with generated table alias
     * @param string $tableName         the table name from which to generate the table alias
     * @return string                   the generated table alias
     */
    public function generateTableAlias($componentAlias, $tableName)
    {
        $char   = strtolower(substr($tableName, 0, 1));

        $alias  = $char;

        if ( ! isset($this->_tableAliasSeeds[$alias])) {
            $this->_tableAliasSeeds[$alias] = 1;
        }

        while (isset($this->_tableAliases[$alias])) {
            if ( ! isset($this->_tableAliasSeeds[$alias])) {
                $this->_tableAliasSeeds[$alias] = 1;
            }
            $alias = $char . ++$this->_tableAliasSeeds[$alias];
        }

        $this->_tableAliases[$alias] = $componentAlias;

        return $alias;
    }
    
    /**
     * getComponentAlias
     * get component alias associated with given table alias
     *
     * @param string $tableAlias    the table alias that identifies the component alias
     * @return string               component alias
     */
    public function getComponentAlias($tableAlias)
    {
        if ( ! isset($this->_tableAliases[$tableAlias])) {
            throw new Doctrine_Hydrate_Exception('Unknown table alias ' . $tableAlias);
        }
        return $this->_tableAliases[$tableAlias];
    }
    
    /**
     * _execute 
     * 
     * @param array $params 
     * @return void
     */
    public function _execute($params)
    {
        $params = $this->_conn->convertBooleans($params);

        if ( ! $this->_view) {
            $query = $this->getQuery($params);
        } else {
            $query = $this->_view->getSelectSql();
        }

        $params = $this->convertEnums($params);

        if ($this->isLimitSubqueryUsed() &&
            $this->_conn->getAttribute(Doctrine::ATTR_DRIVER_NAME) !== 'mysql') {

            $params = array_merge($params, $params);
        }

        if ($this->type !== self::SELECT) {
            return $this->_conn->exec($query, $params);
        }
        //echo $query . "<br /><br />";
        $stmt = $this->_conn->execute($query, $params);
        return $stmt;
    }

    /**
     * execute
     * executes the query and populates the data set
     *
     * @param string $params
     * @return Doctrine_Collection            the root collection
     */
    public function execute($params = array(), $hydrationMode = null)
    {
        $params = array_merge($this->_params['set'], 
                              $this->_params['where'],
                              $this->_params['having'], 
                              $params);
        if ($this->_cache) {
            $cacheDriver = $this->getCacheDriver();

            $dql  = $this->getDql();
            // calculate hash for dql query
            $hash = md5($dql . var_export($params, true));

            $cached = ($this->_expireCache) ? false : $cacheDriver->fetch($hash);


            if ($cached === false) {
                // cache miss
                $stmt = $this->_execute($params);
                $array = $this->_hydrator->hydrateResultSet($stmt, $this->_aliasMap,
                        $this->_tableAliases, Doctrine::HYDRATE_ARRAY);

                $cached = $this->getCachedForm($array);

                $cacheDriver->save($hash, $cached, $this->_timeToLive);
            } else {
                $cached = unserialize($cached);
                $this->_tableAliases = $cached[2];
                $array = $cached[0];

                $map   = array();
                foreach ($cached[1] as $k => $v) {
                    $e = explode('.', $v[0]);
                    if (count($e) === 1) {
                        $map[$k]['table'] = $this->_conn->getTable($e[0]);
                    } else {
                        $map[$k]['parent']   = $e[0];
                        $map[$k]['relation'] = $map[$e[0]]['table']->getRelation($e[1]);
                        $map[$k]['table']    = $map[$k]['relation']->getTable();
                    }
                    if (isset($v[1])) {
                        $map[$k]['agg'] = $v[1];
                    }
                }
                $this->_aliasMap = $map;
            }
        } else {
            $stmt = $this->_execute($params);

            if (is_integer($stmt)) {
                return $stmt;
            }
            
            $array = $this->_hydrator->hydrateResultSet($stmt, $this->_aliasMap,
                    $this->_tableAliases, $hydrationMode);
        }
        return $array;
    }
    
    
    /**
     * addSelect
     * adds fields to the SELECT part of the query
     *
     * @param string $select        Query SELECT part
     * @return Doctrine_Query
     */
    public function addSelect($select)
    {
        return $this->parseQueryPart('select', $select, true);
    }
    
    /** 
     * addTableAlias
     * adds an alias for table and associates it with given component alias
     *
     * @param string $componentAlias    the alias for the query component associated with given tableAlias
     * @param string $tableAlias        the table alias to be added
     * @return Doctrine_Hydrate
     */
    public function addTableAlias($tableAlias, $componentAlias)
    {
        $this->_tableAliases[$tableAlias] = $componentAlias;

        return $this;
    }

    /**
     * addFrom
     * adds fields to the FROM part of the query
     *
     * @param string $from        Query FROM part
     * @return Doctrine_Query
     */
    public function addFrom($from)
    {
        return $this->parseQueryPart('from', $from, true);
    }

    /**
     * addWhere
     * adds conditions to the WHERE part of the query
     *
     * @param string $where         Query WHERE part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addWhere($where, $params = array())
    {
        if (is_array($params)) {
            $this->_params['where'] = array_merge($this->_params['where'], $params);
        } else {
            $this->_params['where'][] = $params;
        }
        return $this->parseQueryPart('where', $where, true);
    }

    /**
     * whereIn
     * adds IN condition to the query WHERE part
     *
     * @param string $expr          the operand of the IN
     * @param mixed $params         an array of parameters or a simple scalar
     * @param boolean $not          whether or not to use NOT in front of IN
     * @return Doctrine_Query
     */
    public function whereIn($expr, $params = array(), $not = false)
    {
        $params = (array) $params;
        $a = array();
        foreach ($params as $k => $value) {
            if ($value instanceof Doctrine_Expression) {
                $value = $value->getSql();
                unset($params[$k]);
            } else {
                $value = '?';          
            }
            $a[] = $value;
        }

        $this->_params['where'] = array_merge($this->_params['where'], $params);

        $where = $expr . ($not === true ? ' NOT ':'') . ' IN (' . implode(', ', $a) . ')';

        return $this->parseQueryPart('where', $where, true);
    }

    /**
     * whereNotIn
     * adds NOT IN condition to the query WHERE part
     *
     * @param string $expr          the operand of the NOT IN
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */

    public function whereNotIn($expr, $params = array())
    {
        return $this->whereIn($expr, $params, true);
    } 

    /**
     * addGroupBy
     * adds fields to the GROUP BY part of the query
     *
     * @param string $groupby       Query GROUP BY part
     * @return Doctrine_Query
     */
    public function addGroupBy($groupby)
    {
        return $this->parseQueryPart('groupby', $groupby, true);
    }

    /**
     * addHaving
     * adds conditions to the HAVING part of the query
     *
     * @param string $having        Query HAVING part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addHaving($having, $params = array())
    {
        if (is_array($params)) {
            $this->_params['having'] = array_merge($this->_params['having'], $params);
        } else {
            $this->_params['having'][] = $params;
        }
        return $this->parseQueryPart('having', $having, true);
    }

    /**
     * addOrderBy
     * adds fields to the ORDER BY part of the query
     *
     * @param string $orderby       Query ORDER BY part
     * @return Doctrine_Query
     */
    public function addOrderBy($orderby)
    {
        return $this->parseQueryPart('orderby', $orderby, true);
    }

    /**
     * select
     * sets the SELECT part of the query
     *
     * @param string $select        Query SELECT part
     * @return Doctrine_Query
     */
    public function select($select)
    {
        return $this->parseQueryPart('select', $select);
    }

    /**
     * distinct
     * Makes the query SELECT DISTINCT.
     *
     * @param bool $flag            Whether or not the SELECT is DISTINCT (default true).
     * @return Doctrine_Query
     */
    public function distinct($flag = true)
    {   
        $this->parts['distinct'] = (bool) $flag;

        return $this;
    }

    /**
     * forUpdate
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag            Whether or not the SELECT is FOR UPDATE (default true).
     * @return Doctrine_Query
     */
    public function forUpdate($flag = true)
    {
        $this->parts[self::FOR_UPDATE] = (bool) $flag;

        return $this;
    }

    /**
     * delete
     * sets the query type to DELETE
     *
     * @return Doctrine_Query
     */
    public function delete()
    {
        $this->type = self::DELETE;

        return $this;
    }

    /**
     * update
     * sets the UPDATE part of the query
     *
     * @param string $update        Query UPDATE part
     * @return Doctrine_Query
     */
    public function update($update)
    {
        $this->type = self::UPDATE;

        return $this->parseQueryPart('from', $update);
    }

    /**
     * set
     * sets the SET part of the query
     *
     * @param string $update        Query UPDATE part
     * @return Doctrine_Query
     */
    public function set($key, $value, $params = null)
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
            return $this->parseQueryPart('set', $key . ' = ' . $value, true);
        }
    }

    /**
     * from
     * sets the FROM part of the query
     *
     * @param string $from          Query FROM part
     * @return Doctrine_Query
     */
    public function from($from)
    {
        return $this->parseQueryPart('from', $from);
    }

    /**
     * innerJoin
     * appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join         Query INNER JOIN
     * @return Doctrine_Query
     */
    public function innerJoin($join)
    {
        return $this->parseQueryPart('from', 'INNER JOIN ' . $join, true);
    }

    /**
     * leftJoin
     * appends a LEFT JOIN to the FROM part of the query
     *
     * @param string $join         Query LEFT JOIN
     * @return Doctrine_Query
     */
    public function leftJoin($join)
    {
        return $this->parseQueryPart('from', 'LEFT JOIN ' . $join, true);
    }

    /**
     * groupBy
     * sets the GROUP BY part of the query
     *
     * @param string $groupby      Query GROUP BY part
     * @return Doctrine_Query
     */
    public function groupBy($groupby)
    {
        return $this->parseQueryPart('groupby', $groupby);
    }

    /**
     * where
     * sets the WHERE part of the query
     *
     * @param string $join         Query WHERE part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function where($where, $params = array())
    {
        $this->_params['where'] = array();
        if (is_array($params)) {
            $this->_params['where'] = $params;
        } else {
            $this->_params['where'][] = $params;
        }

        return $this->parseQueryPart('where', $where);
    }

    /**
     * having
     * sets the HAVING part of the query
     *
     * @param string $having       Query HAVING part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function having($having, $params = array())
    {
        $this->_params['having'] = array();
        if (is_array($params)) {
            $this->_params['having'] = $params;
        } else {
            $this->_params['having'][] = $params;
        }
        
        return $this->parseQueryPart('having', $having);
    }

    /**
     * orderBy
     * sets the ORDER BY part of the query
     *
     * @param string $orderby      Query ORDER BY part
     * @return Doctrine_Query
     */
    public function orderBy($orderby)
    {
        return $this->parseQueryPart('orderby', $orderby);
    }

    /**
     * limit
     * sets the Query query limit
     *
     * @param integer $limit        limit to be used for limiting the query results
     * @return Doctrine_Query
     */
    public function limit($limit)
    {
        return $this->parseQueryPart('limit', $limit);
    }

    /**
     * offset
     * sets the Query query offset
     *
     * @param integer $offset       offset to be used for paginating the query
     * @return Doctrine_Query
     */
    public function offset($offset)
    {
        return $this->parseQueryPart('offset', $offset);
    }
    
    /**
     * getSql
     * return the sql associated with this object
     *
     * @return string   sql query string
     */
    public function getSql()
    {
        return $this->getQuery();
    }
    
    /**
     * clear
     * resets all the variables
     *
     * @return void
     */
    protected function clear()
    {
        $this->parts = array(
                    'select'    => array(),
                    'distinct'  => false,
                    'forUpdate' => false,
                    'from'      => array(),
                    'set'       => array(),
                    'join'      => array(),
                    'where'     => array(),
                    'groupby'   => array(),
                    'having'    => array(),
                    'orderby'   => array(),
                    'limit'     => false,
                    'offset'    => false,
                    );
        $this->inheritanceApplied = false;
    }
    
    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrator->setHydrationMode($hydrationMode);
        return $this;
    }
    
    public function getAliasMap()
    {
        return $this->_aliasMap;
    }
    
    /**
     * Return the SQL parts.
     *
     * @return array The parts
     */
    public function getParts()
    {
        return $this->parts;
    }
    
    /**
     * getType
     *
     * returns the type of this query object
     * by default the type is Doctrine_Hydrate::SELECT but if update() or delete()
     * are being called the type is Doctrine_Hydrate::UPDATE and Doctrine_Hydrate::DELETE,
     * respectively
     *
     * @see Doctrine_Hydrate::SELECT
     * @see Doctrine_Hydrate::UPDATE
     * @see Doctrine_Hydrate::DELETE
     *
     * @return integer      return the query type
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * useCache
     *
     * @param Doctrine_Cache_Interface|bool $driver      cache driver
     * @param integer $timeToLive                        how long the cache entry is valid
     * @return Doctrine_Hydrate         this object
     */
    public function useCache($driver = true, $timeToLive = null)
    {
        if($driver !== null && $driver !== true && ! ($driver instanceOf Doctrine_Cache_Interface)){
            $msg = 'First argument should be instance of Doctrine_Cache_Interface or null.';
            throw new Doctrine_Hydrate_Exception($msg);
        }
        $this->_cache = $driver;

        return $this->setCacheLifeSpan($timeToLive);
    }
    
    /**
     * expireCache
     *
     * @param boolean $expire       whether or not to force cache expiration
     * @return Doctrine_Hydrate     this object
     */
    public function expireCache($expire = true)
    {
        $this->_expireCache = true;

        return $this;
    }
    
    /**
     * setCacheLifeSpan
     *
     * @param integer $timeToLive   how long the cache entry is valid
     * @return Doctrine_Hydrate     this object
     */
    public function setCacheLifeSpan($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }
        $this->_timeToLive = $timeToLive;

        return $this;
    }
    
    /**
     * getCacheDriver
     * returns the cache driver associated with this object
     *
     * @return Doctrine_Cache_Interface|boolean|null    cache driver
     */
    public function getCacheDriver()
    {
        if ($this->_cache instanceof Doctrine_Cache_Interface) {
            return $this->_cache;
        } else {
            return $this->_conn->getCacheDriver();
        }
    }
    
    /**
     * getConnection
     *
     * @return Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->_conn;
    }

    /**
     * parseQueryPart
     * parses given DQL query part
     *
     * @param string $queryPartName     the name of the query part
     * @param string $queryPart         query part to be parsed
     * @param boolean $append           whether or not to append the query part to its stack
     *                                  if false is given, this method will overwrite 
     *                                  the given query part stack with $queryPart
     * @return Doctrine_Query           this object
     */
    abstract public function parseQueryPart($queryPartName, $queryPart, $append = false);
    
    /**
     *
     *
     */
    abstract public function getQuery($params = array());
}
