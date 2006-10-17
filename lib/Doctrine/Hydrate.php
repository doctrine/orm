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
Doctrine::autoload('Doctrine_Access');
/**
 * Doctrine_Hydrate is a base class for Doctrine_RawSql and Doctrine_Query.
 * Its purpose is to populate object graphs.
 *
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
abstract class Doctrine_Hydrate extends Doctrine_Access {
    /**
     * @var array $fetchmodes                   an array containing all fetchmodes
     */
    protected $fetchModes  = array();
    /**
     * @var array $tables                       an array containing all the tables used in the query
     */
    protected $tables      = array();
    /**
     * @var array $collections                  an array containing all collections 
     *                                          this hydrater has created/will create
     */
    protected $collections = array();
    /**
     * @var array $joins                        an array containing all table joins
     */
    protected $joins       = array();
    /**
     * @var array $data                         fetched data
     */
    protected $data        = array();
    /**
     * @var array $params                       query input parameters
     */
    protected $params      = array();
    /**
     * @var Doctrine_Connection $connection     Doctrine_Connection object
     */
    protected $connection;
    /**
     * @var Doctrine_View $view                 Doctrine_View object
     */
    protected $view;
    /**
     * @var boolean $inheritanceApplied
     */
    protected $inheritanceApplied = false;
    /**
     * @var boolean $aggregate
     */
    protected $aggregate  = false;
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
    
    protected $components   = array();
    
    protected $pendingAggregates = array();

    protected $aggregateMap      = array();
    /**
     * @var array $parts            SQL query string parts
     */
    protected $parts = array(
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
    /**
     * constructor
     *
     * @param Doctrine_Connection|null $connection
     */
    public function __construct($connection = null) {
        if( ! ($connection instanceof Doctrine_Connection))
            $connection = Doctrine_Manager::getInstance()->getCurrentConnection();

        $this->connection = $connection;
    }
    /**
     * getComponentAliases
     *
     * @return array
     */
    public function getComponentAliases() {
        return $this->compAliases;
    }
    /**
     * getTableAliases
     *
     * @return array
     */
    public function getTableAliases() {
        return $this->tableAliases;
    }
    /**
     * getTableIndexes
     *
     * @return array
     */
    public function getTableIndexes() {
        return $this->tableIndexes;
    }
    /**
     * copyAliases
     *
     * @return void
     */
    public function copyAliases(Doctrine_Hydrate $query) {
        $this->compAliases  = $query->getComponentAliases();
        $this->tableAliases = $query->getTableAliases();
        $this->tableIndexes = $query->getTableIndexes();
        
        return $this;
    }
    
    public function getPathAlias($path) {
        $s = array_search($path, $this->compAliases);
        if($s === false)
            return $path;
    
        return $s;
    }
    /**
     * createSubquery
     * 
     * @return Doctrine_Hydrate
     */
    public function createSubquery() {
        $class = get_class($this);
        $obj   = new $class();
        
        // copy the aliases to the subquery
        $obj->copyAliases($this);

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
    public function isLimitSubqueryUsed() {
        return false;
    }

    /**
     * remove
     *
     * @param $name
     */
	public function remove($name) {
		if(isset($this->parts[$name])) {
			if($name == "limit" || $name == "offset")
				$this->parts[$name] = false;
			else 
				$this->parts[$name] = array(); 
		}
		return $this;
	}
    /**
     * clear
     * resets all the variables
     * 
     * @return void
     */
    protected function clear() {
        $this->fetchModes   = array();
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
        $this->aggregate        = false;
        $this->data             = array();
        $this->collections      = array();
        $this->joins            = array();
        $this->tableIndexes     = array();
        $this->tableAliases     = array();
    }
    /**
     * getConnection
     *
     * @return Doctrine_Connection
     */
    public function getConnection() {
        return $this->connection;
    }
    /**
     * setView
     * sets a database view this query object uses
     * this method should only be called internally by doctrine
     *
     * @param Doctrine_View $view       database view
     * @return void
     */
    public function setView(Doctrine_View $view) {
        $this->view = $view;
    }
    /**
     * getView
     *
     * @return Doctrine_View
     */
    public function getView() {
        return $this->view;
    }
    /**
     * getTableAlias
     *
     * @param string $path
     * @return string
     */
    final public function getTableAlias($path) {
        if(isset($this->compAliases[$path]))
            $path = $this->compAliases[$path];

        if( ! isset($this->tableAliases[$path]))
            return false;

        return $this->tableAliases[$path];
    }
    /**
     * getCollection
     *
     * @parma string $name              component name
     * @param integer $index
     */
    private function getCollection($name) {
        $table = $this->tables[$name];
        if( ! isset($this->fetchModes[$name]))
            return new Doctrine_Collection($table);

        switch($this->fetchModes[$name]):
            case Doctrine::FETCH_BATCH:
                $coll = new Doctrine_Collection_Batch($table);
            break;
            case Doctrine::FETCH_LAZY:
                $coll = new Doctrine_Collection_Lazy($table);
            break;
            case Doctrine::FETCH_OFFSET:
                $coll = new Doctrine_Collection_Offset($table);
            break;
            case Doctrine::FETCH_IMMEDIATE:
                $coll = new Doctrine_Collection_Immediate($table);
            break;
            case Doctrine::FETCH_LAZY_OFFSET:
                $coll = new Doctrine_Collection_LazyOffset($table);
            break;
            default:
                throw new Doctrine_Exception("Unknown fetchmode");
        endswitch;

        return $coll;
    }
    /**
     * convertBoolean
     * converts boolean to integers
     *
     * @param mixed $item
     * @return void
     */
    public static function convertBoolean(&$item) {
        if(is_bool($item))
            $item = (int) $item;
    }
    /**
     * execute
     * executes the dql query and populates all collections
     *
     * @param string $params
     * @return Doctrine_Collection            the root collection
     */
    public function execute($params = array(), $return = Doctrine::FETCH_RECORD) {
        $this->collections = array();
        
        $params = array_merge($this->params, $params);
        
        array_walk($params, array(__CLASS__, 'convertBoolean'));
        
        if( ! $this->view)
            $query = $this->getQuery();
        else
            $query = $this->view->getSelectSql();

        if($this->isLimitSubqueryUsed())
            $params = array_merge($params, $params);

        $stmt  = $this->connection->execute($query,$params);

        if($this->aggregate)
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($this->tables) == 0)
            throw new Doctrine_Query_Exception("No components selected");

        $keys  = array_keys($this->tables);
        $root  = $keys[0];

        $previd = array();

        $coll        = $this->getCollection($root);
        $prev[$root] = $coll;


        if($this->aggregate)
            $return = Doctrine::FETCH_ARRAY;

        $array = $this->parseData($stmt);


        if($return == Doctrine::FETCH_ARRAY)
            return $array;


        foreach($array as $data) {
            /**
             * remove duplicated data rows and map data into objects
             */
            foreach($data as $key => $row) {
                if(empty($row))
                    continue;


                $ids     = $this->tables[$key]->getIdentifier();
                $name    = $key;

                if($this->isIdentifiable($row, $ids)) {

                    $prev = $this->initRelated($prev, $name);
                        // aggregate values have numeric keys

                        if(isset($row[0])) {
                            $path    = array_search($name, $this->tableAliases);
                            $alias   = $this->getPathAlias($path);
                            
                            //print_r($this->pendingAggregates);
                            foreach($row as $index => $value) {
                                $agg = false;

                                if(isset($this->pendingAggregates[$alias][$index]))
                                    $agg = $this->pendingAggregates[$alias][$index][0];

                                $prev[$name]->setAggregateValue($agg, $value);
                            }

                        }
                    continue;
                }


                if( ! isset($previd[$name]))
                            $previd[$name] = array();

                if($previd[$name] !== $row) {
                        // set internal data

                        $this->tables[$name]->setData($row);

                        // initialize a new record
                        $record = $this->tables[$name]->getRecord();


                    if($name == $root) {

                        // add record into root collection
                        $coll->add($record);
                        unset($previd);

                    } else {

                            $prev = $this->addRelated($prev, $name, $record);
                    }
    
                    // following statement is needed to ensure that mappings
                    // are being done properly when the result set doesn't
                    // contain the rows in 'right order'
    
                    if($prev[$name] !== $record)
                        $prev[$name] = $record;
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
    public function initRelated(array $prev, $name) {
        $pointer = $this->joins[$name];
        $path    = array_search($name, $this->tableAliases);
        $tmp     = explode(".", $path);
        $alias   = end($tmp);

        if( ! isset($prev[$pointer]) )
            return $prev;

        $fk      = $this->tables[$pointer]->getRelation($alias);

        if( ! $fk->isOneToOne()) {
            if($prev[$pointer]->getLast() instanceof Doctrine_Record) {
                if( ! $prev[$pointer]->getLast()->hasReference($alias)) {
                    $prev[$name] = $this->getCollection($name);
                    $prev[$pointer]->getLast()->initReference($prev[$name],$fk);
                } else 
                    $prev[$name] = $prev[$pointer]->getLast()->get($alias);
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
    public function addRelated(array $prev, $name, Doctrine_Record $record) {
        $pointer = $this->joins[$name];

        $path    = array_search($name, $this->tableAliases);
        $tmp     = explode(".", $path);
        $alias   = end($tmp);

        $fk      = $this->tables[$pointer]->getRelation($alias);

        if($fk->isOneToOne()) {
            $prev[$pointer]->getLast()->set($fk->getAlias(), $record);

            $prev[$name] = $record;
        } else {
            // one-to-many relation or many-to-many relation

            if( ! $prev[$pointer]->getLast()->hasReference($alias)) {
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
    public function isIdentifiable(array $row, $ids) {
        if(is_array($ids)) {
            foreach($ids as $id) {
                if($row[$id] == null)
                    return true;
            }
        } else {
            if( ! isset($row[$ids]))
                return true;
        }
        return false;
    }
    /**
     * applyInheritance
     * applies column aggregation inheritance to DQL / SQL query
     *
     * @return string
     */
    public function applyInheritance() {
        // get the inheritance maps
        $array = array();

        foreach($this->tables as $alias => $table):
            $array[$alias][] = $table->getInheritanceMap();
        endforeach;

        // apply inheritance maps
        $str = "";
        $c = array();

        $index = 0;
        foreach($array as $tname => $maps) {
            $a = array();
            foreach($maps as $map) {
                $b = array();
                foreach($map as $field => $value) {
                    if($index > 0)
                        $b[] = "(".$tname.".$field = $value OR $tname.$field IS NULL)";
                    else
                        $b[] = $tname.".$field = $value";
                }
                if( ! empty($b)) $a[] = implode(" AND ",$b);
            }
            if( ! empty($a)) $c[] = implode(" AND ",$a);
            $index++;
        }

        $str .= implode(" AND ",$c);

        return $str;
    }
    /**
     * parseData
     * parses the data returned by PDOStatement
     *
     * @return array
     */
    public function parseData(PDOStatement $stmt) {
        $array = array();
        
        while($data = $stmt->fetch(PDO::FETCH_ASSOC)):
            /**
             * parse the data into two-dimensional array
             */
            foreach($data as $key => $value):
                $e = explode("__",$key);

                $field     = strtolower( array_pop($e) );
                $component = strtolower( implode("__",$e) );

                $data[$component][$field] = $value;

                unset($data[$key]);
            endforeach;
            $array[] = $data;
        endwhile;

        $stmt->closeCursor();
        return $array;
    }
    /**
     * returns a Doctrine_Table for given name
     *
     * @param string $name              component name
     * @return Doctrine_Table
     */
    public function getTable($name) {
        return $this->tables[$name];
    }
    /**
     * @return string                   returns a string representation of this object
     */
    public function __toString() {
        return Doctrine_Lib::formatSql($this->getQuery());
    }
}

