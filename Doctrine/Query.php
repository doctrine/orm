<?php
require_once("Access.php");
/**
 * Doctrine_Query
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Query extends Doctrine_Access {
    /**
     * @var array $fetchmodes               an array containing all fetchmodes
     */
    private $fetchModes  = array();
    /**
     * @var array $tables                   an array containing all the tables used in the query
     */
    private $tables      = array();
    /**
     * @var array $collections              an array containing all collections this parser has created/will create
     */
    private $collections = array();

    private $joined      = array();
    
    private $joins       = array();
    /**
     * @var array $data                     fetched data
     */
    private $data        = array();
    /**
     * @var Doctrine_Session $session       Doctrine_Session object
     */
    private $session;
    /**
     * @var Doctrine_View $view             Doctrine_View object
     */
    private $view;
    

    private $inheritanceApplied = false;

    private $aggregate  = false;
    /**
     * @var array $connectors               component connectors
     */
    private $connectors  = array();
    /**
     * @var array $tableAliases
     */
    private $tableAliases = array();
    /**
     * @var array $tableIndexes
     */
    private $tableIndexes = array();
    /**
     * @var array $dql                      DQL query string parts
     */
    protected $dql = array(
        "columns"   => array(),
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
     * @var array $parts            SQL query string parts
     */
    protected $parts = array(
        "columns"   => array(),
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
     * clear
     * resets all the variables
     * 
     * @return void
     */
    private function clear() {
        $this->fetchModes   = array();
        $this->tables       = array();

        $this->parts = array(
                  "columns"   => array(),
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
        $this->aggregate    = false;
        $this->data         = array();
        $this->connectors   = array();
        $this->collections  = array();
        $this->joined       = array();
        $this->joins        = array();
    }
    /**
     * loadFields      
     * loads fields for a given table and
     * constructs a little bit of sql for every field
     *
     * fields of the tables become: [tablename].[fieldname] as [tablename]__[fieldname]
     *
     * @access private
     * @param object Doctrine_Table $table       a Doctrine_Table object
     * @param integer $fetchmode                 fetchmode the table is using eg. Doctrine::FETCH_LAZY
     * @param array $names                      fields to be loaded (only used in lazy property loading)
     * @return void
     */
    private function loadFields(Doctrine_Table $table, $fetchmode, array $names) {
        $name = $table->getComponentName();

        switch($fetchmode):
            case Doctrine::FETCH_OFFSET:
                $this->limit = $table->getAttribute(Doctrine::ATTR_COLL_LIMIT);
            case Doctrine::FETCH_IMMEDIATE:
                if( ! empty($names))
                    throw new Doctrine_Exception("Lazy property loading can only be used with fetching strategies lazy, batch and lazyoffset.");

                $names  = $table->getColumnNames();
            break;
            case Doctrine::FETCH_LAZY_OFFSET:
                $this->limit = $table->getAttribute(Doctrine::ATTR_COLL_LIMIT);
            case Doctrine::FETCH_LAZY:
            case Doctrine::FETCH_BATCH:
                $names = array_merge($table->getPrimaryKeys(), $names);
            break;
            default:
                throw new Doctrine_Exception("Unknown fetchmode.");
        endswitch;
        
        $component          = $table->getComponentName(); 

        $this->fetchModes[$component] = $fetchmode;
        $tablename      = $this->tableAliases[$component];           
        $count = count($this->tables);

        foreach($names as $name) {
            if($count == 0) {
                $this->parts["columns"][] = $tablename.".".$name;
            } else {
                $this->parts["columns"][] = $tablename.".".$name." AS ".$component."__".$name;
            }
        }
    }
    /** 
     * sets a query part
     *
     * @param string $name
     * @param array $args
     * @return void
     */
    public function __call($name, $args) {
        $name = strtolower($name);
        if(isset($this->parts[$name])) {
            $method = "parse".ucwords($name);
            switch($name):
                case "where":
                case "having":
                    $this->parts[$name] = array($this->$method($args[0]));
                break;
                case "limit":
                case "offset":
                    if($args[0] == null)
                        $args[0] = false;

                    $this->parts[$name] = $args[0];
                break;
                case "from":
                    $this->parts['columns'] = array();
                    $this->parts['join']    = array();
                    $this->joins            = array();
                    $this->tables           = array();
                    $this->fetchModes       = array();
                default:
                    $this->parts[$name] = array();
                    $this->$method($args[0]);
            endswitch;
        } else 
            throw new Doctrine_Query_Exception("Unknown overload method");

        return $this;
    }
    /**
     * returns a query part
     *
     * @param $name         query part name
     * @return mixed
     */
    public function get($name) {
        if( ! isset($this->parts[$name]))
            return false;

        return $this->parts[$name];
    }
    /**
     * sets a query part
     *
     * @param $name         query part name
     * @param $value        query part value
     * @return boolean
     */
    public function set($name, $value) {

        if(isset($this->parts[$name])) {
            $method = "parse".ucwords($name);
            switch($name):
                case "where":
                case "having":
                    $this->parts[$name] = array($this->$method($value));
                break;
                case "limit":
                case "offset": 
                    if($value == null)
                        $value = false;

                    $this->parts[$name] = $value;
                break;
                case "from":
                    $this->parts['columns'] = array();
                    $this->parts['join']    = array();
                    $this->joins            = array();
                    $this->tables           = array();
                    $this->fetchModes       = array();
                default:
                    $this->parts[$name] = array();
                    $this->$method($value);
            endswitch;
            
            return true;
        }
        return false;
    }
    /**
     * returns the built sql query
     *
     * @return string
     */
    final public function getQuery() {
        if(empty($this->parts["columns"]) || empty($this->parts["from"]))
            return false;

        // build the basic query
        $q = "SELECT ".implode(", ",$this->parts["columns"]).
             " FROM ";
        
        foreach($this->parts["from"] as $tname => $bool) {
            $a[] = $tname;
        }
        $q .= implode(", ",$a);
        
        if( ! empty($this->parts['join'])) {
            foreach($this->parts['join'] as $part) {
                $q .= " ".implode(' ', $part);
            }
        }

        $this->applyInheritance();
        if( ! empty($this->parts["where"]))
            $q .= " WHERE ".implode(" ",$this->parts["where"]);

        if( ! empty($this->parts["groupby"]))
            $q .= " GROUP BY ".implode(", ",$this->parts["groupby"]);

        if( ! empty($this->parts["having"]))
            $q .= " HAVING ".implode(" ",$this->parts["having"]);

        if( ! empty($this->parts["orderby"]))
            $q .= " ORDER BY ".implode(", ",$this->parts["orderby"]);

        if( ! empty($this->parts["limit"]) || ! empty($this->offset))
            $q = $this->session->modifyLimitQuery($q,$this->parts["limit"],$this->offset);

        return $q;
    }
    /**
     * sql delete for mysql
     */
    final public function buildDelete() {
        if(empty($this->parts["columns"]) || empty($this->parts["from"]))
            return false;    
        
        $a = array_merge(array_keys($this->parts["from"]),$this->joined);
        $q = "DELETE ".implode(", ",$a)." FROM ";
        $a = array();

        foreach($this->parts["from"] as $tname => $bool) {
            $str = $tname;
            if(isset($this->parts["join"][$tname]))
                $str .= " ".$this->parts["join"][$tname];

            $a[] = $str;
        }

        $q .= implode(", ",$a);
        $this->applyInheritance();
        if( ! empty($this->parts["where"]))
            $q .= " WHERE ".implode(" ",$this->parts["where"]);



        if( ! empty($this->parts["orderby"]))
            $q .= " ORDER BY ".implode(", ",$this->parts["orderby"]);

        if( ! empty($this->parts["limit"]) && ! empty($this->offset))
            $q = $this->session->modifyLimitQuery($q,$this->parts["limit"],$this->offset);

        return $q;
    }
    /**
     * applyInheritance
     * applies column aggregation inheritance to DQL query
     *
     * @return boolean
     */
    final public function applyInheritance() {
        if($this->inheritanceApplied) 
            return false;

        // get the inheritance maps
        $array = array();

        foreach($this->tables as $table):
            $component = $table->getComponentName();
            $tableName = $this->tableAliases[$component];
            $array[$tableName][] = $table->getInheritanceMap();
        endforeach;

        // apply inheritance maps
        $str = "";
        $c = array();

        foreach($array as $tname => $maps) {
            $a = array();
            foreach($maps as $map) {
                $b = array();
                foreach($map as $field => $value) {
                    $b[] = $tname.".$field = $value";
                }
                if( ! empty($b)) $a[] = implode(" AND ",$b);
            }
            if( ! empty($a)) $c[] = implode(" || ",$a);
        }

        $str .= implode(" || ",$c);

        $this->addWhere($str);
        $this->inheritanceApplied = true;
        return true;
    }
    /**
     * @param string $where
     * @return boolean
     */
    final public function addWhere($where) {
        if(empty($where))
            return false;

        if($this->parts["where"]) {
            $this->parts["where"][] = "AND (".$where.")";
        } else {
            $this->parts["where"][] = "(".$where.")";
        }
        return true;
    }
    /**
     * @param string $having
     * @return boolean
     */
    final public function addHaving($having) {
        if(empty($having))
            return false;

        if($this->parts["having"]) {
            $this->parts["having"][] = "AND (".$having.")";
        } else {
            $this->parts["having"][] = "(".$having.")";
        }
        return true;
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
                throw new DQLException();
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
                $query = $this->getQuery();

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
                            if($row[$ids] === null)
                                $emptyID = true;
                        }


                        $name    = $this->tables[$key]->getComponentName();

                        if($emptyID) {

                            $pointer = $this->joins[$name];
                            $alias   = $this->tables[$pointer]->getAlias($name);
                            $fk      = $this->tables[$pointer]->getForeignKey($alias);
                            $last    = $prev[$pointer]->getLast();

                            switch($fk->getType()):
                                case Doctrine_Relation::ONE_COMPOSITE:
                                case Doctrine_Relation::ONE_AGGREGATE:
                                
                                break;
                                default:
                                    if($last instanceof Doctrine_Record) {
                                        if( ! $last->hasReference($alias)) {
                                            $prev[$name] = $this->getCollection($name);
                                            $last->initReference($prev[$name],$this->connectors[$name]);
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
                            } else {

                                $pointer = $this->joins[$name];
                                $alias   = $this->tables[$pointer]->getAlias($name);
                                $fk      = $this->tables[$pointer]->getForeignKey($alias);
                                $last    = $prev[$pointer]->getLast();

                                switch($fk->getType()):
                                    case Doctrine_Relation::ONE_COMPOSITE:
                                    case Doctrine_Relation::ONE_AGGREGATE:
                                        // one-to-one relation

                                        $last->internalSet($this->connectors[$name]->getLocal(), $record->getID());

                                        $last->initSingleReference($record);

                                        $prev[$name] = $record;
                                    break;
                                    default:
                                        // one-to-many relation or many-to-many relation

                                        if( ! $last->hasReference($alias)) {
                                            $prev[$name] = $this->getCollection($name);
                                            $last->initReference($prev[$name],$this->connectors[$name]);
                                        } else {
                                            // previous entry found from identityMap
                                            $prev[$name] = $last->get($alias);
                                        }

                                        $last->addReference($record);
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
    /**
     * getCollection
     *
     * @parma string $name              component name
     * @param integer $index
     */
    private function getCollection($name) {
        $table = $this->session->getTable($name);
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
        endswitch;

        $coll->populate($this);
        return $coll;
    }
    /**
     * query the database with DQL (Doctrine Query Language)
     *
     * @param string $query                 DQL query
     * @param array $params                 parameters
     */
    public function query($query,$params = array()) {
        $this->parseQuery($query);

        if($this->aggregate) {
            $keys  = array_keys($this->tables);
            $query = $this->getQuery();
            $stmt  = $this->tables[$keys[0]]->getSession()->select($query,$this->parts["limit"],$this->offset);
            $data  = $stmt->fetch(PDO::FETCH_ASSOC);
            if(count($data) == 1) {
                return current($data);
            } else {
                return $data;
            }
        } else {
            return $this->execute($params);
        }
    }
    /**
     * DQL PARSER
     *
     * @param string $query         DQL query
     * @return void
     */
    final public function parseQuery($query) {
        $this->clear();
        $e = self::bracketExplode($query," ","(",")");
            
        $parts = array();
        foreach($e as $k=>$part):
            switch(strtolower($part)):
                case "select":
                case "from":
                case "where":
                case "limit":
                case "offset":
                    $p = $part;
                    $parts[$part] = array();
                break;
                case "order":
                    $p = $part;
                    $i = $k+1;
                    if(isset($e[$i]) && strtolower($e[$i]) == "by") {
                        $parts[$part] = array();
                    }
                break;
                case "by":
                    continue;
                default:
                    $parts[$p][] = $part;
            endswitch;
        endforeach;

        foreach($parts as $k => $part) {
            $part = implode(" ",$part);
            switch(strtoupper($k)):
                case "SELECT":
                    $this->parseSelect($part);
                break;
                case "FROM":
                    $this->parseFrom($part);
                break;
                case "WHERE":
                    $this->addWhere($this->parseWhere($part));
                break;
                case "ORDER":
                    $this->parseOrderBy($part);
                break;  
                case "LIMIT":
                    $this->parts["limit"] = trim($part);
                break;
                case "OFFSET":
                    $this->offset = trim($part);
                break;
            endswitch;
        }
    }
    /**
     * DQL ORDER BY PARSER
     * parses the order by part of the query string
     *
     * @param string $str
     * @return void
     */
    private function parseOrderBy($str) {
        foreach(explode(",",trim($str)) as $r) {
            $r = trim($r);
            $e = explode(" ",$r);
            $a = explode(".",$e[0]);
    
            if(count($a) > 1) {
                $field     = array_pop($a);
                $reference = implode(".",$a);
                $name      = end($a);

                $this->load($reference, false);
                $tname     = $this->tables[$name]->getTableName();

                $r = $tname.".".$field;
                if(isset($e[1])) 
                    $r .= " ".$e[1];
            }
            $this->parts["orderby"][] = $r;
        }
    }
    /**
     * DQL SELECT PARSER
     * parses the select part of the query string
     *
     * @param string $str
     * @return void
     */
    private function parseSelect($str) {
        $this->aggregate = true;
        foreach(explode(",",trim($str)) as $reference) {

            $e = explode(" AS ",trim($reference));

            $f = explode("(",$e[0]);
            $a = explode(".",$f[1]);
            $field = substr(array_pop($a),0,-1);

            $reference = trim(implode(".",$a));

            $objTable = $this->load($reference);
            if(isset($e[1]))
                $s = " AS $e[1]";

            $this->parts["columns"][] = $f[0]."(".$objTable->getTableName().".$field)$s";

        }
    }
    /**
     * DQL GROUP BY PARSER
     * parses the group by part of the query string

     * @param string $str
     * @return void
     */
    private function parseGroupBy($str) {
        foreach(explode(",", $str) as $reference) {
            $reference = trim($reference);
            $e     = explode(".",$reference);
            $field = array_pop($e);
            $table = $this->load(implode(".",$e));
            $component = $table->getComponentName();
            $this->parts["groupby"][] = $this->tableAliases[$component].".".$field;
        }
    }
    /**
     * DQL FROM PARSER
     * parses the from part of the query string

     * @param string $str
     * @return void
     */
    private function parseFrom($str) {
        foreach(self::bracketExplode(trim($str),",", "(",")") as $reference) {
            $reference = trim($reference);
            $a         = explode(".",$reference);
            $field     = array_pop($a);
            $table = $this->load($reference);
        }
    }
    /**
     * returns Doctrine::FETCH_* constant
     *
     * @param string $mode
     * @return integer
     */
    private function parseFetchMode($mode) {
        switch(strtolower($mode)):
            case "i":
            case "immediate":
                $fetchmode = Doctrine::FETCH_IMMEDIATE;
            break;
            case "b":
            case "batch":
                $fetchmode = Doctrine::FETCH_BATCH;
            break;
            case "l":
            case "lazy":
                $fetchmode = Doctrine::FETCH_LAZY;
            break;
            case "o":
            case "offset":
                $fetchmode = Doctrine::FETCH_OFFSET;
            break;
            case "lo":
            case "lazyoffset":
                $fetchmode = Doctrine::FETCH_LAZYOFFSET;
            default:
                throw new DQLException("Unknown fetchmode '$mode'. The availible fetchmodes are 'i', 'b' and 'l'.");
        endswitch;
        return $fetchmode;
    }
    /**
     * DQL CONDITION PARSER
     * parses the where/having part of the query string
     *
     *
     * @param string $str
     * @return string
     */
    private function parseCondition($str, $type = 'Where') {

        $tmp = trim($str);
        $str = self::bracketTrim($tmp,"(",")");
        
        $brackets = false;
        $loadMethod = "load".$type;

        while($tmp != $str) {
            $brackets = true;
            $tmp = $str;
            $str = self::bracketTrim($str,"(",")");
        }

        $parts = self::bracketExplode($str," && ","(",")");
        if(count($parts) > 1) {
            $ret = array();
            foreach($parts as $part) {
                $ret[] = $this->parseCondition($part, $type);
            }
            $r = implode(" AND ",$ret);
        } else {
            $parts = self::bracketExplode($str," || ","(",")");
            if(count($parts) > 1) {
                $ret = array();
                foreach($parts as $part) {
                    $ret[] = $this->parseCondition($part, $type);
                }
                $r = implode(" OR ",$ret);
            } else {
                return $this->$loadMethod($parts[0]);
            }
        }
        if($brackets)
            return "(".$r.")";
        else
            return $r;
    }
    /**
     * DQL WHERE PARSER
     * parses the where part of the query string
     *
     *
     * @param string $str
     * @return string
     */
    private function parseWhere($str) {
        return $this->parseCondition($str,'Where');
    }
    /**
     * DQL HAVING PARSER
     * parses the having part of the query string
     *
     *
     * @param string $str
     * @return string
     */
    private function parseHaving($str) {
        return $this->parseCondition($str,'Having');
    }
    /**
     * trims brackets
     *
     * @param string $str
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     */
    public static function bracketTrim($str,$e1,$e2) {
        if(substr($str,0,1) == $e1 && substr($str,-1) == $e2)
            return substr($str,1,-1);
        else
            return $str;
    }
    /**
     * bracketExplode
     * usage:
     * $str = (age < 20 AND age > 18) AND email LIKE 'John@example.com'
     * now exploding $str with parameters $d = ' AND ', $e1 = '(' and $e2 = ')'
     * would return an array:
     * array("(age < 20 AND age > 18)", "email LIKE 'John@example.com'")
     *
     * @param string $str
     * @param string $d         the delimeter which explodes the string
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     *
     */
    public static function bracketExplode($str,$d,$e1,$e2) {
        $str = explode("$d",$str);
        $i = 0;
        $term = array();
        foreach($str as $key=>$val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);
                $s1 = substr_count($term[$i],"$e1");
                $s2 = substr_count($term[$i],"$e2");
                    if($s1 == $s2) $i++;
            } else {
                $term[$i] .= "$d".trim($val);
                $c1 = substr_count($term[$i],"$e1");
                $c2 = substr_count($term[$i],"$e2");
                    if($c1 == $c2) $i++;
            }
        }
        return $term;
    }
    /**
     * DQL Aggregate Function parser
     *
     * @param string $func
     * @return mixed
     */
    private function parseAggregateFunction($func) {
        $pos = strpos($func,"(");

        if($pos !== false) {

            $funcs  = array();

            $name   = substr($func, 0, $pos);
            $func   = substr($func, ($pos + 1), -1);
            $params = self::bracketExplode($func, ",", "(", ")");

            foreach($params as $k => $param) {
                $params[$k] = $this->parseAggregateFunction($param);
            }

            $funcs = $name."(".implode(", ", $params).")";

            return $funcs;

        } else {
            if( ! is_numeric($func)) {
                $a = explode(".",$func);
                $field     = array_pop($a);
                $reference = implode(".",$a);
                $table     = $this->load($reference, false);
                $component = $table->getComponentName();

                $func      = $this->tableAliases[$component].".".$field;

                return $func;
            } else {
                return $func;
            }
        }
    }
    /**
     * loadHaving
     *
     * @param string $having
     */
    private function loadHaving($having) {
        $e = self::bracketExplode($having," ","(",")");

        $r = array_shift($e);
        $t = explode("(",$r);

        $count = count($t);
        $r = $this->parseAggregateFunction($r);
        $operator  = array_shift($e);
        $value     = implode(" ",$e);
        $r .= " ".$operator." ".$value;

        return $r;
    }
    /**
     * loadWhere
     *
     * @param string $where
     */
    private function loadWhere($where) {
        $e = explode(" ",$where);
        $r = array_shift($e);
        $a = explode(".",$r);


        if(count($a) > 1) {
            $field     = array_pop($a);
            $operator  = array_shift($e);
            $value     = implode(" ",$e);
            $reference = implode(".",$a);
            $count     = count($a);

            $table = $this->load($reference, false);

            $component = $table->getComponentName();
            $where     = $this->tableAliases[$component].".".$field." ".$operator." ".$value;
        }
        return $where;
    }
    /**
     * @param string $path              the path of the loadable component
     * @param integer $fetchmode        optional fetchmode, if not set the components default fetchmode will be used
     * @throws DQLException
     */
    final public function load($path, $loadFields = true) {
        $e = preg_split("/[.:]/",$path);
        $index = 0;

        foreach($e as $key => $fullname) {
            try {
                $e2 = preg_split("/[-(]/",$fullname);
                $name = $e2[0];

                if($key == 0) {

                    $table = $this->session->getTable($name);

                    $tname = $table->getTableName();
                    $this->parts["from"][$tname] = true;

                    $this->tableAliases[$name] = $tname;
                } else {

                    $index += strlen($e[($key - 1)]) + 1;
                    // the mark here is either '.' or ':'
                    $mark  = substr($path,($index - 1),1);

                    
                    $parent = $table->getComponentName();

                    if(isset($this->tableAliases[$parent])) {
                        $tname = $this->tableAliases[$parent];
                    } else
                        $tname = $table->getTableName();


                    $fk     = $table->getForeignKey($name);
                    $name   = $fk->getTable()->getComponentName();  
                    $tname2 = $fk->getTable()->getTableName();

                    $this->connectors[$name] = $fk;

                    switch($mark):
                        case ":":
                            $join = 'INNER JOIN ';
                        break;
                        case ".":
                            $join = 'LEFT JOIN ';
                        break;
                        default:
                            throw new Doctrine_Exception("Unknown operator '$mark'");
                    endswitch;


                    if($fk instanceof Doctrine_ForeignKey ||
                       $fk instanceof Doctrine_LocalKey) {

                        $this->parts["join"][$tname][$tname2]  = $join.$tname2." ON ".$tname.".".$fk->getLocal()." = ".$tname2.".".$fk->getForeign();

                    } elseif($fk instanceof Doctrine_Association) {
                        $asf = $fk->getAssociationFactory();

                        $assocTableName = $asf->getTableName();

                        $this->parts["join"][$tname][$assocTableName]   = $join.$assocTableName." ON ".$tname.".id = ".$assocTableName.".".$fk->getLocal();
                        
                        if($tname == $tname2) {
                            $tname2 = $tname."2";
                            $alias  = $tname." AS ".$tname2;
                        } else 
                            $alias = $tname2;

                        $this->parts["join"][$tname][$tname2]           = $join.$alias." ON ".$tname2.".id = ".$assocTableName.".".$fk->getForeign();
                    }

                    $c = $table->getComponentName();
                    $this->joins[$name] = $c;


                    $table = $fk->getTable();

                    $this->tableAliases[$name] = $tname2;
                }

                if( ! isset($this->tables[$name])) {
                    $this->tables[$name] = $table;    

                    if($loadFields && ! $this->aggregate) {
                        $fields = array();

                        if(strpos($fullname, "-") === false) {
                            $fetchmode = $table->getAttribute(Doctrine::ATTR_FETCHMODE);
                            
                            if(isset($e2[1]))
                                $fields = explode(",",substr($e2[1],0,-1));

                        } else {
                            if(isset($e2[1])) {
                                $fetchmode = $this->parseFetchMode($e2[1]);
                            } else
                                $fetchmode = $table->getAttribute(Doctrine::ATTR_FETCHMODE);
                                
                            if(isset($e2[2]))
                                $fields = explode(",",substr($e2[2],0,-1));
                        }

                        $this->loadFields($table, $fetchmode, $fields);
                    }
                }

            } catch(Exception $e) {
                throw new DQLException($e->__toString());
            }
        }
        return $table;
    }
}

?>
