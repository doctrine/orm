<?php

/**
 * Doctrine_DQL_Parser
 * this is the base class for generating complex data graphs 
 * (multiple collections, multiple factories, multiple data access objects)
 * with only one query
 *
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_DQL_Parser {
    /**
     * @var array $fetchmodes       an array containing all fetchmodes
     */
    private $fetchModes  = array();
    /** 
     * @var array $fields           an array containing all the selected fields
     */
    private $fields      = array();
    /**
     * @var array $tablenames       an array containing all the tables used in the query
     */
    private $tablenames   = array();
    /**
     * @var array $from             query FROM parts
     */
    private $from        = array();
    /**
     * @var array $join             query JOIN parts
     */
    private $join        = array();
    /**
     * @var string $where
     */
    private $where       = array();
    /**
     * @var array $orderby          query ORDER BY parts
     */
    private $orderby     = array();
    /**
     * @var integer $limit          query limit
     */
    private $limit;
    /**
     * @var integer $offset         query offset
     */
    private $offset;
    
    private $joined      = array();
    /**
     * @var array $data             fetched data
     */
    private $data        = array();
    /**
     * @var Doctrine_Session $session     Doctrine_Session object
     */
    private $session;

    private $inheritanceApplied = false;

    private $aggregate  = false;

    private $paths       = array();
    
    private $connectors  = array();
    /**
     * @param Doctrine_Session $session
     */
    public function __construct(Doctrine_Session $session) {
        $this->session = $session;
    }
    /**
     * clear
     * resets all the variables
     */
    private function clear() {
        $this->fetchModes   = array();
        $this->fields       = array();
        $this->tnames       = array();

        $this->from         = array();
        $this->join         = array();
        $this->where        = array();
        $this->orderby      = array();
        $this->inheritanceApplied = false;
        $this->aggregate    = false;
        $this->data         = array();
        $this->connectors   = array();
    }
    /**
     * loadFields       -- this method loads fields for a given factory and
     *                     constructs a little bit of sql for every field
     *                      
     *                     fields of the factories become: [tablename].[fieldname] as [tablename]__[fieldname]
     *
     * @access private
     * @param object Doctrine_Table $table    a Doctrine_Table object
     * @param integer $fetchmode                 fetchmode the table is using eg. Doctrine::FETCH_LAZY
     * @return void
     */
    private function loadFields(Doctrine_Table $table,$fetchmode) {
        switch($fetchmode):
            case Doctrine::FETCH_IMMEDIATE:
                $names  = $table->getColumnNames();
            break;
            case Doctrine::FETCH_LAZY:
            case Doctrine::FETCH_BATCH:
                $names = $table->getPrimaryKeys();
            break;
            default:
                throw new InvalidFetchModeException();
        endswitch;
        $cname          = $table->getComponentName();
        $this->fetchModes[$cname] = $fetchmode;
        $tablename      = $table->getTableName();

        $count = count($this->tnames);
        foreach($names as $name) {
            if($count == 0) {
                $this->fields[] = $tablename.".".$name;
            } else {
                $this->fields[] = $tablename.".".$name." AS ".$cname."__".$name;
            }
        }
    }
    /**
     * @return string               the built sql query
     */
    final public function getQuery() {
        if(empty($this->fields) || empty($this->from))
            return false;

        // build the basic query
        $q = "SELECT ".implode(", ",$this->fields).
             " FROM ";
        foreach($this->from as $tname => $bool) {
            $str = $tname;
            if(isset($this->join[$tname]))
                $str .= " ".$this->join[$tname];

            $a[] = $str;
        }
        $q .= implode(", ",$a);
        $this->applyInheritance();
        if( ! empty($this->where))
            $q .= " WHERE ".implode(" && ",$this->where);

        if( ! empty($this->orderby))
            $q .= " ORDER BY ".implode(", ",$this->orderby);

        return $q;
    }
    /**
     * sql delete for mysql
     */
    final public function buildDelete() {
        if(empty($this->fields) || empty($this->from))
            return false;    
        
        $a = array_merge(array_keys($this->from),$this->joined);
        $q = "DELETE ".implode(", ",$a)." FROM ";
        $a = array();

        foreach($this->from as $tname => $bool) {
            $str = $tname;
            if(isset($this->join[$tname]))
                $str .= " ".$this->join[$tname];

            $a[] = $str;
        }

        $q .= implode(", ",$a);
        $this->applyInheritance();
        if( ! empty($this->where))
            $q .= " WHERE ".implode(" && ",$this->where);

        if( ! empty($this->orderby))
            $q .= " ORDER BY ".implode(", ",$this->orderby);

        if( ! empty($this->limit) && ! empty($this->offset))
            $q = $this->session->modifyLimitQuery($q,$this->limit,$this->offset);

        return $q;
    }
    /**
     * applyInheritance
     * applies column aggregation inheritance to DQL query
     * @return boolean
     */
    final public function applyInheritance() {
        if($this->inheritanceApplied) 
            return false;

        // get the inheritance maps
        $array = array();

        foreach($this->tnames as $objTable):
            $tname = $objTable->getTableName();
            $array[$tname][] = $objTable->getInheritanceMap();
        endforeach;

        // apply inheritance maps
        $str = "";
        $c = array();

        foreach($array as $tname => $maps) {
            $a = array();
            foreach($maps as $map) {
                $b = array();
                foreach($map as $field=>$value) {
                    $b[] = $tname.".$field = $value";
                }
                if( ! empty($b)) $a[] = implode(" && ",$b);
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

        $this->where[] = "(".$where.")";
        return true;
    }
    /**
     * @param string $from              from part of the query
     */
    final public function addFrom($from) {
        $this->from[] = $from;
    }
    /**
     * getData
     * @param $key                      the factory name
     * @return array                    the data row for the specified factory
     */
    final public function getData($key) {
        if(isset($this->data[$key]))
            return $this->data[$key];
        
        return array();
    }
    /**
     * execute
     * executes the datagraph and populates Doctrine_Collections
     * @param string $params
     * @return Doctrine_Collection            the root collection
     */
    private function execute($params = array()) {

        switch(count($this->tnames)):
            case 0:
                throw new DQLException();
            break;
            case 1:
                $query = $this->getQuery();

                $keys  = array_keys($this->tnames);
    
                $name  = $this->tnames[$keys[0]]->getComponentName();
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

                $keys  = array_keys($this->tnames);
                $root  = $keys[0];
                $stmt  = $this->session->execute($query,$params);
                
                $previd = array();

                $coll = $this->getCollection($root);

                $array = $this->parseData($stmt);

                foreach($array as $data):

                    /**
                     * remove duplicated data rows and map data into objects
                     */
                    foreach($data as $key => $row):
                        if(empty($row) || empty($row['id']))
                            continue;

                        $key  = ucwords($key);
                        $name = $this->tnames[$key]->getComponentName();

                        if( ! isset($previd[$name]))
                            $previd[$name] = array();


                        if($previd[$name] !== $row) {
                            $this->tnames[$name]->setData($row);
                            $record = $this->tnames[$name]->getRecord();

                            if($name == $root) {
                                $this->tnames[$name]->setData($row);
                                $record = $this->tnames[$name]->getRecord();
                                $coll->add($record);
                            } else {
                                $last = $coll->getLast();

                                if( ! $last->hasReference($name)) {
                                    $last->initReference($this->getCollection($name),$this->connectors[$name]);
                                }
                                $last->addReference($record);
                            }
                        }

                        $previd[$name] = $row;
                    endforeach;
                endforeach;

                return $coll;
        endswitch;
    }
    /**
     * parseData
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
     * @return Doctrine_Table
     */
    public function getTable($name) {
        return $this->tnames[$name];
    }
    /**
     * getCollection
     * @param integer $index
     */
    private function getCollection($name) {
        switch($this->fetchModes[$name]):
            case 0:
                $coll = new Doctrine_Collection_Immediate($this,$name);
            break;
            case 1:
                $coll = new Doctrine_Collection_Batch($this,$name);
            break;
            case 2:
                $coll = new Doctrine_Collection_Lazy($this,$name);
            break;
            default:
                throw new Exception("Unknown fetchmode");
        endswitch;

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
            $keys  = array_keys($this->tnames);
            $query = $this->getQuery();
            $stmt  = $this->tnames[$keys[0]]->getSession()->select($query);
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
            switch($k):
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
                    $this->limit = trim($part);
                break;
                case "OFFSET":
                    $this->offset = trim($part);
                break;
            endswitch;
        }
    }
    /**
     * DQL SELECT PARSER
     * parses the select part of the query string
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

            $this->fields[]= $f[0]."(".$objTable->getTableName().".$field)$s";

        }
    }
    /**
     * DQL FROM PARSER
     * parses the from part of the query string

     * @param string $str
     * @return void
     */
    private function parseFrom($str) {
        foreach(explode(",",trim($str)) as $reference) {
            $reference = trim($reference);
            $e = explode("-",$reference);
            $reference = $e[0];
            $table = $this->load($reference);

            if(isset($e[1])) {
                switch(strtolower($e[1])):
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
                    default:
                        throw new DQLException("Unknown fetchmode '$e[1]'. The availible fetchmodes are 'i', 'b' and 'l'.");
                endswitch;
            } else
                $fetchmode = $table->getAttribute(Doctrine::ATTR_FETCHMODE);

            if( ! $this->aggregate) {
                $this->loadFields($table,$fetchmode);
            }
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
                $this->load($reference);
                $tname     = $this->tnames[$name]->getTableName();

                $r = $tname.".".$field;
                if(isset($e[1])) $r .= " ".$e[1];
                $this->orderby[] = $r;
            } else {
                $this->orderby[] = $r;
            }
        }
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
        $tmp = trim($str);
        $str = self::bracketTrim($tmp,"(",")");
        
        $brackets = false;
        while($tmp != $str) {
            $brackets = true;
            $tmp = $str;
            $str = self::bracketTrim($str,"(",")");
        }

        $parts = self::bracketExplode($str," && ","(",")");
        if(count($parts) > 1) {
            $ret = array();
            foreach($parts as $part) {
                $ret[] = $this->parseWhere($part);
            }
            $r = implode(" && ",$ret);
        } else {
            $parts = self::bracketExplode($str," || ","(",")");
            if(count($parts) > 1) {
                $ret = array();
                foreach($parts as $part) {
                    $ret[] = $this->parseWhere($part);
                }
                $r = implode(" || ",$ret);
            } else {
                return $this->loadWhere($parts[0]);
            }
        }
        if($brackets) 
            return "(".$r.")";
        else 
            return $r;
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
     * $str = (age < 20 && age > 18) && email LIKE 'John@example.com'
     * now exploding $str with parameters $d = ' && ', $e1 = '(' and $e2 = ')'
     * would return an array:
     * array("(age < 20 && age > 18)", "email LIKE 'John@example.com'")
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
     * loadWhere
     *
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
            $objTable   = $this->session->getTable(end($a));
            $where     = $objTable->getTableName().".".$field." ".$operator." ".$value;
            if(count($a) > 1 && isset($a[1])) {
                $root = $a[0];
                $fk = $this->tnames[$root]->getForeignKey($a[1]);
                if($fk instanceof Doctrine_Association) {
                $asf = $fk->getAssociationFactory();
                    switch($fk->getType()):
                        case Doctrine_Table::ONE_AGGREGATE:
                        case Doctrine_Table::ONE_COMPOSITE:

                        break;
                        case Doctrine_Table::MANY_AGGREGATE:
                        case Doctrine_Table::MANY_COMPOSITE:
                            $b = array_shift($a);
                            $b = array_shift($a);
                            $graph = new Doctrine_DQL_Parser($this->session);
                            $graph->parseQuery("FROM $b WHERE $where");
                            $where = $this->tnames[$root]->getTableName().".id IN (SELECT ".$fk->getLocal()." FROM ".$asf->getTableName()." WHERE ".$fk->getForeign()." IN (".$graph->getQuery()."))";
                        break;
                    endswitch;
                } else
                $this->load($reference);

            } else
                $this->load($reference);
        }
        return $where;
    }
    /**
     * @param string $path              the path of the loadable component
     * @param integer $fetchmode        optional fetchmode, if not set the components default fetchmode will be used
     * @throws DQLException
     */
    final public function load($path, $fetchmode = Doctrine::FETCH_LAZY) {
        $e = explode(".",$path);
        foreach($e as $key => $name) {
            $low  = strtolower($name);
            $name = ucwords($low);

            try {
                if($key == 0) {

                    $objTable = $this->session->getTable($name);
                    if(count($e) == 1) {
                        $tname = $objTable->getTableName();
                        $this->from[$tname] = true;
                    }
                } else {
                    $fk     = $objTable->getForeignKey($name);
                    $tname  = $objTable->getTableName();
                    $next   = $fk->getTable();
                    $tname2 = $next->getTableName();

                    $this->connectors[$name] = $fk;

                    if($fk instanceof Doctrine_ForeignKey ||
                       $fk instanceof Doctrine_LocalKey) {
                        switch($fk->getType()):
                            case Doctrine_Table::ONE_AGGREGATE:
                            case Doctrine_Table::ONE_COMPOSITE:

                                $this->where[] = "(".$tname.".".$fk->getLocal()." = ".$tname2.".".$fk->getForeign().")";
                                $this->from[$tname]  = true;
                                $this->from[$tname2] = true;
                            break;
                            case Doctrine_Table::MANY_AGGREGATE:
                            case Doctrine_Table::MANY_COMPOSITE:
                                $this->join[$tname]  = "LEFT JOIN ".$tname2." ON ".$tname.".".$fk->getLocal()." = ".$tname2.".".$fk->getForeign();
                                $this->joined[]      = $tname2;
                                $this->from[$tname]  = true;
                            break;
                        endswitch;
                    } elseif($fk instanceof Doctrine_Association) {
                        $asf = $fk->getAssociationFactory();

                        switch($fk->getType()):
                            case Doctrine_Table::ONE_AGGREGATE:
                            case Doctrine_Table::ONE_COMPOSITE:

                            break;
                            case Doctrine_Table::MANY_AGGREGATE:
                            case Doctrine_Table::MANY_COMPOSITE:

                                //$this->addWhere("SELECT ".$fk->getLocal()." FROM ".$asf->getTableName()." WHERE ".$fk->getForeign()." IN (SELECT ".$fk->getTable()->getComponentName().")");
                                $this->from[$tname]  = true;
                            break;
                        endswitch;
                    }

                    $objTable = $next;
                }
                if( ! isset($this->tnames[$name])) {
                    $this->tnames[$name] = $objTable;
                }

            } catch(Doctrine_Exception $e) {
                throw new DQLException();
            } catch(InvalidKeyException $e) {
                throw new DQLException();
            }
        }
        return $objTable;
    }
}

?>
