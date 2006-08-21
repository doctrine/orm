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
require_once("Hydrate.php");
/**
 * Doctrine_Query
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Query extends Doctrine_Hydrate {
    /**
     * @param array $subqueryAliases        the table aliases needed in some LIMIT subqueries
     */
    private $subqueryAliases = array();
    /**
     * @param boolean $needsSubquery
     */
    private $needsSubquery   = false;
	/**
 	 * count
     *
	 * @return integer
     */
	public function count(Doctrine_Table $table, $params = array()) {
		$this->remove('select');
		$join  = $this->join;
		$where = $this->where;
		$having = $this->having;
		
		$q = "SELECT COUNT(1) FROM ".$table->getComponentName()." ";
		foreach($join as $j) {
			$q .= implode(" ",$j);
		}
        $string = $this->applyInheritance();

        if( ! empty($where)) {
            $q .= " WHERE ".implode(" AND ",$where);
            if( ! empty($string))
                $q .= " AND (".$string.")";
        } else {
            if( ! empty($string))
                $q .= " WHERE (".$string.")";
        }
			
		if( ! empty($having)) 
			$q .= " HAVING ".implode(' AND ',$having);

		$a = $this->getSession()->execute($q, $params)->fetch(PDO::FETCH_NUM);
		return $a[0];		
	}
    /**
     * loadFields      
     * loads fields for a given table and
     * constructs a little bit of sql for every field
     *
     * fields of the tables become: [tablename].[fieldname] as [tablename]__[fieldname]
     *
     * @access private
     * @param object Doctrine_Table $table          a Doctrine_Table object
     * @param integer $fetchmode                    fetchmode the table is using eg. Doctrine::FETCH_LAZY
     * @param array $names                          fields to be loaded (only used in lazy property loading)
     * @return void
     */
    protected function loadFields(Doctrine_Table $table, $fetchmode, array $names, $cpath) {
        $name = $table->getComponentName();

        switch($fetchmode):
            case Doctrine::FETCH_OFFSET:
                $this->limit = $table->getAttribute(Doctrine::ATTR_COLL_LIMIT);
            case Doctrine::FETCH_IMMEDIATE:
                if( ! empty($names))
                    $names = array_unique(array_merge($table->getPrimaryKeys(), $names));
                else
                    $names = $table->getColumnNames();
            break;
            case Doctrine::FETCH_LAZY_OFFSET:
                $this->limit = $table->getAttribute(Doctrine::ATTR_COLL_LIMIT);
            case Doctrine::FETCH_LAZY:
            case Doctrine::FETCH_BATCH:
                $names = array_unique(array_merge($table->getPrimaryKeys(), $names));
            break;
            default:
                throw new Doctrine_Exception("Unknown fetchmode.");
        endswitch;
        
        $component          = $table->getComponentName();
        $tablename          = $this->tableAliases[$cpath];

        $this->fetchModes[$tablename] = $fetchmode;

        $count = count($this->tables);

        foreach($names as $name) {
            if($count == 0) {
                $this->parts["select"][] = $tablename.".".$name;
            } else {
                $this->parts["select"][] = $tablename.".".$name." AS ".$tablename."__".$name;
            }
        }
    }
    /**
     * addFrom
     * 
     * @param strint $from
     */
    public function addFrom($from) {
        $class = "Doctrine_Query_From";
        $parser = new $class($this);
        $parser->parse($from);
    }
    /**
     * addWhere
     *
     * @param string $where
     */
    public function addWhere($where) {
        $class  = "Doctrine_Query_Where";
        $parser = new $class($this);
        $this->parts['where'][] = $parser->parse($where);
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
                case "from":
                    $this->parts['from']    = array();
                    $this->parts['select']  = array();
                    $this->parts['join']    = array();
                    $this->joins            = array();
                    $this->tables           = array();
                    $this->fetchModes       = array();
                    $this->tableIndexes     = array();
                    $this->tableAliases     = array();
                    
                    $class = "Doctrine_Query_".ucwords($name);
                    $parser = new $class($this);
                    
                    $parser->parse($args[0]);
                break;
                case "where":
                case "having": 
                case "orderby":
                case "groupby":
                    $class = "Doctrine_Query_".ucwords($name);
                    $parser = new $class($this);

                    $this->parts[$name] = array($parser->parse($args[0]));
                break;
                case "limit":
                case "offset":
                    if($args[0] == null)
                        $args[0] = false;

                    $this->parts[$name] = $args[0];
                break;
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
                    $this->parts['select']  = array();
                    $this->parts['join']    = array();
                    $this->joins            = array();
                    $this->tables           = array();
                    $this->fetchModes       = array();
                    $this->tableIndexes     = array();
                    $this->tableAliases     = array();
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
    public function getQuery() {
        if(empty($this->parts["select"]) || empty($this->parts["from"]))
            return false;
        
        $needsSubQuery = false;
        $subquery = '';

        if( ! empty($this->parts['limit']) && $this->needsSubquery)
            $needsSubQuery = true;

        // build the basic query
        $q = "SELECT ".implode(", ",$this->parts["select"]).
             " FROM ";

        foreach($this->parts["from"] as $tname => $bool) {
            $a[] = $tname;
        }
        $q .= implode(", ",$a);
        $k  = array_keys($this->tables);
        $table = $this->tables[$k[0]];

        if($needsSubQuery)
            $subquery = 'SELECT DISTINCT '.$table->getTableName().".".$table->getIdentifier().
                        ' FROM '.$table->getTableName();

        if( ! empty($this->parts['join'])) {
            foreach($this->parts['join'] as $part) {
                $q .= " ".implode(' ', $part);
            }

            if($needsSubQuery) {
                foreach($this->parts['join'] as $parts) {
                    foreach($parts as $part) {
                        if(substr($part,0,9) === 'LEFT JOIN') {
                            $e = explode(' ', $part);

                            if( ! in_array($e[2],$this->subqueryAliases))
                                continue;
                        }

                        $subquery .= " ".$part;
                    }
                }
            }
        }

        $string = $this->applyInheritance();

        if( ! empty($string))
            $this->parts['where'][] = '('.$string.')';

        if($needsSubQuery) {
            // all conditions must be preserved in subquery
            $subquery .= ( ! empty($this->parts['where']))?" WHERE ".implode(" AND ",$this->parts["where"]):'';
            $subquery .= ( ! empty($this->parts['groupby']))?" GROUP BY ".implode(", ",$this->parts["groupby"]):'';
            $subquery .= ( ! empty($this->parts['having']))?" HAVING ".implode(" ",$this->parts["having"]):'';
        }

        $modifyLimit = false;
        if( ! empty($this->parts["limit"]) || ! empty($this->parts["offset"])) {
            if($needsSubQuery) {
                $subquery = $this->session->modifyLimitQuery($subquery,$this->parts["limit"],$this->parts["offset"]);
    
                $field    = $table->getTableName().'.'.$table->getIdentifier();
                array_unshift($this->parts['where'], $field.' IN ('.$subquery.')');
            } else
                $modifyLimit = true;
        }

        $q .= ( ! empty($this->parts['where']))?" WHERE ".implode(" AND ",$this->parts["where"]):'';
        $q .= ( ! empty($this->parts['groupby']))?" GROUP BY ".implode(", ",$this->parts["groupby"]):'';
        $q .= ( ! empty($this->parts['having']))?" HAVING ".implode(" ",$this->parts["having"]):'';
        $q .= ( ! empty($this->parts['orderby']))?" ORDER BY ".implode(" ",$this->parts["orderby"]):'';
        if($modifyLimit)
            $q = $this->session->modifyLimitQuery($q,$this->parts["limit"],$this->parts["offset"]);

        // return to the previous state
        if( ! empty($string))
            array_pop($this->parts['where']);
        if($needsSubQuery)
            array_shift($this->parts['where']);

        return $q;
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
            $stmt  = $this->tables[$keys[0]]->getSession()->select($query,$this->parts["limit"],$this->parts["offset"]);
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
     * parses a DQL query
     * first splits the query in parts and then uses individual
     * parsers for each part
     *
     * @param string $query         DQL query
     * @return void
     */
    public function parseQuery($query) {
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
                case "having":
                    $p = $part;
                    $parts[$part] = array();
                break;
                case "order":
                case "group":
                    $i = ($k + 1);
                    if(isset($e[$i]) && strtolower($e[$i]) === "by") {
                        $p = $part;
                        $parts[$part] = array();
                    } else 
                        $parts[$p][] = $part;
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

                    $class  = "Doctrine_Query_".ucwords(strtolower($k));
                    $parser = new $class($this);
                    $parser->parse($part);
                break;
                case "GROUP":
                case "ORDER":
                    $k .= "by";
                case "WHERE":
                case "HAVING":
                    $class  = "Doctrine_Query_".ucwords(strtolower($k));
                    $parser = new $class($this);

                    $name = strtolower($k);
                    $this->parts[$name][] = $parser->parse($part);
                break;
                case "LIMIT":
                    $this->parts["limit"] = trim($part);
                break;
                case "OFFSET":
                    $this->parts["offset"] = trim($part);
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
    final public function parseOrderBy($str) {
        $parser = new Doctrine_Query_Part_Orderby($this);
        return $parser->parse($str);
    }
    /**
     * returns Doctrine::FETCH_* constant
     *
     * @param string $mode
     * @return integer
     */
    final public function parseFetchMode($mode) {
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
                throw new Doctrine_Query_Exception("Unknown fetchmode '$mode'. The availible fetchmodes are 'i', 'b' and 'l'.");
        endswitch;
        return $fetchmode;
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
    public static function bracketExplode($str,$d,$e1 = '(',$e2 = ')') {
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
     * generateAlias
     *
     * @param string $tableName
     * @return string
     */
    final public function generateAlias($tableName) {
        if(isset($this->tableIndexes[$tableName])) {
            return $tableName.++$this->tableIndexes[$tableName];
        } else {
            $this->tableIndexes[$tableName] = 1;
            return $tableName;
        }
    }

    /**
     * loads a component
     *
     * @param string $path              the path of the loadable component
     * @param integer $fetchmode        optional fetchmode, if not set the components default fetchmode will be used
     * @throws Doctrine_Query_Exception
     * @return Doctrine_Table
     */
    final public function load($path, $loadFields = true) {
        $e = preg_split("/[.:]/",$path);
        $index = 0;
        $currPath = '';

        foreach($e as $key => $fullname) {
            try {
                $copy  = $e;

                $e2    = preg_split("/[-(]/",$fullname);
                $name  = $e2[0];

                $currPath .= ".".$name;

                if($key == 0) {
                    $currPath = substr($currPath,1);

                    $table = $this->session->getTable($name);

                    $tname = $table->getTableName();

                    if( ! isset($this->tableAliases[$currPath]))
                        $this->tableIndexes[$tname] = 1;
                    
                    $this->parts["from"][$tname] = true;

                    $this->tableAliases[$currPath] = $tname;
                    
                    $tableName = $tname;
                } else {

                    $index += strlen($e[($key - 1)]) + 1;
                    // the mark here is either '.' or ':'
                    $mark  = substr($path,($index - 1),1);

                    if(isset($this->tableAliases[$prevPath])) {
                        $tname = $this->tableAliases[$prevPath];
                    } else
                        $tname = $table->getTableName();


                    $fk       = $table->getForeignKey($name);
                    $name     = $fk->getTable()->getComponentName();
                    $original = $fk->getTable()->getTableName();



                    if(isset($this->tableAliases[$currPath])) {
                        $tname2 = $this->tableAliases[$currPath];
                    } else
                        $tname2 = $this->generateAlias($original);

                    if($original !== $tname2) 
                        $aliasString = $original." AS ".$tname2;
                    else
                        $aliasString = $original;

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

                    if($fk->getType() == Doctrine_Relation::MANY_AGGREGATE ||
                       $fk->getType() == Doctrine_Relation::MANY_COMPOSITE) {
                        if( ! $loadFields)
                            $this->subqueryAliases[] = $tname2;
                    
                        $this->needsSubquery = true;
                    }

                    if($fk instanceof Doctrine_ForeignKey ||
                       $fk instanceof Doctrine_LocalKey) {

                        $this->parts["join"][$tname][$tname2]         = $join.$aliasString." ON ".$tname.".".$fk->getLocal()." = ".$tname2.".".$fk->getForeign();

                    } elseif($fk instanceof Doctrine_Association) {
                        $asf = $fk->getAssociationFactory();

                        $assocTableName = $asf->getTableName();

                        $this->parts["join"][$tname][$assocTableName] = $join.$assocTableName." ON ".$tname.".id = ".$assocTableName.".".$fk->getLocal();
                        $this->parts["join"][$tname][$tname2]         = $join.$aliasString." ON ".$tname2.".id = ".$assocTableName.".".$fk->getForeign();
                    }

                    $this->joins[$tname2] = $prevTable;


                    $table = $fk->getTable();
                    $this->tableAliases[$currPath] = $tname2;

                    $tableName = $tname2;
                }

                if( ! isset($this->tables[$tableName])) {
                    $this->tables[$tableName] = $table;

                    if($loadFields && ! $this->aggregate) {
                        $this->parseFields($fullname, $tableName, $e2, $currPath);
                    }
                }


                $prevPath  = $currPath;
                $prevTable = $tableName;
            } catch(Exception $e) {
                throw new Doctrine_Query_Exception($e->__toString());
            }
        }
        return $table;
    }
    /**
     * parseFields
     *
     * @param string $fullName
     * @param string $tableName
     * @param array $exploded
     * @param string $currPath
     * @return void
     */
    final public function parseFields($fullName, $tableName, $exploded, $currPath) {
        $table = $this->tables[$tableName];

        $fields = array();

        if(strpos($fullName, "-") === false) {
            $fetchmode = $table->getAttribute(Doctrine::ATTR_FETCHMODE);

            if(isset($exploded[1]))
                $fields = explode(",",substr($exploded[1],0,-1));

            } else {
                if(isset($exploded[1])) {
                    $fetchmode = $this->parseFetchMode($exploded[1]);
                } else
                    $fetchmode = $table->getAttribute(Doctrine::ATTR_FETCHMODE);

                if(isset($exploded[2]))
                    $fields = explode(",",substr($exploded[2],0,-1));
            }

        $this->loadFields($table, $fetchmode, $fields, $currPath);
    }
}
?>
