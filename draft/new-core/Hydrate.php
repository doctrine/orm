<?php
/*
 *  $Id: Hydrate.php 1255 2007-04-16 14:43:12Z pookey $
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
Doctrine::autoload('Doctrine_Access');
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
 * @version     $Revision: 1255 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Hydrate extends Doctrine_Access
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
     * @var array $tables                       an array containing all the tables used in the query
     */
    protected $tables      = array();
    /**
     * @var array $joins                        an array containing all table joins
     */
    protected $joins       = array();
    /**
     * @var array $params                       query input parameters
     */
    protected $params      = array();
    /**
     * @var Doctrine_Connection $conn           Doctrine_Connection object
     */
    protected $conn;
    /**
     * @var Doctrine_View $view                 Doctrine_View object
     */
    protected $view;
    /**
     * @var boolean $inheritanceApplied
     */
    protected $inheritanceApplied = false;
    /**
     * @var array $compAliases
     */
    protected $compAliases  = array();
    /**
     * @var array $tableAliases
     */
    protected $tableAliases = array();
    /**
     * @var array $tableIndexes
     */
    protected $tableIndexes = array();

    protected $pendingAggregates = array();
    
    protected $subqueryAggregates = array();
    /**
     * @var array $aggregateMap             an array containing all aggregate aliases, keys as dql aliases
     *                                      and values as sql aliases
     */
    protected $aggregateMap      = array();
    /**
     * @var Doctrine_Hydrate_Alias $aliasHandler
     */
    protected $aliasHandler;
    /**
     * @var array $parts            SQL query string parts
     */
    protected $parts = array(
        'select'    => array(),
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
        $this->conn = $connection;
        $this->aliasHandler = new Doctrine_Hydrate_Alias();
    }
    /**
     * getComponentAliases
     *
     * @return array
     */
    public function getComponentAliases()
    {
        return $this->compAliases;
    }
    /**
     * getTableAliases
     *
     * @return array
     */
    public function getTableAliases()
    {
        return $this->tableAliases;
    }
    /**
     * getTableIndexes
     *
     * @return array
     */
    public function getTableIndexes()
    {
        return $this->tableIndexes;
    }
    /**
     * getTables
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }
    /**
     * copyAliases
     *
     * @return void
     */
    public function copyAliases(Doctrine_Hydrate $query)
    {
        $this->compAliases  = $query->getComponentAliases();
        $this->tableAliases = $query->getTableAliases();
        $this->tableIndexes = $query->getTableIndexes();
        $this->aliasHandler = $query->aliasHandler;

        return $this;
    }

    public function getPathAlias($path)
    {
        $s = array_search($path, $this->compAliases);
        if ($s === false)
            return $path;

        return $s;
    }
    /**
     * createSubquery
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
     * getQuery
     *
     * @return string
     */
    abstract public function getQuery();
    /**
     * limitSubqueryUsed
     *
     * @return boolean
     */
    public function isLimitSubqueryUsed()
    {
        return false;
    }

    /**
     * remove
     *
     * @param $name
     */
    public function remove($name)
    {
        if (isset($this->parts[$name])) {
            if ($name == "limit" || $name == "offset") {
                $this->parts[$name] = false;
            } else {
                $this->parts[$name] = array();
            }
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
        $this->tables       = array();
        $this->parts = array(
                  "select"    => array(),
                  "from"      => array(),
                  "join"      => array(),
                  "where"     => array(),
                  "groupby"   => array(),
                  "having"    => array(),
                  "orderby"   => array(),
                  "limit"     => false,
                  "offset"    => false,
                );
        $this->inheritanceApplied = false;
        $this->joins            = array();
        $this->tableIndexes     = array();
        $this->tableAliases     = array();
        $this->aliasHandler->clear();
    }
    /**
     * getConnection
     *
     * @return Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->conn;
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
        $this->view = $view;
    }
    /**
     * getView
     * returns the view associated with this query object (if any)
     *
     * @return Doctrine_View        the view associated with this query object
     */
    public function getView()
    {
        return $this->view;
    }
    /**
     * getParams
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;                           	
    }
    /**
     * getTableAlias
     *
     * @param string $path
     * @return string
     */
    final public function getTableAlias($path)
    {
        if (isset($this->compAliases[$path])) {
            $path = $this->compAliases[$path];
        }
        if ( ! isset($this->tableAliases[$path])) {
            return false;
        }
        return $this->tableAliases[$path];
    }
    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams(array $params = array()) {
        $this->params = $params;
    }
    /**
     * execute
     * executes the dql query and populates all collections
     *
     * @param string $params
     * @return Doctrine_Collection            the root collection
     */
    public function execute($params = array(), $return = Doctrine::FETCH_RECORD) {
        $params = $this->conn->convertBooleans(array_merge($this->params, $params));

        if ( ! $this->view) {
            $query = $this->getQuery($params);
        } else {
            $query = $this->view->getSelectSql();
        }

        if ($this->isLimitSubqueryUsed() && 
            $this->conn->getDBH()->getAttribute(Doctrine::ATTR_DRIVER_NAME) !== 'mysql') {
            
            $params = array_merge($params, $params);
        }
        $stmt  = $this->conn->execute($query, $params);

        if (count($this->tables) == 0) {
            throw new Doctrine_Query_Exception('No components selected');
        }

        $keys  = array_keys($this->tables);
        $root  = $keys[0];

        $previd = array();

        $coll        = $this->getCollection($root);
        $prev[$root] = $coll;

        if ($this->aggregate) {
            $return = Doctrine::FETCH_ARRAY;
        }

        $array = $this->parseData($stmt);

        if ($return == Doctrine::FETCH_ARRAY) {
            return $array;
        }

        foreach ($array as $data) {
            /**
             * remove duplicated data rows and map data into objects
             */
            foreach ($data as $key => $row) {
                if (empty($row)) {
                    continue;
                }
                //$key = array_search($key, $this->shortAliases);

                foreach ($this->tables as $k => $t) {
                    if ( ! strcasecmp($key, $k)) {
                        $key = $k;
                    }
                }

                if ( !isset($this->tables[$key]) ) {
                    throw new Doctrine_Exception('No table named ' . $key . ' found.');
                }
                $ids     = $this->tables[$key]->getIdentifier();
                $name    = $key;

                if ($this->isIdentifiable($row, $ids)) {
                    if ($name !== $root) {
                        $prev = $this->initRelated($prev, $name);
                    }
                    // aggregate values have numeric keys
                    if (isset($row[0])) {
                        $component = $this->tables[$name]->getComponentName();

                        // if the collection already has objects, get the last object
                        // otherwise create a new one where the aggregate values are being mapped

                        if ($prev[$name]->count() > 0) {
                            $record = $prev[$name]->getLast();
                        } else {
                            $record = new $component();
                            $prev[$name]->add($record);
                        }

                        $path    = array_search($name, $this->tableAliases);
                        $alias   = $this->getPathAlias($path);

                        // map each aggregate value
                        foreach ($row as $index => $value) {
                            $agg = false;

                            if (isset($this->pendingAggregates[$alias][$index])) {
                                $agg = $this->pendingAggregates[$alias][$index][3];
                            } elseif (isset($this->subqueryAggregates[$alias][$index])) {
                                $agg = $this->subqueryAggregates[$alias][$index];
                            }

                            $record->mapValue($agg, $value);
                        }
                    }

                    continue;

                }

                if ( ! isset($previd[$name])) {
                    $previd[$name] = array();
                }              
                if ($previd[$name] !== $row) {
                    // set internal data

                    $this->tables[$name]->setData($row);

                    // initialize a new record

                    $record = $this->tables[$name]->getRecord();

                    // aggregate values have numeric keys
                    if (isset($row[0])) {
                        $path    = array_search($name, $this->tableAliases);
                        $alias   = $this->getPathAlias($path);

                        // map each aggregate value
                        foreach ($row as $index => $value) {
                            $agg = false;

                            if (isset($this->pendingAggregates[$alias][$index])) {
                                $agg = $this->pendingAggregates[$alias][$index][3];
                            } elseif (isset($this->subqueryAggregates[$alias][$index])) {
                                $agg = $this->subqueryAggregates[$alias][$index];
                            }
                            $record->mapValue($agg, $value);
                        }
                    }

                    if ($name == $root) {
                        // add record into root collection

                        $coll->add($record);
                        unset($previd);

                    } else {
                        $prev = $this->addRelated($prev, $name, $record);
                    }

                    // following statement is needed to ensure that mappings
                    // are being done properly when the result set doesn't
                    // contain the rows in 'right order'

                    if ($prev[$name] !== $record) {
                        $prev[$name] = $record;
                    }
                }

                $previd[$name] = $row;
            }
        }

        return $coll;
    }
    /**
     * initRelation
     *
     * @param array $prev
     * @param string $name
     * @return array
     */
    public function initRelated(array $prev, $name)
    {
        $pointer = $this->joins[$name];
        $path    = array_search($name, $this->tableAliases);
        $tmp     = explode('.', $path);
        $alias   = end($tmp);

        if ( ! isset($prev[$pointer]) ) {
            return $prev;
        }
        $fk      = $this->tables[$pointer]->getRelation($alias);

        if ( ! $fk->isOneToOne()) {
            if ($prev[$pointer]->getLast() instanceof Doctrine_Record) {
                if ( ! $prev[$pointer]->getLast()->hasReference($alias)) {
                    $prev[$name] = $this->getCollection($name);
                    $prev[$pointer]->getLast()->initReference($prev[$name],$fk);
                } else {
                    $prev[$name] = $prev[$pointer]->getLast()->get($alias);
                }
            }
        }

        return $prev;
    }
    /**
     * addRelated
     *
     * @param array $prev
     * @param string $name
     * @return array
     */
    public function addRelated(array $prev, $name, Doctrine_Record $record)
    {
        $pointer = $this->joins[$name];

        $path    = array_search($name, $this->tableAliases);
        $tmp     = explode('.', $path);
        $alias   = end($tmp);

        $fk      = $this->tables[$pointer]->getRelation($alias);

        if ($fk->isOneToOne()) {
            $prev[$pointer]->getLast()->set($fk->getAlias(), $record);

            $prev[$name] = $record;
        } else {
            // one-to-many relation or many-to-many relation

            if ( ! $prev[$pointer]->getLast()->hasReference($alias)) {
                $prev[$name] = $this->getCollection($name);
                $prev[$pointer]->getLast()->initReference($prev[$name], $fk);

            } else {
                // previous entry found from memory
                $prev[$name] = $prev[$pointer]->getLast()->get($alias);
            }

            $prev[$pointer]->getLast()->addReference($record, $fk);
        }
        return $prev;
    }
    /**
     * isIdentifiable
     * returns whether or not a given data row is identifiable (it contains
     * all id fields specified in the second argument)
     *
     * @param array $row
     * @param mixed $ids
     * @return boolean
     */
    public function isIdentifiable(array $row, $ids)
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if ($row[$id] == null)
                    return true;
            }
        } else {
            if ( ! isset($row[$ids])) {
                return true;
            }
        }
        return false;
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

        foreach ($this->tables as $alias => $table) {
            $array[$alias][] = $table->inheritanceMap;
        }

        // apply inheritance maps
        $str = "";
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

                $field     = strtolower(array_pop($e));
                $component = strtolower(implode('__', $e));

                $data[$component][$field] = $value;

                unset($data[$key]);
            }
            $array[] = $data;
        }

        $stmt->closeCursor();
        return $array;
    }
    /**
     * returns a Doctrine_Table for given name
     *
     * @param string $name              component name
     * @return Doctrine_Table|boolean
     */
    public function getTable($name)
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }
        return false;
    }
    /**
     * @return string                   returns a string representation of this object
     */
    public function __toString()
    {
        return Doctrine_Lib::formatSql($this->getQuery());
    }
}
