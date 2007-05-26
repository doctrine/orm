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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Hydrate is a base class for Doctrine_RawSql and Doctrine_Query.
 * Its purpose is to populate object graphs.
 *
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hydrate
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

    /**
     * @var array $params                       query input parameters
     */
    protected $_params      = array();
    /**
     * @var Doctrine_Connection $conn           Doctrine_Connection object
     */
    protected $_conn;
    /**
     * @var Doctrine_View $_view                Doctrine_View object, when set this object will use the
     *                                          the query given by the view object for object population
     */
    protected $_view;
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
     */
    protected $_aliasMap         = array();
    /**
     *
     */
    protected $pendingAggregates = array();
    /**
     * @var array $aggregateMap             an array containing all aggregate aliases, keys as dql aliases
     *                                      and values as sql aliases
     */
    protected $aggregateMap      = array();
    /**
     * @var array $parts            SQL query string parts
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
     * @var integer $type                   the query type
     *
     * @see Doctrine_Query::* constants
     */
    protected $type            = self::SELECT;

    protected $_tableAliases   = array();
    /**
     * @var array $_tableAliasSeeds         A simple array keys representing table aliases and values
     *                                      as table alias seeds. The seeds are used for generating short table
     *                                      aliases.
     */
    protected $_tableAliasSeeds = array();
    /**
     * constructor
     *
     * @param Doctrine_Connection|null $connection
     */
    public function __construct($connection = null)
    {
        if ( ! ($connection instanceof Doctrine_Connection)) {
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
        }
        $this->_conn = $connection;
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

        return $alias;
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
            $alias = $char . ++$this->_tableAliasSeeds[$alias];
        }

        $this->_tableAliases[$alias] = $componentAlias;

        return $alias;
    }
    /**
     * getAliases
     * returns all aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->_tableAliases;
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
        $this->parts[$name][] = $part;

        return $this;
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
            $this->parts[$name] = array($part);
        } else {
            $this->parts[$name] = $part;
        }
        
        return $this;
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
    public function copyAliases(Doctrine_Hydrate $query)
    {
        $this->_tableAliases = $query->_tableAliases;

        return $this;
    }
    /**
     * createSubquery
     * creates a subquery
     *
     * @return Doctrine_Hydrate
     */
    public function createSubquery()
    {
        $class = get_class($this);
        $obj   = new $class();

        // copy the aliases to the subquery
        $obj->copyAliases($this);

        // this prevents the 'id' being selected, re ticket #307
        $obj->isSubquery(true);

        return $obj;
    }
    /**
     * limitSubqueryUsed
     * whether or not limit subquery was used
     *
     * @return boolean
     */
    public function isLimitSubqueryUsed()
    {
        return false;
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
    public function getQueryPart($part)
    {
        if ( ! isset($this->parts[$part])) {
            throw new Doctrine_Hydrate_Exception('Unknown query part ' . $part);
        }

        return $this->parts[$part];
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
        if (isset($this->parts[$name])) {
            if ($name == 'limit' || $name == 'offset') {
                $this->parts[$name] = false;
            } else {
                $this->parts[$name] = array();
            }
        } else {
            throw new Doctrine_Hydrate_Exception('Unknown query part ' . $part);
        }
        return $this;
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
     * getParams
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }
    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams(array $params = array()) {
        $this->_params = $params;
    }
    public function convertEnums($params)
    {
        return $params;
    }
    /**
     * setAliasMap
     * sets the whole component alias map
     *
     * @param array $map            alias map
     * @return Doctrine_Hydrate     this object
     */
    public function setAliasMap(array $map)
    {
        $this->_aliasMap = $map;

        return $this;
    }
    /**
     * getAliasMap
     * returns the component alias map
     *
     * @return array    component alias map
     */
    public function getAliasMap()
    {
        return $this->_aliasMap;
    }
    /**
     * mapAggregateValues
     * map the aggregate values of given dataset row to a given record
     *
     * @param Doctrine_Record $record
     * @param array $row
     * @return Doctrine_Record
     */
    public function mapAggregateValues($record, array $row, $alias)
    {
        $found = false;
        // aggregate values have numeric keys
        if (isset($row[0])) {
            // map each aggregate value   
            foreach ($row as $index => $value) {
                $agg = false;

                if (isset($this->_aliasMap[$alias]['agg'][$index])) {
                    $agg = $this->_aliasMap[$alias]['agg'][$index];
                }
                $record->mapValue($agg, $value);
                $found = true;
            }
        }
        return $found;
    }
    /**
     * execute
     * executes the dql query and populates all collections
     *
     * @param string $params
     * @return Doctrine_Collection            the root collection
     */
    public function execute($params = array(), $return = Doctrine::FETCH_RECORD)
    {
        $params = $this->_conn->convertBooleans(array_merge($this->_params, $params));
        $params = $this->convertEnums($params);

        if ( ! $this->_view) {
            $query = $this->getQuery($params);
        } else {
            $query = $this->_view->getSelectSql();
        }

        if ($this->isLimitSubqueryUsed() &&
            $this->_conn->getDBH()->getAttribute(Doctrine::ATTR_DRIVER_NAME) !== 'mysql') {

            $params = array_merge($params, $params);
        }

        if ($this->type !== self::SELECT) {
            return $this->_conn->exec($query, $params);
        }

        $stmt  = $this->_conn->execute($query, $params);
        $array = (array) $this->parseData($stmt);
        if (empty($this->_aliasMap)) {
            throw new Doctrine_Hydrate_Exception("Couldn't execute query. Component alias map was empty.");
        }
        // initialize some variables used within the main loop
        reset($this->_aliasMap);
        $rootMap     = current($this->_aliasMap);
        $rootAlias   = key($this->_aliasMap);
        $coll        = new Doctrine_Collection($rootMap['table']);
        $prev[$rootAlias] = $coll;

        // we keep track of all the collections
        $colls   = array();
        $colls[] = $coll;
        $prevRow = array();
        /**
         * iterate over the fetched data
         * here $data is a two dimensional array
         */
        foreach ($array as $data) {
            /**
             * remove duplicated data rows and map data into objects
             */
            foreach ($data as $tableAlias => $row) {
                // skip empty rows (not mappable)
                if (empty($row)) {
                    continue;
                }
                $alias = $this->getComponentAlias($tableAlias);
                $map   = $this->_aliasMap[$alias];

                // initialize previous row array if not set
                if ( ! isset($prevRow[$tableAlias])) {
                    $prevRow[$tableAlias] = array();
                }

                // don't map duplicate rows
                if ($prevRow[$tableAlias] !== $row) {
                    $identifiable = $this->isIdentifiable($row, $map['table']->getIdentifier());

                    if ($identifiable) {
                        // set internal data
                        $map['table']->setData($row);
                    }

                    // initialize a new record
                    $record = $map['table']->getRecord();

                    // map aggregate values (if any)
                    if($this->mapAggregateValues($record, $row, $alias)) {
                        $identifiable = true;
                    }


                    if ($alias == $rootAlias) {
                        // add record into root collection

                        if ($identifiable) {
                            $coll->add($record);
                            unset($prevRow);
                        }
                    } else {

                        $relation    = $map['relation'];
                        $parentAlias = $map['parent'];
                        $parentMap   = $this->_aliasMap[$parentAlias];
                        $parent      = $prev[$parentAlias]->getLast();

                        // check the type of the relation
                        if ($relation->isOneToOne()) {
                            if ( ! $identifiable) {
                                continue;
                            }
                            $prev[$alias] = $record;
                        } else {
                            // one-to-many relation or many-to-many relation
                            if ( ! $prev[$parentAlias]->getLast()->hasReference($relation->getAlias())) {
                                // initialize a new collection
                                $prev[$alias] = new Doctrine_Collection($map['table']);
                                $prev[$alias]->setReference($parent, $relation);
                            } else {
                                // previous entry found from memory
                                $prev[$alias] = $prev[$parentAlias]->getLast()->get($relation->getAlias());
                            }

                            $colls[] = $prev[$alias];

                            // add record to the current collection
                            if ($identifiable) {
                                $prev[$alias]->add($record);
                            }
                        }
                        // initialize the relation from parent to the current collection/record
                        $parent->set($relation->getAlias(), $prev[$alias]);
                    }

                    // following statement is needed to ensure that mappings
                    // are being done properly when the result set doesn't
                    // contain the rows in 'right order'

                    if ($prev[$alias] !== $record) {
                        $prev[$alias] = $record;
                    }
                }
                $prevRow[$tableAlias] = $row;
            }
        }
        // take snapshots from all initialized collections
        foreach(array_unique($colls) as $c) {
            $c->takeSnapshot();
        }

        return $coll;
    }
    /**
     * isIdentifiable
     * returns whether or not a given data row is identifiable (it contains
     * all primary key fields specified in the second argument)
     *
     * @param array $row
     * @param mixed $primaryKeys
     * @return boolean
     */
    public function isIdentifiable(array $row, $primaryKeys)
    {
        if (is_array($primaryKeys)) {
            foreach ($primaryKeys as $id) {
                if ($row[$id] == null) {
                    return false;
                }
            }
        } else {
            if ( ! isset($row[$primaryKeys])) {
                return false;
            }
        }
        return true;
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
                    if ($index > 0) {
                        $b[] = '(' . $tableAlias  . $field . ' = ' . $value
                             . ' OR ' . $tableAlias . $field . ' IS NULL)';
                    } else {
                        $b[] = $tableAlias . $field . ' = ' . $value;
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
     * parseData
     * parses the data returned by statement object
     *
     * @param mixed $stmt
     * @return array
     */
    public function parseData($stmt)
    {
        $array = array();

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /**
             * parse the data into two-dimensional array
             */
            foreach ($data as $key => $value) {
                $e = explode('__', $key);

                $field      = strtolower(array_pop($e));
                $tableAlias = strtolower(implode('__', $e));

                $data[$tableAlias][$field] = $value;

                unset($data[$key]);
            }
            $array[] = $data;
        }

        $stmt->closeCursor();
        return $array;
    }
    /**
     * @return string                   returns a string representation of this object
     */
    public function __toString()
    {
        return Doctrine_Lib::formatSql($this->getQuery());
    }
}
