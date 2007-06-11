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
Doctrine::autoload('Doctrine_Query_Abstract');
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
class Doctrine_Query extends Doctrine_Query_Abstract implements Countable
{
    protected $subqueryAliases   = array();
    /**
     * @param boolean $needsSubquery
     */
    protected $needsSubquery     = false;
    /**
     * @param boolean $isSubquery           whether or not this query object is a subquery of another 
     *                                      query object
     */
    protected $isSubquery;
    
    protected $isLimitSubqueryUsed = false;
    /**
     * @var array $_neededTableAliases      an array containing the needed table aliases
     */
    protected $_neededTables     = array();
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
     * @var array $_parsers                 an array of parser objects, each DQL query part has its own parser
     */
    protected $_parsers    = array();
    /**
     * @var array $_enumParams              an array containing the keys of the parameters that should be enumerated
     */
    protected $_enumParams = array();

    /**
     * @var array $_dqlParts                an array containing all DQL query parts
     */
    protected $_dqlParts   = array(
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
                            'limit'     => array(),
                            'offset'    => array(),
                            );
    /**
     * @var array $_pendingJoinConditions    an array containing pending joins
     */
    protected $_pendingJoinConditions = array();

    /**
     * create
     * returns a new Doctrine_Query object
     *
     * @param Doctrine_Connection $conn     optional connection parameter
     * @return Doctrine_Query
     */
    public static function create($conn = null)
    {
        return new Doctrine_Query($conn);
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
     * addPendingJoinCondition
     *
     * @param string $componentAlias    component alias
     * @param string $joinCondition     dql join condition
     * @return Doctrine_Query           this object
     */
    public function addPendingJoinCondition($componentAlias, $joinCondition)
    {
        $this->_pendingJoins[$componentAlias] = $joinCondition;
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
     * @param string $dqlAlias      the dql alias of an aggregate value
     * @return string
     */
    public function getAggregateAlias($dqlAlias)
    {
        if (isset($this->aggregateMap[$dqlAlias])) {
            return $this->aggregateMap[$dqlAlias];
        }
        if ( ! empty($this->pendingAggregates)) {
            $this->processPendingAggregates();
            
            return $this->getAggregateAlias($dqlAlias);
        }
        throw new Doctrine_Query_Exception('Unknown aggregate alias ' . $dqlAlias);
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
    public function parseQueryPart($queryPartName, $queryPart, $append = false) 
    {              

    	// sanity check
    	if ($queryPart === '' || $queryPart === null) {
            throw new Doctrine_Query_Exception('Empty ' . $queryPartName . ' part given.');
    	}

        // add query part to the dql part array
    	if ($append) {
    	    $this->_dqlParts[$queryPartName][] = $queryPart;
    	} else {
            $this->_dqlParts[$queryPartName] = array($queryPart);
    	}
    	
        // check for cache
        if ( ! $this->_options['resultSetCache']) {
    	    $parser = $this->getParser($queryPartName);
                         
            $sql = $parser->parse($queryPart);

            if (isset($sql)) {
                if ($append) {
                    $this->addQueryPart($queryPartName, $sql);
                } else {
                    $this->setQueryPart($queryPartName, $sql);
                }                
            }
    	}
    	   
        return $this;
    }
    /**
     * getDql
     * returns the DQL query associated with this object
     *
     * the query is built from $_dqlParts
     *
     * @return string   the DQL query
     */
    public function getDql()
    {
    	$q = '';
    	$q .= ( ! empty($this->_dqlParts['select']))?  'SELECT '    . implode(', ', $this->_dqlParts['select']) : '';
        $q .= ( ! empty($this->_dqlParts['from']))?    ' FROM '     . implode(' ', $this->_dqlParts['from']) : '';
        $q .= ( ! empty($this->_dqlParts['where']))?   ' WHERE '    . implode(' AND ', $this->_dqlParts['where']) : '';
        $q .= ( ! empty($this->_dqlParts['groupby']))? ' GROUP BY ' . implode(', ', $this->_dqlParts['groupby']) : '';
        $q .= ( ! empty($this->_dqlParts['having']))?  ' HAVING '   . implode(' AND ', $this->_dqlParts['having']) : '';
        $q .= ( ! empty($this->_dqlParts['orderby']))? ' ORDER BY ' . implode(', ', $this->_dqlParts['orderby']) : '';
        $q .= ( ! empty($this->_dqlParts['limit']))?   ' LIMIT '    . implode(' ', $this->_dqlParts['limit']) : '';
        $q .= ( ! empty($this->_dqlParts['offset']))?  ' OFFSET '   . implode(' ', $this->_dqlParts['offset']) : '';
        
        return $q;
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

        $pos   = strpos(trim($refs[0]), ' ');
        $first = substr($refs[0], 0, $pos);
        
        if ($first === 'DISTINCT') {
            $this->parts['distinct'] = true;
            
            $refs[0] = substr($refs[0], ++$pos);
        }

        foreach ($refs as $reference) {
            $reference = trim($reference);
            if (strpos($reference, '(') !== false) {
                if (substr($reference, 0, 1) === '(') {
                    // subselect found in SELECT part
                    $this->parseSubselect($reference);
                } else {
                    $this->parseAggregateFunction($reference);
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
    /**
     * parseAggregateFunction
     * parses an aggregate function and returns the parsed form
     *
     * @see Doctrine_Expression
     * @param string $expr                  DQL aggregate function
     * @throws Doctrine_Query_Exception     if unknown aggregate function given
     * @return array                        parsed form of given function
     */
    public function parseAggregateFunction($expr, $nestedCall = false)
    {
        $e    = Doctrine_Tokenizer::bracketExplode($expr, ' ');
        $func = $e[0];

        $pos  = strpos($func, '(');
        if ($pos === false) {
            return $expr;
        }

        // get the name of the function
        $name   = substr($func, 0, $pos);
        $argStr = substr($func, ($pos + 1), -1);

        $args   = array();
        // parse args
        foreach (Doctrine_Tokenizer::bracketExplode($argStr, ',') as $expr) {
           $args[] = $this->parseAggregateFunction($expr, true);
        }

        // convert DQL function to its RDBMS specific equivalent
        try {
            $expr = call_user_func_array(array($this->_conn->expression, $name), $args);
        } catch(Doctrine_Expression_Exception $e) {
            throw new Doctrine_Query_Exception('Unknown function ' . $func . '.');
        }

        if ( ! $nestedCall) {
            // try to find all component references
            preg_match_all("/[a-z0-9_]+\.[a-z0-9_]+[\.[a-z0-9]+]*/i", $argStr, $m);

            if (isset($e[1])) {
                if (strtoupper($e[1]) === 'AS') {
                    if ( ! isset($e[2])) {
                        throw new Doctrine_Query_Exception('Missing aggregate function alias.');
                    }
                    $alias = $e[2];
                } else {
                    $alias = $e[1];
                }
            } else {
                $alias = substr($expr, 0, strpos($expr, '('));
            }

            $this->pendingAggregates[] = array($expr, $m[0], $alias);
        }

        return $expr;
    }
    /**
     * processPendingSubqueries
     * processes pending subqueries
     *
     * subqueries can only be processed when the query is fully constructed
     * since some subqueries may be correlated
     *
     * @return void
     */
    public function processPendingSubqueries() 
    {
    	foreach ($this->pendingSubqueries as $value) {
            list($dql, $alias) = $value;

            $sql = $this->createSubquery()->parseQuery($dql, false)->getQuery();

            reset($this->_aliasMap);
            $componentAlias = key($this->_aliasMap);
            $tableAlias = $this->getTableAlias($componentAlias);

            $sqlAlias = $tableAlias . '__' . count($this->aggregateMap);

            $this->parts['select'][] = '(' . $sql . ') AS ' . $sqlAlias;

            $this->aggregateMap[$alias] = $sqlAlias;
            $this->_aliasMap[$componentAlias]['agg'][] = $alias;
        }
        $this->pendingSubqueries = array();
    }
    /** 
     * processPendingAggregates
     * processes pending aggregate values for given component alias
     *
     * @return void
     */
    public function processPendingAggregates()
    {
    	// iterate trhough all aggregates
        foreach ($this->pendingAggregates as $aggregate) {
            list ($expression, $components, $alias) = $aggregate;

            $tableAliases = array();

            // iterate through the component references within the aggregate function
            if ( ! empty ($components)) {
                foreach ($components as $component) {
                    $e = explode('.', $component);
    
                    $field = array_pop($e);
                    $componentAlias = implode('.', $e);
    
                    // check the existence of the component alias
                    if ( ! isset($this->_aliasMap[$componentAlias])) {
                        throw new Doctrine_Query_Exception('Unknown component alias ' . $componentAlias);
                    }
    
                    $table = $this->_aliasMap[$componentAlias]['table'];
    
                    $field = $table->getColumnName($field);
    
                    // check column existence
                    if ( ! $table->hasColumn($field)) {
                        throw new Doctrine_Query_Exception('Unknown column ' . $field);
                    }
    
                    $tableAlias = $this->getTableAlias($componentAlias);
    
                    $tableAliases[$tableAlias] = true;
    
                    // build sql expression
                    $expression = str_replace($component, $tableAlias . '.' . $field, $expression);
                }
            }

            if (count($tableAliases) !== 1) {
                $componentAlias = reset($this->tableAliases);
                $tableAlias = key($this->tableAliases);
            }

            $index    = count($this->aggregateMap);
            $sqlAlias = $tableAlias . '__' . $index;

            $this->parts['select'][] = $expression . ' AS ' . $sqlAlias;

            $this->aggregateMap[$alias] = $sqlAlias;

            $this->_aliasMap[$componentAlias]['agg'][$index] = $alias;

            $this->neededTables[] = $tableAlias;
        }
        // reset the state
        $this->pendingAggregates = array();
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
                $distinct = ($this->parts['distinct']) ? 'DISTINCT ' : '';

                $q = 'SELECT ' . $distinct . implode(', ', $this->parts['select']) . ' FROM ';
            break;
        }
        return $q;
    }
    /**
     * buildFromPart
     * builds the from part of the query and returns it
     *
     * @return string   the query sql from part
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

            if (isset($this->_pendingJoinConditions[$k])) {
                $parser = new Doctrine_Query_JoinCondition($this);
                $part  .= ' AND ' . $parser->parse($this->_pendingJoinConditions[$k]);

                unset($this->_pendingJoinConditions[$k]);
            }

            $q .= ' ' . $part;
            
            $this->parts['from'][$k] = $part;
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
    	// check if parser cache is on
    	if ($this->_cache) {
    	    foreach ($this->_dqlParts as $queryPartName => $queryParts) {
                if (is_array($queryParts) && ! empty($queryParts)) {

                    foreach ($queryParts as $queryPart) {
                        $this->getParser($queryPartName)->parse($queryPart);
                    }
                }
    	    }
        }
        if (empty($this->parts['from'])) {
            return false;
        }

        $needsSubQuery = false;
        $subquery = '';
        $map   = reset($this->_aliasMap);
        $table = $map['table'];
        $rootAlias = key($this->_aliasMap);

        if ( ! empty($this->parts['limit']) && $this->needsSubquery && $table->getAttribute(Doctrine::ATTR_QUERY_LIMIT) == Doctrine::LIMIT_RECORDS) {
            $this->isLimitSubqueryUsed = true;
            $needsSubQuery = true;
        }

        // process all pending SELECT part subqueries
        $this->processPendingSubqueries();
        $this->processPendingAggregates();

        // build the basic query

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


                switch (strtolower($this->_conn->getName())) {
                    case 'mysql':
                        // mysql doesn't support LIMIT in subqueries
                        $list     = $this->_conn->execute($subquery, $params)->fetchAll(PDO::FETCH_COLUMN);
                        $subquery = implode(', ', $list);
                        break;
                    case 'pgsql':
                        // pgsql needs special nested LIMIT subquery
                        $subquery = 'SELECT doctrine_subquery_alias.' . $table->getIdentifier(). ' FROM (' . $subquery . ') AS doctrine_subquery_alias';
                        break;
                }

                $field = $this->getTableAlias($rootAlias) . '.' . $table->getIdentifier();

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
            $q = $this->_conn->modifyLimitQuery($q, $this->parts['limit'], $this->parts['offset']);
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
        $alias      = $this->getTableAlias($componentAlias);
        $primaryKey = $alias . '.' . $table->getIdentifier();

        // initialize the base of the subquery
        $subquery   = 'SELECT DISTINCT ' . $primaryKey;

        if ($this->_conn->getDBH()->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
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
            if (substr($part, 0, 9) === 'LEFT JOIN') {
                $e = explode(' ', $part);
                
                if (empty($this->parts['orderby']) && empty($this->parts['where'])) {
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
        $subquery = $this->_conn->modifyLimitQuery($subquery, $this->parts['limit'], $this->parts['offset']);

        $parts = Doctrine_Tokenizer::quoteExplode($subquery, ' ', "'", "'");

        foreach($parts as $k => $part) {
            if(strpos($part, "'") !== false) {
                continue;
            }

            if($this->hasTableAlias($part)) {
                $parts[$k] = $this->generateNewTableAlias($part);
            }

            if(strpos($part, '.') !== false) {
                $e = explode('.', $part);

                $trimmed = ltrim($e[0], '( ');
                $pos     = strpos($e[0], $trimmed);

                $e[0] = substr($e[0], 0, $pos) . $this->generateNewTableAlias($trimmed);
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
            $k = strtolower($k);
            switch ($k) {
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
                    $this->parseQueryPart($k, $part);
                break;
                case 'update':
                    $this->type = self::UPDATE;
                    $k = 'FROM';

                case 'from':
                    $this->parseQueryPart($k, $part);
                break;
                case 'set':
                    $this->parseQueryPart($k, $part, true);
                break;
                case 'group':
                case 'order':
                    $k .= 'by';
                case 'where':
                case 'having':
                case 'limit':
                case 'offset':
                    $this->parseQueryPart($k, $part);
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
            $joinCondition = $e[1];
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
            
            // if the current alias already exists, skip it
            if (isset($this->_aliasMap[$componentAlias])) {
                continue;
            }

            if ( ! isset($table)) {
                // process the root of the path

                $table = $this->loadRoot($name, $componentAlias);
            } else {
                $join = ($delimeter == ':') ? 'INNER JOIN ' : 'LEFT JOIN ';

                $relation = $table->getRelation($name);
                $table    = $relation->getTable();
                $this->_aliasMap[$componentAlias] = array('table'    => $table,
                                                          'parent'   => $parent,
                                                          'relation' => $relation);
                if ( ! $relation->isOneToOne()) {
                   $this->needsSubquery = true;
                }

                $localAlias   = $this->getTableAlias($parent, $table->getTableName());
                $foreignAlias = $this->getTableAlias($componentAlias, $relation->getTable()->getTableName());
                $localSql     = $this->_conn->quoteIdentifier($table->getTableName()) . ' ' . $localAlias;
                $foreignSql   = $this->_conn->quoteIdentifier($relation->getTable()->getTableName()) . ' ' . $foreignAlias;

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
  
                    $assocAlias = $this->getTableAlias($assocPath, $asf->getTableName());

                    $queryPart = $join . $assocTableName . ' ' . $assocAlias . ' ON ' . $localAlias  . '.'
                                                               . $table->getIdentifier() . ' = '
                                                               . $assocAlias . '.' . $relation->getLocal();

                    if ($relation->isEqual()) {
                        $queryPart .= ' OR ' . $localAlias  . '.'
                                    . $table->getIdentifier() . ' = '
                                    . $assocAlias . '.' . $relation->getForeign();

                    }

                    $this->parts['from'][] = $queryPart;

                    $queryPart = $join . $foreignSql . ' ON ';
                    if ($relation->isEqual()) {
                        $queryPart .= '(';
                    } 
                    $queryPart .= $foreignAlias . '.'
                                . $relation->getTable()->getIdentifier() . ' = '
                                . $assocAlias . '.' . $relation->getForeign();

                    if ($relation->isEqual()) {
                        $queryPart .= ' OR '  . $foreignAlias   . '.' . $table->getIdentifier()
                                    . ' = '   . $assocAlias     . '.' . $relation->getLocal()
                                    . ') AND ' . $foreignAlias   . '.' . $table->getIdentifier()
                                    . ' != '  . $localAlias     . '.' . $table->getIdentifier();
                    }

                } else {

                    $queryPart = $join . $foreignSql
                                       . ' ON ' . $localAlias .  '.'
                                       . $relation->getLocal() . ' = ' . $foreignAlias . '.' . $relation->getForeign();
                }
                $this->parts['from'][$componentAlias] = $queryPart;
                if ( ! empty($joinCondition)) {
                    $this->_pendingJoinConditions[$componentAlias] = $joinCondition;
                }
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
                /**
                if(isset($this->pendingAggregates[$componentAlias]) || isset($this->pendingAggregates[0])) {
                    $this->processPendingAggregates($componentAlias);
                }
                */

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
        $this->_conn = Doctrine_Manager::getInstance()
                      ->getConnectionForComponent($name);

        $table = $this->_conn->getTable($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->getTableAlias($componentAlias, $tableName);
        // quote table name
        $queryPart = $this->_conn->quoteIdentifier($tableName);

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
     * The modified DQL query:
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
		$q  = 'SELECT COUNT(DISTINCT ' . $this->getTableAlias($componentAlias)
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
        $params = array_merge($this->_params, $params);

		return (int) $this->getConnection()->fetchOne($q, $params);
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
}
