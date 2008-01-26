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
 * <http://www.phpdoctrine.org>.
 */

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
 * @todo        See {@link Doctrine_Query} 
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
    /**
     * A query object is in CLEAN state when it has NO unparsed/unprocessed DQL parts.
     */
    const STATE_CLEAN  = 1;
    
    /**
     * A query object is in state DIRTY when it has DQL parts that have not yet been 
     * parsed/processed.
     */
    const STATE_DIRTY  = 2;
    
    /**
     * A query is in DIRECT state when ... ?
     */
    const STATE_DIRECT = 3;
    
    /**
     * A query object is on LOCKED state when ... ?
     */
    const STATE_LOCKED = 4;
    
    /**
     * @var array  Table alias map. Keys are SQL aliases and values DQL aliases. 
     */
    protected $_tableAliasMap = array();
    
    /**
     * @var Doctrine_View  The view object used by this query, if any.
     */
    protected $_view;
    
    /**
     * @var integer $_state   The current state of this query.
     */
    protected $_state = Doctrine_Query::STATE_CLEAN;

    /**
     * @var array $params  The parameters of this query.
     */
    protected $_params = array('join' => array(),
                               'where' => array(),
                               'set' => array(),
                               'having' => array());
    
    /* Caching properties */
    /** 
     * @var Doctrine_Cache_Interface  The cache driver used for caching result sets.
     */
    protected $_resultCache; 
    /**
     * @var boolean $_expireResultCache  A boolean value that indicates whether or not
     *                                   expire the result cache.
     */
    protected $_expireResultCache = false;
    protected $_resultCacheTTL;
    
    /** 
     * @var Doctrine_Cache_Interface  The cache driver used for caching queries.
     */
    protected $_queryCache;
    protected $_expireQueryCache = false;
    protected $_queryCacheTTL;
    
    
    /**
     * @var Doctrine_Connection  The connection used by this query object.
     */
    protected $_conn;
    
    
    /**
     * @var array $_sqlParts  The SQL query string parts. Filled during the DQL parsing process.
     */
    protected $_sqlParts = array(
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
     * @var array $_dqlParts   An array containing all DQL query parts.
     */
    protected $_dqlParts = array(
            'from'      => array(),
            'select'    => array(),
            'forUpdate' => false,
            'set'       => array(),
            'join'      => array(),
            'where'     => array(),
            'groupby'   => array(),
            'having'    => array(),
            'orderby'   => array(),
            'limit'     => array(),
            'offset'    => array(),
            );
            
    
    /**
     * @var array $_queryComponents   Two dimensional array containing the components of this query,
     *                                informations about their relations and other related information.
     *                                The components are constructed during query parsing.
     *
     *      Keys are component aliases and values the following:
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
    protected $_queryComponents = array();
    
    /**
     * @var integer $type                   the query type
     *
     * @see Doctrine_Query::* constants
     */
    protected $_type = self::SELECT;
    
    /**
     * @var Doctrine_Hydrator   The hydrator object used to hydrate query results.
     */
    protected $_hydrator;
    
    /**
     * @var Doctrine_Query_Tokenizer  The tokenizer that is used during the query parsing process.
     */
    protected $_tokenizer;
    
    /**
     * @var Doctrine_Query_Parser  The parser that is used for query parsing.
     */
    protected $_parser;
    
    /**
     * @var array $_tableAliasSeeds         A simple array keys representing table aliases and values
     *                                      table alias seeds. The seeds are used for generating short table
     *                                      aliases.
     */
    protected $_tableAliasSeeds = array();
    
    /**
     * @var array $_options                 an array of options
     */
    protected $_options    = array(
                            'fetchMode'      => Doctrine::FETCH_RECORD
                            );
    
    /**
     * @var array $_enumParams              an array containing the keys of the parameters that should be enumerated
     */
    protected $_enumParams = array();
    
    /**
     * @var boolean
     */
    protected $_isLimitSubqueryUsed = false;
    
    
    /**
     * Constructor.
     *
     * @param Doctrine_Connection  The connection object the query will use.
     * @param Doctrine_Hydrator_Abstract  The hydrator that will be used for generating result sets.
     */
    public function __construct(Doctrine_Connection $connection = null,
            Doctrine_Hydrator_Abstract $hydrator = null)
    {
        if ($connection === null) {
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        }
        if ($hydrator === null) {
            $hydrator = new Doctrine_Hydrator();
        }
        $this->_conn = $connection;
        $this->_hydrator = $hydrator;
        $this->_tokenizer = new Doctrine_Query_Tokenizer();
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
     * @deprecated
     */
    public function hasTableAlias($sqlTableAlias)
    {
        return $this->hasSqlTableAlias($sqlTableAlias);
    }
    
    /**
     * hasSqlTableAlias
     * whether or not this object has given tableAlias
     *
     * @param string $tableAlias    the table alias to be checked
     * @return boolean              true if this object has given alias, otherwise false
     */
    public function hasSqlTableAlias($sqlTableAlias)
    {
        return (isset($this->_tableAliasMap[$sqlTableAlias]));
    }
    
    /**
     * getTableAliases
     * returns all table aliases
     *
     * @return array        table aliases as an array
     * @deprecated
     */
    public function getTableAliases()
    {
        return $this->getTableAliasMap();
    }
    
    /**
     * getTableAliasMap
     * returns all table aliases
     *
     * @return array        table aliases as an array
     */
    public function getTableAliasMap()
    {
        return $this->_tableAliasMap;
    }
    
    /**
     * getQueryPart
     * gets a query part from the query part array
     *
     * @param string $name          the name of the query part to be set
     * @param string $part          query part string
     * @throws Doctrine_Query_Exception   if trying to set unknown query part
     * @return Doctrine_Query_Abstract  this object
     * @deprecated
     */
    public function getQueryPart($part)
    {
        return $this->getSqlQueryPart($part);
    }
    
    /**
     * getSqlQueryPart
     * gets an SQL query part from the SQL query part array
     *
     * @param string $name          the name of the query part to be set
     * @param string $part          query part string
     * @throws Doctrine_Query_Exception   if trying to set unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function getSqlQueryPart($part)
    {
        if ( ! isset($this->_sqlParts[$part])) {
            throw new Doctrine_Query_Exception('Unknown SQL query part ' . $part);
        }
        return $this->_sqlParts[$part];
    }
    
    /**
     * setQueryPart
     * sets a query part in the query part array
     *
     * @param string $name          the name of the query part to be set
     * @param string $part          query part string
     * @throws Doctrine_Query_Exception   if trying to set unknown query part
     * @return Doctrine_Hydrate     this object
     * @deprecated
     */
    public function setQueryPart($name, $part)
    {
        return $this->setSqlQueryPart($name, $part);
    }
    
    /**
     * setSqlQueryPart
     * sets an SQL query part in the SQL query part array
     *
     * @param string $name          the name of the query part to be set
     * @param string $part          query part string
     * @throws Doctrine_Query_Exception   if trying to set unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function setSqlQueryPart($name, $part)
    {
        if ( ! isset($this->_sqlParts[$name])) {
            throw new Doctrine_Query_Exception('Unknown query part ' . $name);
        }

        if ($name !== 'limit' && $name !== 'offset') {
            if (is_array($part)) {
                $this->_sqlParts[$name] = $part;
            } else {
                $this->_sqlParts[$name] = array($part);
            }
        } else {
            $this->_sqlParts[$name] = $part;
        }

        return $this;
    }
    
    /**
     * addQueryPart
     * adds a query part in the query part array
     *
     * @param string $name          the name of the query part to be added
     * @param string $part          query part string
     * @throws Doctrine_Query_Exception   if trying to add unknown query part
     * @return Doctrine_Hydrate     this object
     * @deprecated
     */
    public function addQueryPart($name, $part)
    {
        return $this->addSqlQueryPart($name, $part);
    }
    
    /**
     * addSqlQueryPart
     * adds an SQL query part to the SQL query part array
     *
     * @param string $name          the name of the query part to be added
     * @param string $part          query part string
     * @throws Doctrine_Query_Exception   if trying to add unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function addSqlQueryPart($name, $part)
    {
        if ( ! isset($this->_sqlParts[$name])) {
            throw new Doctrine_Query_Exception('Unknown query part ' . $name);
        }
        if (is_array($part)) {
            $this->_sqlParts[$name] = array_merge($this->_sqlParts[$name], $part);
        } else {
            $this->_sqlParts[$name][] = $part;
        }
        return $this;
    }
    
    /**
     * removeQueryPart
     * removes a query part from the query part array
     *
     * @param string $name          the name of the query part to be removed
     * @throws Doctrine_Query_Exception   if trying to remove unknown query part
     * @return Doctrine_Hydrate     this object
     * @deprecated
     */
    public function removeQueryPart($name)
    {
        return $this->removeSqlQueryPart($name);
    }
    
    /**
     * removeSqlQueryPart
     * removes a query part from the query part array
     *
     * @param string $name          the name of the query part to be removed
     * @throws Doctrine_Query_Exception   if trying to remove unknown query part
     * @return Doctrine_Hydrate     this object
     */
    public function removeSqlQueryPart($name)
    {
        try {
        if ( ! isset($this->_sqlParts[$name])) {
            throw new Doctrine_Query_Exception('Unknown query part ' . $name);
        }}
        catch (Exception $e) {echo $e->getTraceAsString(); echo "<br /><br /><br />";}

        if ($name == 'limit' || $name == 'offset') {
                $this->_sqlParts[$name] = false;
        } else {
                $this->_sqlParts[$name] = array();
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
        return $this->_isLimitSubqueryUsed;
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
     * Creates the SQL snippet for additional joins.
     * 
     * @return string  The created SQL snippet.
     */
    protected function _createCustomJoinSql($componentName, $componentAlias)
    {
        $table = $this->_conn->getTable($componentName);
        $tableAlias = $this->getSqlTableAlias($componentAlias, $table->getTableName());
        $customJoins = $this->_conn->getMapper($componentName)->getCustomJoins();
        $sql = '';
        foreach ($customJoins as $componentName => $joinType) {
            $joinedTable = $this->_conn->getTable($componentName);
            $joinedAlias = $componentAlias . '.' . $componentName;
            $joinedTableAlias = $this->getSqlTableAlias($joinedAlias, $joinedTable->getTableName());
            $sql .= " $joinType JOIN " . $this->_conn->quoteIdentifier($joinedTable->getTableName())
                    . ' ' . $this->_conn->quoteIdentifier($joinedTableAlias) . ' ON ';
            
            foreach ($table->getIdentifierColumnNames() as $column) {
                $sql .= $this->_conn->quoteIdentifier($tableAlias) 
                        . '.' . $this->_conn->quoteIdentifier($column)
                        . ' = ' . $this->_conn->quoteIdentifier($joinedTableAlias)
                        . '.' . $this->_conn->quoteIdentifier($column);
            }
        }
        
        return $sql;
    }
    
    /**
     * Creates the SQL snippet for the WHERE part that contains the discriminator
     * column conditions.
     *
     * @return string  The created SQL snippet.
     */
    protected function _createDiscriminatorConditionSql()
    {        
        $array = array();
        foreach ($this->_queryComponents as $componentAlias => $data) {
            $sqlTableAlias = $this->getSqlTableAlias($componentAlias);
            if ( ! $data['mapper'] instanceof Doctrine_Mapper_SingleTable) {
                $array[$sqlTableAlias][] = array();
            } else {
                $array[$sqlTableAlias][] = $data['mapper']->getDiscriminatorColumn();
            }
        }
        //var_dump($array);
        // apply inheritance maps
        $str = '';
        $c = array();

        $index = 0;
        foreach ($array as $tableAlias => $maps) {
            $a = array();

            // don't use table aliases if the query isn't a select query
            if ($this->_type !== Doctrine_Query::SELECT) {
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
     * @deprecated
     */
    public function getTableAlias($componentAlias, $tableName = null)
    {
        return $this->getSqlTableAlias($componentAlias, $tableName);
    }
    
    /**
     * getSqlTableAlias
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
    public function getSqlTableAlias($componentAlias, $tableName = null)
    {
        $alias = array_search($componentAlias, $this->_tableAliasMap);

        if ($alias !== false) {
            return $alias;
        }

        if ($tableName === null) {
            throw new Doctrine_Query_Exception("Couldn't get short alias for " . $componentAlias);
        }

        return $this->generateTableAlias($componentAlias, $tableName);
    }
    
    /**
     * generateNewTableAlias
     * generates a new alias from given table alias
     *
     * @param string $tableAlias    table alias from which to generate the new alias from
     * @return string               the created table alias
     * @deprecated
     */
    public function generateNewTableAlias($oldAlias)
    {
        return $this->generateNewSqlTableAlias($oldAlias);
    }
    
    /**
     * generateNewSqlTableAlias
     * generates a new alias from given table alias
     *
     * @param string $tableAlias    table alias from which to generate the new alias from
     * @return string               the created table alias
     */
    public function generateNewSqlTableAlias($oldAlias)
    {
        if (isset($this->_tableAliasMap[$oldAlias])) {
            // generate a new alias
            $name = substr($oldAlias, 0, 1);
            $i    = ((int) substr($oldAlias, 1));

            if ($i == 0) {
                $i = 1;
            }

            $newIndex  = ($this->_tableAliasSeeds[$name] + $i);

            return $name . $newIndex;
        }

        return $oldAlias;
    }
    
    /**
     * getTableAliasSeed
     * returns the alias seed for given table alias
     *
     * @param string $tableAlias    table alias that identifies the alias seed
     * @return integer              table alias seed
     * @deprecated
     */
    public function getTableAliasSeed($sqlTableAlias)
    {
        return $this->getSqlTableAliasSeed($sqlTableAlias);
    }
    
    /**
     * getSqlTableAliasSeed
     * returns the alias seed for given table alias
     *
     * @param string $tableAlias    table alias that identifies the alias seed
     * @return integer              table alias seed
     */
    public function getSqlTableAliasSeed($sqlTableAlias)
    {
        if ( ! isset($this->_tableAliasSeeds[$sqlTableAlias])) {
            return 0;
        }
        return $this->_tableAliasSeeds[$sqlTableAlias];
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
        return isset($this->_queryComponents[$componentAlias]);
    }
    
    /**
     * getAliasDeclaration
     * get the declaration for given component alias
     *
     * @param string $componentAlias    the component alias the retrieve the declaration from
     * @return array                    the alias declaration
     * @deprecated
     */
    public function getAliasDeclaration($componentAlias)
    {
        return $this->getQueryComponent($componentAlias);
    }
    
    /**
     * getQueryComponent
     * get the declaration for given component alias
     *
     * @param string $componentAlias    the component alias the retrieve the declaration from
     * @return array                    the alias declaration
     */
    public function getQueryComponent($componentAlias)
    {
        if ( ! isset($this->_queryComponents[$componentAlias])) {
            throw new Doctrine_Query_Exception('Unknown component alias ' . $componentAlias);
        }

        return $this->_queryComponents[$componentAlias];
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
        $this->_tableAliasMap = $query->_tableAliasMap;
        $this->_queryComponents     = $query->_queryComponents;
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
        if ( ! $this->_queryComponents) {
          $this->getSql();
        }
        reset($this->_queryComponents);

        return key($this->_queryComponents);
    }
    
    /**
     * getRootDeclaration
     * returns the root declaration
     *
     * @return array
     */
    public function getRootDeclaration()
    {
        $map = reset($this->_queryComponents);
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
        $map = reset($this->_queryComponents);

        if ( ! isset($map['table'])) {
            throw new Doctrine_Query_Exception('Root component not initialized.');
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
     * @deprecated
     */
    public function generateTableAlias($componentAlias, $tableName)
    {
        return $this->generateSqlTableAlias($componentAlias, $tableName);
    }
    
    /**
     * generateSqlTableAlias
     * generates a table alias from given table name and associates 
     * it with given component alias
     *
     * @param string $componentAlias    the component alias to be associated with generated table alias
     * @param string $tableName         the table name from which to generate the table alias
     * @return string                   the generated table alias
     */
    public function generateSqlTableAlias($componentAlias, $tableName)
    {
        $char   = strtolower(substr($tableName, 0, 1));

        $alias  = $char;

        if ( ! isset($this->_tableAliasSeeds[$alias])) {
            $this->_tableAliasSeeds[$alias] = 1;
        }

        while (isset($this->_tableAliasMap[$alias])) {
            if ( ! isset($this->_tableAliasSeeds[$alias])) {
                $this->_tableAliasSeeds[$alias] = 1;
            }
            $alias = $char . ++$this->_tableAliasSeeds[$alias];
        }

        $this->_tableAliasMap[$alias] = $componentAlias;

        return $alias;
    }
    
    /**
     * getComponentAlias
     * get component alias associated with given table alias
     *
     * @param string $sqlTableAlias    the SQL table alias that identifies the component alias
     * @return string               component alias
     */
    public function getComponentAlias($sqlTableAlias)
    {
        if ( ! isset($this->_tableAliasMap[$sqlTableAlias])) {
            throw new Doctrine_Query_Exception('Unknown table alias ' . $sqlTableAlias);
        }
        return $this->_tableAliasMap[$sqlTableAlias];
    }
    
    /**
     * _execute 
     * 
     * @param array $params 
     * @return PDOStatement  The executed PDOStatement.
     */
    protected function _execute($params)
    {
        $params = $this->_conn->convertBooleans($params);

        if ( ! $this->_view) {
            if ($this->_queryCache || $this->_conn->getAttribute(Doctrine::ATTR_QUERY_CACHE)) {
                $queryCacheDriver = $this->getQueryCacheDriver();
                // calculate hash for dql query
                $dql = $this->getDql(); 
                $hash = md5($dql . 'DOCTRINE_QUERY_CACHE_SALT');
                $cached = $queryCacheDriver->fetch($hash);
                if ($cached) {
                    $query = $this->_constructQueryFromCache($cached);
                } else {
                    $query = $this->getSqlQuery($params);
                    $serializedQuery = $this->getCachedForm($query);
                    $queryCacheDriver->save($hash, $serializedQuery, $this->_queryCacheTTL);
                }
            } else {
                $query = $this->getSqlQuery($params);
            }
        } else {
            $query = $this->_view->getSelectSql();
        }

        $params = $this->convertEnums($params);

        if ($this->isLimitSubqueryUsed() &&
                $this->_conn->getAttribute(Doctrine::ATTR_DRIVER_NAME) !== 'mysql') {
            $params = array_merge($params, $params);
        }

        if ($this->_type !== self::SELECT) {
            return $this->_conn->exec($query, $params);
        }

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
        $params = array_merge($this->_params['join'],
                              $this->_params['set'], 
                              $this->_params['where'],
                              $this->_params['having'], 
                              $params);
        
        if ($this->_resultCache) {
            $cacheDriver = $this->getResultCacheDriver();

            $dql = $this->getDql();
            // calculate hash for dql query
            $hash = md5($dql . var_export($params, true));

            $cached = ($this->_expireResultCache) ? false : $cacheDriver->fetch($hash);

            if ($cached === false) {
                // cache miss
                $stmt = $this->_execute($params);
                $this->_hydrator->setQueryComponents($this->_queryComponents);
                $result = $this->_hydrator->hydrateResultSet($stmt, $this->_tableAliasMap,
                        Doctrine::HYDRATE_ARRAY);

                $cached = $this->getCachedForm($result);
                $cacheDriver->save($hash, $cached, $this->_resultCacheTTL);
                return $result;
            } else {
                return $this->_constructQueryFromCache($cached);
            }
        } else {
            $stmt = $this->_execute($params);

            if (is_integer($stmt)) {
                return $stmt;
            }
            
            $this->_hydrator->setQueryComponents($this->_queryComponents);
            return $this->_hydrator->hydrateResultSet($stmt, $this->_tableAliasMap, $hydrationMode);
        }
    }
    
    /**
     * Constructs the query from the cached form.
     * 
     * @param string  The cached query, in a serialized form.
     * @return array  The custom component that was cached together with the essential
     *                query data. This can be either a result set (result caching)
     *                or an SQL query string (query caching).
     */
    protected function _constructQueryFromCache($cached)
    {
        $cached = unserialize($cached);
        $this->_tableAliasMap = $cached[2];
        $customComponent = $cached[0];

        $queryComponents = array();
        $cachedComponents = $cached[1];
        foreach ($cachedComponents as $alias => $components) {
            $e = explode('.', $components[0]);
            if (count($e) === 1) {
                $queryComponents[$alias]['mapper'] = $this->_conn->getMapper($e[0]);
                $queryComponents[$alias]['table'] = $queryComponents[$alias]['mapper']->getTable();
            } else {
                $queryComponents[$alias]['parent'] = $e[0];
                $queryComponents[$alias]['relation'] = $queryComponents[$e[0]]['table']->getRelation($e[1]);
                $queryComponents[$alias]['mapper'] = $this->_conn->getMapper($queryComponents[$alias]['relation']->getForeignComponentName());
                $queryComponents[$alias]['table'] = $queryComponents[$alias]['mapper']->getTable();
            }
            if (isset($v[1])) {
                $queryComponents[$alias]['agg'] = $components[1];
            }
            if (isset($v[2])) {
                $queryComponents[$alias]['map'] = $components[2];
            }
        }
        $this->_queryComponents = $queryComponents;
        
        return $customComponent;
    }
    
    /**
     * getCachedForm
     * returns the cached form of this query for given resultSet
     *
     * @param array $resultSet
     * @return string           serialized string representation of this query
     */
    public function getCachedForm($customComponent = null)
    {
        $componentInfo = array();

        foreach ($this->getQueryComponents() as $alias => $components) {
            if ( ! isset($components['parent'])) {
                $componentInfo[$alias][] = $components['mapper']->getComponentName();
                //$componentInfo[$alias][] = $components['mapper']->getComponentName();
            } else {
                $componentInfo[$alias][] = $components['parent'] . '.' . $components['relation']->getAlias();
            }
            if (isset($components['agg'])) {
                $componentInfo[$alias][] = $components['agg'];
            }
            if (isset($components['map'])) {
                $componentInfo[$alias][] = $components['map'];
            }
        }
        
        return serialize(array($customComponent, $componentInfo, $this->getTableAliasMap()));
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
        return $this->_addDqlQueryPart('select', $select, true);
    }
    
    /** 
     * addTableAlias
     * adds an alias for table and associates it with given component alias
     *
     * @param string $componentAlias    the alias for the query component associated with given tableAlias
     * @param string $tableAlias        the table alias to be added
     * @return Doctrine_Hydrate
     * @deprecated
     */
    public function addTableAlias($tableAlias, $componentAlias)
    {
        return $this->addSqlTableAlias($tableAlias, $componentAlias);
    }
    
    /** 
     * addSqlTableAlias
     * adds an SQL table alias and associates it a component alias
     *
     * @param string $componentAlias    the alias for the query component associated with given tableAlias
     * @param string $tableAlias        the table alias to be added
     * @return Doctrine_Query_Abstract
     */
    public function addSqlTableAlias($sqlTableAlias, $componentAlias)
    {
        $this->_tableAliasMap[$sqlTableAlias] = $componentAlias;
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
        return $this->_addDqlQueryPart('from', $from, true);
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
        return $this->_addDqlQueryPart('where', $where, true);
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

        // if there's no params, return (else we'll get a WHERE IN (), invalid SQL)
        if (!count($params))
          return;

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

        return $this->_addDqlQueryPart('where', $where, true);
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
        return $this->_addDqlQueryPart('groupby', $groupby, true);
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
        return $this->_addDqlQueryPart('having', $having, true);
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
        return $this->_addDqlQueryPart('orderby', $orderby, true);
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
        return $this->_addDqlQueryPart('select', $select);
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
        $this->_sqlParts['distinct'] = (bool) $flag;
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
        $this->_sqlParts[self::FOR_UPDATE] = (bool) $flag;
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
        $this->_type = self::DELETE;
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
        $this->_type = self::UPDATE;
        return $this->_addDqlQueryPart('from', $update);
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
            return $this->_addDqlQueryPart('set', $key . ' = ' . $value, true);
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
        return $this->_addDqlQueryPart('from', $from);
    }

    /**
     * innerJoin
     * appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join         Query INNER JOIN
     * @return Doctrine_Query
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
     * leftJoin
     * appends a LEFT JOIN to the FROM part of the query
     *
     * @param string $join         Query LEFT JOIN
     * @return Doctrine_Query
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
     * groupBy
     * sets the GROUP BY part of the query
     *
     * @param string $groupby      Query GROUP BY part
     * @return Doctrine_Query
     */
    public function groupBy($groupby)
    {
        return $this->_addDqlQueryPart('groupby', $groupby);
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

        return $this->_addDqlQueryPart('where', $where);
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
        
        return $this->_addDqlQueryPart('having', $having);
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
        return $this->_addDqlQueryPart('orderby', $orderby);
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
        return $this->_addDqlQueryPart('limit', $limit);
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
        return $this->_addDqlQueryPart('offset', $offset);
    }
    
    /**
     * getSql
     * shortcut for {@link getSqlQuery()}.
     *
     * @return string   sql query string
     */
    public function getSql()
    {
        return $this->getSqlQuery();
    }
    
    /**
     * clear
     * resets all the variables
     *
     * @return void
     */
    protected function clear()
    {
        $this->_sqlParts = array(
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
    }
    
    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrator->setHydrationMode($hydrationMode);
        return $this;
    }
    
    /**
     * @deprecated
     */
    public function getAliasMap()
    {
        return $this->_queryComponents;
    }
    
    /**
     * Gets the components of this query.
     */
    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }
    
    /**
     * Return the SQL parts.
     *
     * @return array The parts
     * @deprecated
     */
    public function getParts()
    {
        return $this->getSqlParts();
    }
    
    /**
     * Return the SQL parts.
     *
     * @return array The parts
     */
    public function getSqlParts()
    {
        return $this->_sqlParts;
    }

    /**
     * getType
     *
     * returns the type of this query object
     * by default the type is Doctrine_Query_Abstract::SELECT but if update() or delete()
     * are being called the type is Doctrine_Query_Abstract::UPDATE and Doctrine_Query_Abstract::DELETE,
     * respectively
     *
     * @see Doctrine_Query_Abstract::SELECT
     * @see Doctrine_Query_Abstract::UPDATE
     * @see Doctrine_Query_Abstract::DELETE
     *
     * @return integer      return the query type
     */
    public function getType()
    {
        return $this->_type;
    }
    
    /**
     * useCache
     *
     * @param Doctrine_Cache_Interface|bool $driver      cache driver
     * @param integer $timeToLive                        how long the cache entry is valid
     * @return Doctrine_Hydrate         this object
     * @deprecated Use useResultCache()
     */
    public function useCache($driver = true, $timeToLive = null)
    {
        return $this->useResultCache($driver, $timeToLive);
    }
    
    /**
     * useResultCache
     *
     * @param Doctrine_Cache_Interface|bool $driver      cache driver
     * @param integer $timeToLive                        how long the cache entry is valid
     * @return Doctrine_Hydrate         this object
     */
    public function useResultCache($driver = true, $timeToLive = null)
    {
        if ($driver !== null && $driver !== true && ! ($driver instanceOf Doctrine_Cache_Interface)){
            $msg = 'First argument should be instance of Doctrine_Cache_Interface or null.';
            throw new Doctrine_Query_Exception($msg);
        }
        $this->_resultCache = $driver;

        return $this->setResultCacheLifeSpan($timeToLive);
    }
    
    /**
     * useQueryCache
     *
     * @param Doctrine_Cache_Interface|bool $driver      cache driver
     * @param integer $timeToLive                        how long the cache entry is valid
     * @return Doctrine_Hydrate         this object
     */
    public function useQueryCache(Doctrine_Cache_Interface $driver, $timeToLive = null)
    {
        $this->_queryCache = $driver;
        return $this->setQueryCacheLifeSpan($timeToLive);
    }
    
    /**
     * expireCache
     *
     * @param boolean $expire       whether or not to force cache expiration
     * @return Doctrine_Hydrate     this object
     * @deprecated Use expireResultCache()
     */
    public function expireCache($expire = true)
    {
        return $this->expireResultCache($expire);
    }
    
    /**
     * expireCache
     *
     * @param boolean $expire       whether or not to force cache expiration
     * @return Doctrine_Hydrate     this object
     */
    public function expireResultCache($expire = true)
    {
        $this->_expireResultCache = true;
        return $this;
    }
    
    /**
     * expireQueryCache
     *
     * @param boolean $expire       whether or not to force cache expiration
     * @return Doctrine_Hydrate     this object
     */
    public function expireQueryCache($expire = true)
    {
        $this->_expireQueryCache = true;
        return $this;
    }
    
    /**
     * setCacheLifeSpan
     *
     * @param integer $timeToLive   how long the cache entry is valid
     * @return Doctrine_Hydrate     this object
     * @deprecated Use setResultCacheLifeSpan()
     */
    public function setCacheLifeSpan($timeToLive)
    {
        return $this->setResultCacheLifeSpan($timeToLive);
    }
    
    /**
     * setResultCacheLifeSpan
     *
     * @param integer $timeToLive   how long the cache entry is valid
     * @return Doctrine_Hydrate     this object
     */
    public function setResultCacheLifeSpan($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }
        $this->_resultCacheTTL = $timeToLive;

        return $this;
    }
    
    /**
     * setQueryCacheLifeSpan
     *
     * @param integer $timeToLive   how long the cache entry is valid
     * @return Doctrine_Hydrate     this object
     */
    public function setQueryCacheLifeSpan($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }
        $this->_queryCacheTTL = $timeToLive;

        return $this;
    }
    
    /**
     * getCacheDriver
     * returns the cache driver associated with this object
     *
     * @return Doctrine_Cache_Interface|boolean|null    cache driver
     * @deprecated Use getResultCacheDriver()
     */
    public function getCacheDriver()
    {
        return $this->getResultCacheDriver();
    }
    
    /**
     * getResultCacheDriver
     * returns the cache driver used for caching result sets
     *
     * @return Doctrine_Cache_Interface|boolean|null    cache driver
     */
    public function getResultCacheDriver()
    {
        if ($this->_resultCache instanceof Doctrine_Cache_Interface) {
            return $this->_resultCache;
        } else {
            return $this->_conn->getResultCacheDriver();
        }
    }
    
    /**
     * getQueryCacheDriver
     * returns the cache driver used for caching queries
     *
     * @return Doctrine_Cache_Interface|boolean|null    cache driver
     */
    public function getQueryCacheDriver()
    {
        if ($this->_queryCache instanceof Doctrine_Cache_Interface) {
            return $this->_queryCache;
        } else {
            return $this->_conn->getQueryCacheDriver();
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
     * Adds a DQL part to the internal parts collection.
     * 
     * @param string $queryPartName  The name of the query part.
     * @param string $queryPart      The actual query part to add.
     * @param boolean $append        Whether to append $queryPart to already existing
     *                               parts under the same $queryPartName. Defaults to FALSE
     *                               (previously added parts with the same name get overridden).
     */
    protected function _addDqlQueryPart($queryPartName, $queryPart, $append = false)
    {
        if ($append) {
            $this->_dqlParts[$queryPartName][] = $queryPart;
        } else {
            $this->_dqlParts[$queryPartName] = array($queryPart);
        }
        
        $this->_state = Doctrine_Query::STATE_DIRTY;
        return $this;     
    }
    
    /**
     * _processDqlQueryPart
     * parses given query part
     *
     * @param string $queryPartName     the name of the query part
     * @param array $queryParts         an array containing the query part data
     * @return Doctrine_Query           this object
     * @todo Better description. "parses given query part" ??? Then wheres the difference
     *       between process/parseQueryPart? I suppose this does something different.
     */
    protected function _processDqlQueryPart($queryPartName, $queryParts)
    {
        $this->removeSqlQueryPart($queryPartName);

        if (is_array($queryParts) && ! empty($queryParts)) {
            foreach ($queryParts as $queryPart) {
                $parser = $this->_getParser($queryPartName);
                $sql = $parser->parse($queryPart);
                if (isset($sql)) {
                    if ($queryPartName == 'limit' || $queryPartName == 'offset') {
                        $this->setSqlQueryPart($queryPartName, $sql);
                    } else {
                        $this->addSqlQueryPart($queryPartName, $sql);
                    }
                }
            }
        }
    }
    
    /**
     * _getParser
     * parser lazy-loader
     *
     * @throws Doctrine_Query_Exception     if unknown parser name given
     * @return Doctrine_Query_Part
     * @todo Doc/Description: What is the parameter for? Which parsers are available?
     */
    protected function _getParser($name)
    {
        if ( ! isset($this->_parsers[$name])) {
            $class = 'Doctrine_Query_' . ucwords(strtolower($name));

            Doctrine::autoload($class);

            if ( ! class_exists($class)) {
                throw new Doctrine_Query_Exception('Unknown parser ' . $name);
            }

            $this->_parsers[$name] = new $class($this, $this->_tokenizer);
        }

        return $this->_parsers[$name];
    }
    
    /**
     * Gets the SQL query that corresponds to this query object.
     * The returned SQL syntax depends on the connection driver that is used 
     * by this query object at the time of this method call.
     *
     * @param array $params
     */
    abstract public function getSqlQuery($params = array());
    
    /**
     * parseDqlQuery
     * parses a dql query
     *
     * @param string $query         query to be parsed
     * @return Doctrine_Query_Abstract  this object
     */
    abstract public function parseDqlQuery($query);
    
    /**
     * @deprecated
     */
    public function parseQuery($query)
    {
        return $this->parseDqlQuery($query);
    }
    
    /**
     * @deprecated
     */
    public function getQuery($params = array())
    {
        return $this->getSqlQuery($params);
    }
}
