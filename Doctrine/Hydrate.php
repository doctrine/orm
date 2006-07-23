<?php
Doctrine::autoload('Doctrine_Access');

class Doctrine_Hydrate extends Doctrine_Access {
    /**
     * @var array $fetchmodes               an array containing all fetchmodes
     */
    protected $fetchModes  = array();
    /**
     * @var array $tables                   an array containing all the tables used in the query
     */
    protected $tables      = array();
    /**
     * @var array $collections              an array containing all collections this parser has created/will create
     */
    protected $collections = array();
    /**
     * @var array $joins                    an array containing all table joins
     */
    protected $joins       = array();
    /**
     * @var array $data                     fetched data
     */
    protected $data        = array();
    /**
     * @var Doctrine_Session $session       Doctrine_Session object
     */
    protected $session;
    /**
     * @var Doctrine_View $view             Doctrine_View object
     */
    protected $view;
    

    protected $inheritanceApplied = false;

    protected $aggregate  = false;
    /**
     * @var array $tableAliases
     */
    protected $tableAliases = array();
    /**
     * @var array $tableIndexes
     */
    protected $tableIndexes = array();
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
     * @param Doctrine_Session $session
     */
    public function __construct(Doctrine_Session $session) {
        $this->session = $session;
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
     * @return Doctrine_Session
     */
    public function getSession() {
        return $this->session;
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

        $coll->populate($this);
        return $coll;
    }
    /**
     * getData
     * @param $key                      the component name
     * @return array                    the data row for the specified component
     */
    final public function getData($key) {
        if(isset($this->data[$key]) && is_array($this->data[$key]))
            return $this->data[$key];

        return array();
    }
    /**
     * execute
     * executes the dql query and populates all collections
     *
     * @param string $params
     * @return Doctrine_Collection            the root collection
     */
    public function execute($params = array()) {
        $this->data = array();
        $this->collections = array();
        
        if( ! $this->view)
            $query = $this->getQuery();
        else
            $query = $this->view->getSelectSql();

        switch(count($this->tables)):
            case 0:
                throw new Doctrine_Exception("No tables selected");
            break;
            case 1:
                $keys  = array_keys($this->tables);

                $name  = $this->tables[$keys[0]]->getComponentName();
                $stmt  = $this->session->execute($query,$params);

                while($data = $stmt->fetch(PDO::FETCH_ASSOC)):
                    foreach($data as $key => $value):
                        $e = explode("__",$key);
                        if(count($e) > 1) {
                            $data[$e[1]] = $value;
                        } else {
                            $data[$e[0]] = $value;
                        }
                        unset($data[$key]);
                    endforeach;
                    $this->data[$name][] = $data;
                endwhile;

                return $this->getCollection($keys[0]);
            break;
            default:
                $keys  = array_keys($this->tables);
                $root  = $keys[0];

                $stmt  = $this->session->execute($query,$params);

                $previd = array();

                $coll        = $this->getCollection($root);
                $prev[$root] = $coll;

                $array = $this->parseData($stmt);


                $colls = array();
                
                foreach($array as $data) {
                    /**
                     * remove duplicated data rows and map data into objects
                     */
                    foreach($data as $key => $row) {
                        if(empty($row))
                            continue;
                        

                        $ids  = $this->tables[$key]->getIdentifier();
                        
                        $emptyID = false;
                        if(is_array($ids)) {
                            foreach($ids as $id) {
                                if($row[$id] == null) {
                                    $emptyID = true;
                                    break;
                                }
                            }
                        } else {
                            if( ! isset($row[$ids]))
                                $emptyID = true;
                        }


                        $name    = $key;

                        if($emptyID) {


                            $pointer = $this->joins[$name];
                            $path    = array_search($name, $this->tableAliases);
                            $tmp     = explode(".", $path);
                            $alias   = end($tmp);
                            unset($tmp);
                            $fk      = $this->tables[$pointer]->getForeignKey($alias);

                            if( ! isset($prev[$pointer]) )
                                continue;

                            $last    = $prev[$pointer]->getLast();

                            switch($fk->getType()):
                                case Doctrine_Relation::ONE_COMPOSITE:
                                case Doctrine_Relation::ONE_AGGREGATE:
                                
                                break;
                                default:
                                    if($last instanceof Doctrine_Record) {
                                        if( ! $last->hasReference($alias)) {
                                            $prev[$name] = $this->getCollection($name);
                                            $last->initReference($prev[$name],$fk);
                                        }
                                    }
                            endswitch;

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

                                $pointer = $this->joins[$name];
                                $path    = array_search($name, $this->tableAliases);
                                $tmp     = explode(".", $path);
                                $alias   = end($tmp);
                                unset($tmp);
                                $fk      = $this->tables[$pointer]->getForeignKey($alias);
                                $last    = $prev[$pointer]->getLast();

                                switch($fk->getType()):
                                    case Doctrine_Relation::ONE_COMPOSITE:
                                    case Doctrine_Relation::ONE_AGGREGATE:

                                        // one-to-one relation

                                        $last->internalSet($fk->getLocal(), $record->getIncremented());

                                        $last->initSingleReference($record, $fk);

                                        $prev[$name] = $record;
                                    break;
                                    default:

                                        // one-to-many relation or many-to-many relation

                                        if( ! $last->hasReference($alias)) {
                                            $prev[$name] = $this->getCollection($name);
                                            $last->initReference($prev[$name], $fk);
                                        } else {
                                            // previous entry found from identityMap
                                            $prev[$name] = $last->get($alias);
                                        }

                                        $last->addReference($record, $fk);
                                endswitch;
                            }
                        }

                        $previd[$name] = $row;
                    }
                }

                return $coll;
        endswitch;
    }
    /**
     * applyInheritance
     * applies column aggregation inheritance to DQL query
     *
     * @return string
     */
    final public function applyInheritance() {
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

                if(count($e) > 1) {
                    $data[$e[0]][$e[1]] = $value;
                } else {
                    $data[0][$e[0]] = $value;
                }
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
}
?>
