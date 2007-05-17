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
Doctrine::autoload('Doctrine_Hydrate');
/**
 * Doctrine_Query
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query extends Doctrine_Hydrate implements Countable
{
    /**
     * @param array $subqueryAliases        the table aliases needed in some LIMIT subqueries
     */
    protected $subqueryAliases  = array();
    /**
     * @param boolean $needsSubquery
     */
    protected $needsSubquery    = false;
    /**
     * @param boolean $limitSubqueryUsed
     */
    protected $limitSubqueryUsed = false;

    
    protected $_status         = array('needsSubquery' => true);
    /**
     * @param boolean $isSubquery           whether or not this query object is a subquery of another 
     *                                      query object
     */
    protected $isSubquery;

    protected $isDistinct        = false;

    protected $neededTables      = array();
    /**
     * @var array $pendingFields
     */
    protected $pendingFields     = array();
    /**
     * @var array $pendingSubqueries        SELECT part subqueries, these are called pending subqueries since
     *                                      they cannot be parsed directly (some queries might be correlated)
     */
    protected $pendingSubqueries = array();
    /**
     * @var boolean $subqueriesProcessed    Whether or not pending subqueries have already been processed.
     *                                      Consequent calls to getQuery would result badly constructed queries
     *                                      without this variable
     *
     *                                      Since subqueries can be correlated, they can only be processed when 
     *                                      the main query is fully constructed
     */
    protected $subqueriesProcessed = false;
    /** 
     * @var array $_parsers                 an array of parser objects
     */
    protected $_parsers = array();
    /**
     * @var array $_enumParams              an array containing the keys of the parameters that should be enumerated
     */
    protected $_enumParams = array();

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
     * addEnumParam
     * sets input parameter as an enumerated parameter
     *
     * @param string $key   the key of the input parameter
     * @return Doctrine_Query
     */
    public function addEnumParam($key, $table = null, $column = null)
    {
    	$array = (isset($table) || isset($column)) ? array($table, $column) : array();

    	if ($key === '?') {
    	    $this->_enumParams[] = $array;
        } else {
            $this->_enumParams[$key] = $array;
        }
    }
    /**
     * getEnumParams
     * get all enumerated parameters
     *
     * @return array    all enumerated parameters
     */
    public function getEnumParams()
    {
        return $this->_enumParams;
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
     * getParser
     * parser lazy-loader
     *
     * @throws Doctrine_Query_Exception     if unknown parser name given
     * @return Doctrine_Query_Part
     */
    public function getParser($name)
    {
        if ( ! isset($this->_parsers[$name])) {
            $class = 'Doctrine_Query_' . ucwords(strtolower($name));

            Doctrine::autoload($class);
            
            if ( ! class_exists($class)) {
                throw new Doctrine_Query_Exception('Unknown parser ' . $name);
            }

            $this->_parsers[$name] = new $class($this);
        }
        
        return $this->_parsers[$name];
    }
    /**
     * processPendingFields
     * the fields in SELECT clause cannot be parsed until the components
     * in FROM clause are parsed, hence this method is called everytime a 
     * specific component is being parsed.
     *
     * @throws Doctrine_Query_Exception     if unknown component alias has been given
     * @param string $componentAlias        the alias of the component
     * @return void
     */
    public function processPendingFields($componentAlias)
    {
        $tableAlias = $this->getTableAlias($componentAlias);
        $table      = $this->_aliasMap[$componentAlias]['table'];

        if (isset($this->pendingFields[$componentAlias])) {
            $fields = $this->pendingFields[$componentAlias];

            // check for wildcards
            if (in_array('*', $fields)) {
                $fields = $table->getColumnNames();
            } else {
                // only auto-add the primary key fields if this query object is not 
                // a subquery of another query object
                if ( ! $this->isSubquery) {
                    $fields = array_unique(array_merge($table->getPrimaryKeys(), $fields));
                }
            }
        }
        foreach ($fields as $name) {
            $name = $table->getColumnName($name);

            $this->parts['select'][] = $tableAlias . '.' .$name . ' AS ' . $tableAlias . '__' . $name;
        }
        
        $this->neededTables[] = $tableAlias;

    }
    /**
     * parseSelect
     * parses the query select part and
     * adds selected fields to pendingFields array
     *
     * @param string $dql
     */
    public function parseSelect($dql)
    {
        $refs = Doctrine_Tokenizer::bracketExplode($dql, ',');

        foreach ($refs as $reference) {
            if (strpos($reference, '(') !== false) {
                if (substr($reference, 0, 1) === '(') {
                    // subselect found in SELECT part
                    $this->parseSubselect($reference);
                } else {
                    $this->parseAggregateFunction2($reference);
                }
            } else {

                $e = explode('.', $reference);
                if (count($e) > 2) {
                    $this->pendingFields[] = $reference;
                } else {
                    $this->pendingFields[$e[0]][] = $e[1];
                }
            }
        }
    }
    /** 
     * parseSubselect
     *
     * parses the subquery found in DQL SELECT part and adds the
     * parsed form into $pendingSubqueries stack
     *
     * @param string $reference
     * @return void
     */
    public function parseSubselect($reference) 
    {
        $e     = Doctrine_Tokenizer::bracketExplode($reference, ' ');
        $alias = $e[1];

        if (count($e) > 2) {
            if (strtoupper($e[1]) !== 'AS') {
                throw new Doctrine_Query_Exception('Syntax error near: ' . $reference);
            }
            $alias = $e[2];
        }
        
        $subquery = substr($e[0], 1, -1);
        
        $this->pendingSubqueries[] = array($subquery, $alias);
    }
    public function parseAggregateFunction2($func)
    {
        $e    = Doctrine_Tokenizer::bracketExplode($func, ' ');
        $func = $e[0];

        $pos  = strpos($func, '(');
        $name = substr($func, 0, $pos);

        try {
            $argStr = substr($func, ($pos + 1), -1);
            $args   = explode(',', $argStr);
    
            $func   = call_user_func_array(array($this->conn->expression, $name), $args);
    
            if(substr($func, 0, 1) !== '(') {
                $pos  = strpos($func, '(');
                $name = substr($func, 0, $pos);
            } else {
                $name = $func;
            }
    
            $e2     = explode(' ', $args[0]);
    
            $distinct = '';
            if (count($e2) > 1) {
                if (strtoupper($e2[0]) == 'DISTINCT') {
                    $distinct  = 'DISTINCT ';
                }
    
                $args[0] = $e2[1];
            }
    
    
    
            $parts = explode('.', $args[0]);
            $owner = $parts[0];
            $alias = (isset($e[1])) ? $e[1] : $name;
    
            $e3    = explode('.', $alias);
    
            if (count($e3) > 1) {
                $alias = $e3[1];
                $owner = $e3[0];
            }
    
            // a function without parameters eg. RANDOM()
            if ($owner === '') {
                $owner = 0;
            }
    
            $this->pendingAggregates[$owner][] = array($name, $args, $distinct, $alias);
        } catch(Doctrine_Expression_Exception $e) {
            throw new Doctrine_Query_Exception('Unknown function ' . $func . '.');
        }
    }
    public function processPendingSubqueries() 
    {
    	if ($this->subqueriesProcessed === true) {
            return false;
    	}

    	foreach ($this->pendingSubqueries as $value) {
            list($dql, $alias) = $value;

            $sql = $this->createSubquery()->parseQuery($dql, false)->getQuery();



            reset($this->_aliasMap);
            $componentAlias = key($this->_aliasMap);
            $tableAlias = $this->getTableAlias($componentAlias);

            $sqlAlias = $tableAlias . '__' . count($this->aggregateMap);
    
            $this->parts['select'][] = '(' . $sql . ') AS ' . $sqlAlias;
    
            $this->aggregateMap[$alias] = $sqlAlias;
            $this->subqueryAggregates[$componentAlias][] = $alias;
        }
        $this->subqueriesProcessed = true;
        
        return true;
    }
    public function processPendingAggregates($componentAlias)
    {
        $tableAlias = $this->getTableAlias($componentAlias);     

        $map   = reset($this->_aliasMap);
        $root  = $map['table'];
        $table = $this->_aliasMap[$componentAlias]['table'];

        $aggregates = array();

        if(isset($this->pendingAggregates[$componentAlias])) {
            $aggregates = $this->pendingAggregates[$componentAlias];
        }
        
        if ($root === $table) {
            if (isset($this->pendingAggregates[0])) {
                $aggregates += $this->pendingAggregates[0];
            }
        }

        foreach($aggregates as $parts) {
            list($name, $args, $distinct, $alias) = $parts;

            $arglist = array();
            foreach($args as $arg) {
                $e = explode('.', $arg);


                if (is_numeric($arg)) {
                    $arglist[]  = $arg;
                } elseif (count($e) > 1) {
                    $map    = $this->_aliasMap[$e[0]];
                    $table  = $map['table'];

                    $e[1]       = $table->getColumnName($e[1]);

                    if ( ! $table->hasColumn($e[1])) {
                        throw new Doctrine_Query_Exception('Unknown column ' . $e[1]);
                    }

                    $arglist[]  = $tableAlias . '.' . $e[1];
                } else {
                    $arglist[]  = $e[0];
                }
            }

            $sqlAlias = $tableAlias . '__' . count($this->aggregateMap);

            if (substr($name, 0, 1) !== '(') {
                $this->parts['select'][] = $name . '(' . $distinct . implode(', ', $arglist) . ') AS ' . $sqlAlias;
            } else {
                $this->parts['select'][] = $name . ' AS ' . $sqlAlias;
            }
            $this->aggregateMap[$alias] = $sqlAlias;
            $this->neededTables[] = $tableAlias;
        }
    }
    /**
     * getQueryBase
     * returns the base of the generated sql query
     * On mysql driver special strategy has to be used for DELETE statements
     *
     * @return string       the base of the generated sql query
     */
    public function getQueryBase()
    {
        switch ($this->type) {
            case self::DELETE:
                $q = 'DELETE FROM ';
            break;
            case self::UPDATE:
                $q = 'UPDATE ';
            break;
            case self::SELECT:
                $distinct = ($this->isDistinct()) ? 'DISTINCT ' : '';

                $q = 'SELECT ' . $distinct . implode(', ', $this->parts['select']) . ' FROM ';
            break;
        }
        return $q;
    }
    /**
     * buildFromPart
     *
     * @return string
     */
    public function buildFromPart()
    {
    	$q = '';
        foreach ($this->parts['from'] as $k => $part) {
            if ($k === 0) {
                $q .= $part;
                continue;
            }
            // preserve LEFT JOINs only if needed

            if (substr($part, 0, 9) === 'LEFT JOIN') {
                $e = explode(' ', $part);

                $aliases = array_merge($this->subqueryAliases,
                            array_keys($this->neededTables));

                if( ! in_array($e[3], $aliases) &&
                    ! in_array($e[2], $aliases) &&

                    ! empty($this->pendingFields)) {
                    continue;
                }

            }

            $e = explode(' ON ', $part);
            
            // we can always be sure that the first join condition exists
            $e2 = explode(' AND ', $e[1]);

            $part = $e[0] . ' ON ' . array_shift($e2);

            if ( ! empty($e2)) {
                $parser = new Doctrine_Query_JoinCondition($this);
                $part  .= ' AND ' . $parser->_parse(implode(' AND ', $e2));
            }

            $q .= ' ' . $part;
        }
        return $q;
    }
    /**
     * builds the sql query from the given parameters and applies things such as
     * column aggregation inheritance and limit subqueries if needed
     *
     * @param array $params             an array of prepared statement params (needed only in mysql driver
     *                                  when limit subquery algorithm is used)
     * @return string                   the built sql query
     */
    public function getQuery($params = array())
    {
        if (empty($this->parts['select']) || empty($this->parts['from'])) {
            return false;
        }

        $needsSubQuery = false;
        $subquery = '';
        $map   = reset($this->_aliasMap);
        $table = $map['table'];
        $rootAlias = key($this->_aliasMap);

        if ( ! empty($this->parts['limit']) && $this->needsSubquery && $table->getAttribute(Doctrine::ATTR_QUERY_LIMIT) == Doctrine::LIMIT_RECORDS) {
            $needsSubQuery = true;
            $this->limitSubqueryUsed = true;
        }

        // process all pending SELECT part subqueries
        $this->processPendingSubqueries();

        // build the basic query

        $str = '';
        if ($this->isDistinct()) {
            $str = 'DISTINCT ';
        }

        $q  = $this->getQueryBase();
        $q .= $this->buildFromPart();

        if ( ! empty($this->parts['set'])) {
            $q .= ' SET ' . implode(', ', $this->parts['set']);
        }

        $string = $this->applyInheritance();

        if ( ! empty($string)) {
            $this->parts['where'][] = '(' . $string . ')';
        }


        $modifyLimit = true;
        if ( ! empty($this->parts["limit"]) || ! empty($this->parts["offset"])) {

            if ($needsSubQuery) {
                $subquery = $this->getLimitSubquery();


                switch (strtolower($this->conn->getName())) {
                    case 'mysql':
                        // mysql doesn't support LIMIT in subqueries
                        $list     = $this->conn->execute($subquery, $params)->fetchAll(PDO::FETCH_COLUMN);
                        $subquery = implode(', ', $list);
                        break;
                    case 'pgsql':
                        // pgsql needs special nested LIMIT subquery
                        $subquery = 'SELECT doctrine_subquery_alias.' . $table->getIdentifier(). ' FROM (' . $subquery . ') AS doctrine_subquery_alias';
                        break;
                }

                $field = $this->aliasHandler->getShortAlias($rootAlias) . '.' . $table->getIdentifier();

                // only append the subquery if it actually contains something
                if ($subquery !== '') {
                    array_unshift($this->parts['where'], $field. ' IN (' . $subquery . ')');
                }

                $modifyLimit = false;
            }
        }

        $q .= ( ! empty($this->parts['where']))?   ' WHERE '    . implode(' AND ', $this->parts['where']) : '';
        $q .= ( ! empty($this->parts['groupby']))? ' GROUP BY ' . implode(', ', $this->parts['groupby'])  : '';
        $q .= ( ! empty($this->parts['having']))?  ' HAVING '   . implode(' AND ', $this->parts['having']): '';
        $q .= ( ! empty($this->parts['orderby']))? ' ORDER BY ' . implode(', ', $this->parts['orderby'])  : '';

        if ($modifyLimit) {
            $q = $this->conn->modifyLimitQuery($q, $this->parts['limit'], $this->parts['offset']);
        }

        // return to the previous state
        if ( ! empty($string)) {
            array_pop($this->parts['where']);
        }
        if ($needsSubQuery) {
            array_shift($this->parts['where']);
        }
        return $q;
    }
    /**
     * getLimitSubquery
     * this is method is used by the record limit algorithm
     *
     * when fetching one-to-many, many-to-many associated data with LIMIT clause
     * an additional subquery is needed for limiting the number of returned records instead
     * of limiting the number of sql result set rows
     *
     * @return string       the limit subquery
     */
    public function getLimitSubquery()
    {
        $map    = reset($this->_aliasMap);
        $table  = $map['table'];
        $componentAlias = key($this->_aliasMap);

        // get short alias
        $alias      = $this->aliasHandler->getShortAlias($componentAlias);
        $primaryKey = $alias . '.' . $table->getIdentifier();

        // initialize the base of the subquery
        $subquery   = 'SELECT DISTINCT ' . $primaryKey;

        if ($this->conn->getDBH()->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
            // pgsql needs the order by fields to be preserved in select clause

            foreach ($this->parts['orderby'] as $part) {
                $e = explode(' ', $part);

                // don't add primarykey column (its already in the select clause)
                if ($e[0] !== $primaryKey) {
                    $subquery .= ', ' . $e[0];
                }
            }
        }

        $subquery .= ' FROM';

        foreach ($this->parts['from'] as $part) {
            // preserve LEFT JOINs only if needed
            if (substr($part,0,9) === 'LEFT JOIN') {
                $e = explode(' ', $part);

                if ( ! in_array($e[3], $this->subqueryAliases) &&
                     ! in_array($e[2], $this->subqueryAliases)) {
                    continue;
                }
            }

            $subquery .= ' ' . $part;
        }

        // all conditions must be preserved in subquery
        $subquery .= ( ! empty($this->parts['where']))?   ' WHERE '    . implode(' AND ', $this->parts['where'])  : '';
        $subquery .= ( ! empty($this->parts['groupby']))? ' GROUP BY ' . implode(', ', $this->parts['groupby'])   : '';
        $subquery .= ( ! empty($this->parts['having']))?  ' HAVING '   . implode(' AND ', $this->parts['having']) : '';
        $subquery .= ( ! empty($this->parts['orderby']))? ' ORDER BY ' . implode(', ', $this->parts['orderby'])   : '';

        // add driver specific limit clause
        $subquery = $this->conn->modifyLimitQuery($subquery, $this->parts['limit'], $this->parts['offset']);

        $parts = Doctrine_Tokenizer::quoteExplode($subquery, ' ', "'", "'");

        foreach($parts as $k => $part) {
            if(strpos($part, "'") !== false) {
                continue;
            }

            if($this->aliasHandler->hasAliasFor($part)) {
                $parts[$k] = $this->aliasHandler->generateNewAlias($part);
            }

            if(strpos($part, '.') !== false) {
                $e = explode('.', $part);

                $trimmed = ltrim($e[0], '( ');
                $pos     = strpos($e[0], $trimmed);

                $e[0] = substr($e[0], 0, $pos) . $this->aliasHandler->generateNewAlias($trimmed);
                $parts[$k] = implode('.', $e);
            }
        }
        $subquery = implode(' ', $parts);

        return $subquery;
    }
    /**
     * tokenizeQuery
     * splits the given dql query into an array where keys
     * represent different query part names and values are
     * arrays splitted using sqlExplode method
     *
     * example:
     *
     * parameter:
     *      $query = "SELECT u.* FROM User u WHERE u.name LIKE ?"
     * returns:
     *      array('select' => array('u.*'),
     *            'from'   => array('User', 'u'),
     *            'where'  => array('u.name', 'LIKE', '?'))
     *
     * @param string $query                 DQL query
     * @throws Doctrine_Query_Exception     if some generic parsing error occurs
     * @return array                        an array containing the query string parts
     */
    public function tokenizeQuery($query)
    {
        $e = Doctrine_Tokenizer::sqlExplode($query, ' ');

        foreach($e as $k=>$part) {
            $part = trim($part);
            switch(strtolower($part)) {
                case 'delete':
                case 'update':
                case 'select':
                case 'set':
                case 'from':
                case 'where':
                case 'limit':
                case 'offset':
                case 'having':
                    $p = $part;
                    $parts[$part] = array();
                break;
                case 'order':
                case 'group':
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
                    if( ! isset($p))
                        throw new Doctrine_Query_Exception("Couldn't parse query.");

                    $parts[$p][] = $part;
            }
        }
        return $parts;
    }
    /**
     * DQL PARSER
     * parses a DQL query
     * first splits the query in parts and then uses individual
     * parsers for each part
     *
     * @param string $query                 DQL query
     * @param boolean $clear                whether or not to clear the aliases
     * @throws Doctrine_Query_Exception     if some generic parsing error occurs
     * @return Doctrine_Query
     */
    public function parseQuery($query, $clear = true)
    {
        if ($clear) {
            $this->clear();
        }

        $query = trim($query);
        $query = str_replace("\n", ' ', $query);
        $query = str_replace("\r", ' ', $query);

        $parts = $this->tokenizeQuery($query);

        foreach($parts as $k => $part) {
            $part = implode(' ', $part);
            switch(strtolower($k)) {
                case 'create':
                    $this->type = self::CREATE;
                break;
                case 'insert':
                    $this->type = self::INSERT;
                break;
                case 'delete':
                    $this->type = self::DELETE;
                break;
                case 'select':
                    $this->type = self::SELECT;
                    $this->parseSelect($part);
                break;
                case 'update':
                    $this->type = self::UPDATE;
                    $k = 'FROM';

                case 'from':
                    $class  = 'Doctrine_Query_' . ucwords(strtolower($k));
                    $parser = new $class($this);
                    $parser->parse($part);
                break;
                case 'set':
                    $class  = 'Doctrine_Query_' . ucwords(strtolower($k));
                    $parser = new $class($this);
                    $parser->parse($part);
                break;
                case 'group':
                case 'order':
                    $k .= 'by';
                case 'where':
                case 'having':
                    $class  = 'Doctrine_Query_' . ucwords(strtolower($k));
                    $parser = new $class($this);

                    $name = strtolower($k);
                    $parser->parse($part);
                break;
                case 'limit':
                    $this->parts['limit'] = trim($part);
                break;
                case 'offset':
                    $this->parts['offset'] = trim($part);
                break;
            }
        }

        return $this;
    }
    public function load($path, $loadFields = true) 
    {
        // parse custom join conditions
        $e = explode(' ON ', $path);
        
        $joinCondition = '';

        if (count($e) > 1) {
            $joinCondition = ' AND ' . $e[1];
            $path = $e[0];
        }

        $tmp           = explode(' ', $path);
        $originalAlias = (count($tmp) > 1) ? end($tmp) : null;

        $e = preg_split("/[.:]/", $tmp[0], -1);

        $fullPath = $tmp[0];
        $prevPath = '';
        $fullLength = strlen($fullPath);

        if (isset($this->_aliasMap[$e[0]])) {
            $table = $this->_aliasMap[$e[0]]['table'];

            $prevPath = $parent = array_shift($e);
        }

        foreach ($e as $key => $name) {
            // get length of the previous path
            $length = strlen($prevPath);

            // build the current component path
            $prevPath = ($prevPath) ? $prevPath . '.' . $name : $name;

            $delimeter = substr($fullPath, $length, 1);

            // if an alias is not given use the current path as an alias identifier
            if (strlen($prevPath) === $fullLength && isset($originalAlias)) {
                $componentAlias = $originalAlias;
            } else {
                $componentAlias = $prevPath;
            }

            if ( ! isset($table)) {
                // process the root of the path

                $table = $this->loadRoot($name, $componentAlias);
            } else {
                $join = ($delimeter == ':') ? 'INNER JOIN ' : 'LEFT JOIN ';

                $relation = $table->getRelation($name);

                $this->_aliasMap[$componentAlias] = array('table'    => $relation->getTable(),
                                                          'parent'   => $parent,
                                                          'relation' => $relation);
                if ( ! $relation->isOneToOne()) {
                   $this->needsSubquery = true;
                }

                $localAlias   = $this->getShortAlias($parent, $table->getTableName());
                $foreignAlias = $this->getShortAlias($componentAlias, $relation->getTable()->getTableName());
                $localSql     = $this->conn->quoteIdentifier($table->getTableName()) . ' ' . $localAlias;
                $foreignSql   = $this->conn->quoteIdentifier($relation->getTable()->getTableName()) . ' ' . $foreignAlias;

                $map = $relation->getTable()->inheritanceMap;
  
                if ( ! $loadFields || ! empty($map) || $joinCondition) {
                    $this->subqueryAliases[] = $foreignAlias;
                }

                if ($relation instanceof Doctrine_Relation_Association) {
                    $asf = $relation->getAssociationFactory();
  
                    $assocTableName = $asf->getTableName();
  
                    if( ! $loadFields || ! empty($map) || $joinCondition) {
                        $this->subqueryAliases[] = $assocTableName;
                    }

                    $assocPath = $prevPath . '.' . $asf->getComponentName();
  
                    $assocAlias = $this->getShortAlias($assocPath, $asf->getTableName());

                    $queryPart = $join . $assocTableName . ' ' . $assocAlias . ' ON ' . $localAlias  . '.'
                                                                  . $table->getIdentifier() . ' = '
                                                                  . $assocAlias . '.' . $relation->getLocal();

                    if ($relation instanceof Doctrine_Relation_Association_Self) {
                        $queryPart .= ' OR ' . $localAlias  . '.' . $table->getIdentifier() . ' = '
                                                                  . $assocAlias . '.' . $relation->getForeign();
                    }

                    $this->parts['from'][] = $queryPart;

                    $queryPart = $join . $foreignSql . ' ON ' . $foreignAlias . '.'
                                               . $relation->getTable()->getIdentifier() . ' = '
                                               . $assocAlias . '.' . $relation->getForeign()
                                               . $joinCondition;

                    if ($relation instanceof Doctrine_Relation_Association_Self) {
                        $queryPart .= ' OR ' . $foreignAlias  . '.' . $table->getIdentifier() . ' = '
                                             . $assocAlias . '.' . $relation->getLocal();
                    }

                } else {

                    $queryPart = $join . $foreignSql
                                       . ' ON ' . $localAlias .  '.'
                                       . $relation->getLocal() . ' = ' . $foreignAlias . '.' . $relation->getForeign()
                                       . $joinCondition;
                }
                $this->parts['from'][] = $queryPart;
            }
            if ($loadFields) {
                             	
                $restoreState = false;
                // load fields if necessary
                if ($loadFields && empty($this->pendingFields) 
                    && empty($this->pendingAggregates)
                    && empty($this->pendingSubqueries)) {

                    $this->pendingFields[$componentAlias] = array('*');

                    $restoreState = true;
                }

                if(isset($this->pendingFields[$componentAlias])) {
                    $this->processPendingFields($componentAlias);
                }

                if(isset($this->pendingAggregates[$componentAlias]) || isset($this->pendingAggregates[0])) {
                    $this->processPendingAggregates($componentAlias);
                }

                if ($restoreState) {
                    $this->pendingFields = array();
                    $this->pendingAggregates = array();
                }
            }
            $parent = $prevPath;
        }
        return end($this->_aliasMap);
    }
    /**
     * loadRoot
     *
     * @param string $name
     * @param string $componentAlias
     */
    public function loadRoot($name, $componentAlias)
    {
    	// get the connection for the component
        $this->conn = Doctrine_Manager::getInstance()
                      ->getConnectionForComponent($name);

        $table = $this->conn->getTable($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->aliasHandler->getShortAlias($componentAlias, $tableName);
        // quote table name
        $queryPart = $this->conn->quoteIdentifier($tableName);

        if ($this->type === self::SELECT) {
            $queryPart .= ' ' . $tableAlias;
        }

        $this->parts['from'][] = $queryPart;
        $this->tableAliases[$tableAlias]  = $componentAlias;
        $this->_aliasMap[$componentAlias] = array('table' => $table);
        
        return $table;
    }
	/**
 	 * count
 	 * fetches the count of the query
 	 *
 	 * This method executes the main query without all the
     * selected fields, ORDER BY part, LIMIT part and OFFSET part.
     *
     * Example:
     * Main query: 
     *      SELECT u.*, p.phonenumber FROM User u
     *          LEFT JOIN u.Phonenumber p 
     *          WHERE p.phonenumber = '123 123' LIMIT 10
     *
     * The query this method executes: 
     *      SELECT COUNT(DISTINCT u.id) FROM User u
     *          LEFT JOIN u.Phonenumber p
     *          WHERE p.phonenumber = '123 123'
     *
     * @param array $params        an array of prepared statement parameters
	 * @return integer             the count of this query
     */
	public function count($params = array())
    {
    	// initialize temporary variables
		$where  = $this->parts['where'];
		$having = $this->parts['having'];
		$map    = reset($this->_aliasMap);
		$componentAlias = key($this->_aliasMap);
		$table = $map['table'];

        // build the query base
		$q  = 'SELECT COUNT(DISTINCT ' . $this->aliasHandler->getShortAlias($table->getTableName())
            . '.' . $table->getIdentifier()
            . ') FROM ' . $this->buildFromPart();

        // append column aggregation inheritance (if needed)
        $string = $this->applyInheritance();

        if ( ! empty($string)) {
            $where[] = $string;
        }
        // append conditions
        $q .= ( ! empty($where)) ?  ' WHERE '  . implode(' AND ', $where) : '';
		$q .= ( ! empty($having)) ? ' HAVING ' . implode(' AND ', $having): '';

        if ( ! is_array($params)) {
            $params = array($params);
        }
        // append parameters
        $params = array_merge($this->params, $params);

		return (int) $this->getConnection()->fetchOne($q, $params);
	}
    /**
     * isLimitSubqueryUsed
     * whether or not limit subquery algorithm is used
     *
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
        $this->parseQuery($query);

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
        if (is_array($params)) {
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
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addHaving($having, $params = array())
    {
        if (is_array($params)) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params[] = $params;
        }
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
        return $this->getParser('select')->parse($select);
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
        $this->_parts['distinct'] = (bool) $flag;

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
        $this->_parts[self::FOR_UPDATE] = (bool) $flag;

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
     * @param string $update        DQL UPDATE part
     * @return Doctrine_Query
     */
    public function update($update)
    {
    	$this->type = self::UPDATE;

        return $this->getParser('from')->parse($update);
    }
    /**
     * set
     * sets the SET part of the query
     *
     * @param string $update        DQL UPDATE part
     * @return Doctrine_Query
     */
    public function set($key, $value)
    {
        return $this->getParser('set')->parse($key . ' = ' . $value);
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
        return $this->getParser('from')->parse('LEFT JOIN ' . $join);
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
        $this->params = (array) $params;

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
        $this->params = (array) $params;
        
        return $this->getParser('having')->parse($having);
    }
    /**
     * orderBy
     * sets the ORDER BY part of the query
     *
     * @param string $orderby      DQL ORDER BY part
     * @return Doctrine_Query
     */
    public function orderBy($orderby)
    {
        return $this->getParser('orderby')->parse($orderby);
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
        return $this->getParser('limit')->parse($limit);
    }
    /**
     * offset
     * sets the DQL query offset
     *
     * @param integer $offset       offset to be used for paginating the query
     * @return Doctrine_Query
     */
    public function offset($offset)
    {
        return $this->getParser('offset')->parse($offset);
    }
}

