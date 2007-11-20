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
 * @subpackage  Query
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query extends Doctrine_Query_Abstract implements Countable, Serializable
{
    protected $subqueryAliases = array();

    /**
     * @param boolean $needsSubquery
     */
    protected $needsSubquery = false;

    /**
     * @param boolean $isSubquery           whether or not this query object is a subquery of another 
     *                                      query object
     */
    protected $isSubquery;

    /**
     * @var array $_neededTables            an array containing the needed table aliases
     */
    protected $_neededTables = array();

    /**
     * @var array $pendingSubqueries        SELECT part subqueries, these are called pending subqueries since
     *                                      they cannot be parsed directly (some queries might be correlated)
     */
    protected $_pendingSubqueries = array();

    /**
     * @var array $_pendingFields           an array of pending fields (fields waiting to be parsed)
     */
    protected $_pendingFields = array();

    /**
     * @var array $_parsers                 an array of parser objects, each DQL query part has its own parser
     */
    protected $_parsers = array();

    /**
     * @var array $_dqlParts                an array containing all DQL query parts
     */
    protected $_dqlParts   = array(
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
     * @var array $_pendingJoinConditions    an array containing pending joins
     */
    protected $_pendingJoinConditions = array();

    protected $_expressionMap = array();
    
    /**
     * @var integer $_state   The current state of this query.
     */
    protected $_state = Doctrine_Query::STATE_CLEAN;
    
    /**
     * @var string $_sql            cached SQL query
     */
    protected $_sql;
    

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
     * Resets the query to the state just after it has been instantiated.
     */
    public function reset()
    {
        $this->_pendingJoinConditions = array();
        $this->_pendingSubqueries = array();
        $this->_pendingFields = array();
        $this->_neededTables = array();
        $this->_expressionMap = array();
        $this->subqueryAliases = array();
        $this->needsSubquery = false;
        $this->isLimitSubqueryUsed = false;
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
     * getParams
     *
     * @return array
     */
    public function getParams()
    {
        return array_merge($this->_params['set'], $this->_params['where'], $this->_params['having']);
    }
    
    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams(array $params = array()) {
        $this->_params = $params;
    }
    
    /**
     * getCachedForm
     * returns the cached form of this query for given resultSet
     *
     * @param array $resultSet
     * @return string           serialized string representation of this query
     */
    public function getCachedForm(array $resultSet)
    {
        $map = '';

        foreach ($this->getAliasMap() as $k => $v) {
            if ( ! isset($v['parent'])) {
                $map[$k][] = $v['table']->getComponentName();
            } else {
                $map[$k][] = $v['parent'] . '.' . $v['relation']->getAlias();
            }
            if (isset($v['agg'])) {
                $map[$k][] = $v['agg'];
            }
        }

        return serialize(array($resultSet, $map, $this->getTableAliases()));
    }
    
    /**
     * fetchArray
     * Convenience method to execute using array fetching as hydration mode.
     *
     * @param string $params
     * @return array
     */
    public function fetchArray($params = array()) {
        return $this->execute($params, Doctrine::HYDRATE_ARRAY);
    }
    
    /**
     * fetchOne
     * Convenience method to execute the query and return the first item
     * of the collection.
     *
     * @param string $params Parameters
     * @param int $hydrationMode Hydration mode
     * @return mixed Array or Doctrine_Collection or false if no result.
     */
    public function fetchOne($params = array(), $hydrationMode = null)
    {
        $collection = $this->execute($params, $hydrationMode);

        if (count($collection) === 0) {
            return false;
        }

        if ($collection instanceof Doctrine_Collection) {
            return $collection->getFirst();
        } else if (is_array($collection)) {
            return array_shift($collection);
        }

        return false;
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
            // mark the expression as used
            $this->_expressionMap[$dqlAlias][1] = true;

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
     * @todo Doc/Description: What is the parameter for? Which parsers are available?
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
        if ($this->_state === self::STATE_LOCKED) {
            throw new Doctrine_Query_Exception('This query object is locked. No query parts can be manipulated.');
        }

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

        if ($this->_state === self::STATE_DIRECT) {
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

        $this->_state = Doctrine_Query::STATE_DIRTY;

        return $this;
    }

    /**
     * getDqlPart
     * returns a specific DQL query part.
     *
     * @param string $queryPart     the name of the query part
     * @return string   the DQL query part
     * @todo Description: List which query parts exist or point to the method/property
     *       where they are listed.
     */
    public function getDqlPart($queryPart)
    {
        if ( ! isset($this->_dqlParts[$queryPart])) {
           throw new Doctrine_Query_Exception('Unknown query part ' . $queryPart);
        }

        return $this->_dqlParts[$queryPart];
    }

    /**
     * getDql
     * returns the DQL query that is represented by this query object.
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
     * @todo Description: What is a 'pending field' (and are there non-pending fields, too)?
     *       What is 'processed'? (Meaning: What information is gathered & stored away)
     */
    public function processPendingFields($componentAlias)
    {
        $tableAlias = $this->getTableAlias($componentAlias);
        $table      = $this->_aliasMap[$componentAlias]['table'];

        if ( ! isset($this->_pendingFields[$componentAlias])) {
            return;
        }

        $fields = $this->_pendingFields[$componentAlias];

        // check for wildcards
        if (in_array('*', $fields)) {
            //echo "<br />";Doctrine::dump($table->getColumnNames()); echo "<br />";
            $fields = $table->getFieldNames();
        } else {
            // only auto-add the primary key fields if this query object is not
            // a subquery of another query object
            if ( ! $this->isSubquery) {
                $fields = array_unique(array_merge((array) $table->getIdentifier(), $fields));
            }
        }
        
        $sql = array();
        foreach ($fields as $fieldName) {
            $columnName = $table->getColumnName($fieldName);
            if (($owner = $table->getColumnOwner($columnName)) !== null && 
                    $owner !== $table->getComponentName()) {

                $parent = $this->_conn->getTable($owner);

                $columnName = $parent->getColumnName($fieldName);

                $parentAlias = $this->getTableAlias($componentAlias . '.' . $parent->getComponentName());

                $sql[] = $this->_conn->quoteIdentifier($parentAlias . '.' . $columnName)
                       . ' AS '
                       . $this->_conn->quoteIdentifier($tableAlias . '__' . $columnName);
            } else {

                $columnName = $table->getColumnName($fieldName);

                $sql[] = $this->_conn->quoteIdentifier($tableAlias . '.' . $columnName)
                       . ' AS '
                       . $this->_conn->quoteIdentifier($tableAlias . '__' . $columnName);
            }
        }

        $this->_neededTables[] = $tableAlias;
        //Doctrine::dump(implode(', ', $sql));
        //echo "<br /><br />";
        return implode(', ', $sql);
    }

    /**
     * parseSelectField
     *
     * @throws Doctrine_Query_Exception     if unknown component alias has been given
     * @return void
     * @todo Description: Explain what this method does. Is there a relation to parseSelect()?
     *       (It doesnt seem to get called from there...?). In what circumstances is this method
     *       used?
     */
    public function parseSelectField($field)
    {
        $terms = explode('.', $field);

        if (isset($terms[1])) {
            $componentAlias = $terms[0];
            $field = $terms[1];
        } else {
            reset($this->_aliasMap);
            $componentAlias = key($this->_aliasMap);
            $fields = $terms[0];
        }

        $tableAlias = $this->getTableAlias($componentAlias);
        $table      = $this->_aliasMap[$componentAlias]['table'];


        // check for wildcards
        if ($field === '*') {
            $sql = array();

            foreach ($table->getColumnNames() as $field) {
                $sql[] = $this->parseSelectField($componentAlias . '.' . $field);
            }

            return implode(', ', $sql);
        } else {

        }

        $name = $table->getColumnName($field);

        $this->_neededTables[] = $tableAlias;

        return $this->_conn->quoteIdentifier($tableAlias . '.' . $name)
               . ' AS '
               . $this->_conn->quoteIdentifier($tableAlias . '__' . $name);
    }

    /**
     * getExpressionOwner
     * returns the component alias for owner of given expression
     *
     * @param string $expr      expression from which to get to owner from
     * @return string           the component alias
     * @todo Description: What does it mean if a component is an 'owner' of an expression?
     *       What kind of 'expression' are we talking about here?
     */
    public function getExpressionOwner($expr)
    {
        if (strtoupper(substr(trim($expr, '( '), 0, 6)) !== 'SELECT') {
            preg_match_all("/[a-z0-9_]+\.[a-z0-9_]+[\.[a-z0-9]+]*/i", $expr, $matches);

            $match = current($matches);

            if (isset($match[0])) {
                $terms = explode('.', $match[0]);

                return $terms[0];
            }
        }
        return $this->getRootAlias();

    }

    /**
     * parseSelect
     * parses the query select part and
     * adds selected fields to pendingFields array
     *
     * @param string $dql
     * @todo Description: What information is extracted (and then stored)?
     */
    public function parseSelect($dql)
    {
        $refs = Doctrine_Tokenizer::sqlExplode($dql, ',');

        $pos   = strpos(trim($refs[0]), ' ');
        $first = substr($refs[0], 0, $pos);

        // check for DISTINCT keyword
        if ($first === 'DISTINCT') {
            $this->parts['distinct'] = true;

            $refs[0] = substr($refs[0], ++$pos);
        }

        $parsedComponents = array();

        foreach ($refs as $reference) {
            $reference = trim($reference);

            if (empty($reference)) {
                continue;
            }

            $terms = Doctrine_Tokenizer::sqlExplode($reference, ' ');

            $pos   = strpos($terms[0], '(');

            if (count($terms) > 1 || $pos !== false) {
                $expression = array_shift($terms);
                $alias = array_pop($terms);

                if ( ! $alias) {
                    $alias = substr($expression, 0, $pos);
                }

                $componentAlias = $this->getExpressionOwner($expression);
                $expression = $this->parseClause($expression);

                $tableAlias = $this->getTableAlias($componentAlias);

                $index    = count($this->aggregateMap);

                $sqlAlias = $this->_conn->quoteIdentifier($tableAlias . '__' . $index);

                $this->parts['select'][] = $expression . ' AS ' . $sqlAlias;

                $this->aggregateMap[$alias] = $sqlAlias;
                $this->_expressionMap[$alias][0] = $expression;

                $this->_aliasMap[$componentAlias]['agg'][$index] = $alias;

                $this->_neededTables[] = $tableAlias;
            } else {
                $e = explode('.', $terms[0]);

                if (isset($e[1])) {
                    $componentAlias = $e[0];
                    $field = $e[1];
                } else {
                    reset($this->_aliasMap);
                    $componentAlias = key($this->_aliasMap);
                    $field = $e[0];
                }

                $this->_pendingFields[$componentAlias][] = $field;
            }
        }
    }

    /**
     * parseClause
     * parses given DQL clause
     *
     * this method handles five tasks:
     *
     * 1. Converts all DQL functions to their native SQL equivalents
     * 2. Converts all component references to their table alias equivalents
     * 3. Converts all column aliases to actual column names
     * 4. Quotes all identifiers
     * 5. Parses nested clauses and subqueries recursively
     *
     * @return string   SQL string
     * @todo Description: What is a 'dql clause' (and what not)?
     *       Refactor: Too long & nesting level
     */
    public function parseClause($clause)
    {
    	$clause = trim($clause);

    	if (is_numeric($clause)) {
    	   return $clause;
    	}

        $terms = Doctrine_Tokenizer::clauseExplode($clause, array(' ', '+', '-', '*', '/'));

        $str = '';
        foreach ($terms as $term) {
            $pos = strpos($term[0], '(');
            
            if ($pos !== false) {
                $name = substr($term[0], 0, $pos);
                if ($name !== '') {
                    $argStr = substr($term[0], ($pos + 1), -1);

                    $args   = array();
                    // parse args

                    foreach (Doctrine_Tokenizer::sqlExplode($argStr, ',') as $expr) {
                       $args[] = $this->parseClause($expr);
                    }

                    // convert DQL function to its RDBMS specific equivalent
                    try {
                        $expr = call_user_func_array(array($this->_conn->expression, $name), $args);
                    } catch (Doctrine_Expression_Exception $e) {
                        throw new Doctrine_Query_Exception('Unknown function ' . $expr . '.');
                    }
                    $term[0] = $expr;
                } else {
                    $trimmed = trim(Doctrine_Tokenizer::bracketTrim($term[0]));

                    // check for possible subqueries
                    if (substr($trimmed, 0, 4) == 'FROM' || substr($trimmed, 0, 6) == 'SELECT') {
                        // parse subquery
                        $trimmed = $this->createSubquery()->parseQuery($trimmed)->getQuery();
                    } else {
                        // parse normal clause
                        $trimmed = $this->parseClause($trimmed);
                    }

                    $term[0] = '(' . $trimmed . ')';
                }
            } else {
                if (substr($term[0], 0, 1) !== "'" && substr($term[0], -1) !== "'") {
                    
                    if (strpos($term[0], '.') !== false) {
                        if ( ! is_numeric($term[0])) {
                            $e = explode('.', $term[0]);

                            $field = array_pop($e);

                            if ($this->getType() === Doctrine_Query::SELECT) {
                                $componentAlias = implode('.', $e);
    
                                if (empty($componentAlias)) {
                                    $componentAlias = $this->getRootAlias();
                                }
    
                                $this->load($componentAlias);

                                // check the existence of the component alias
                                if ( ! isset($this->_aliasMap[$componentAlias])) {
                                    throw new Doctrine_Query_Exception('Unknown component alias ' . $componentAlias);
                                }

                                $table = $this->_aliasMap[$componentAlias]['table'];
    
                                // get the actual field name from alias
                                $field = $table->getColumnName($field);
    
                                // check column existence
                                if ( ! $table->hasColumn($field)) {
                                    throw new Doctrine_Query_Exception('Unknown column ' . $field);
                                }
    
                                $tableAlias = $this->getTableAlias($componentAlias);

                                // build sql expression
                                $term[0] = $this->_conn->quoteIdentifier($tableAlias)
                                         . '.'
                                         . $this->_conn->quoteIdentifier($field);
                            } else {
                                // build sql expression
                                $term[0] = $this->_conn->quoteIdentifier($field);
                            }
                        }
                    } else {

                        if ( ! empty($term[0]) &&
                             ! in_array(strtoupper($term[0]), self::$_keywords) &&
                             ! is_numeric($term[0])) {

                            $componentAlias = $this->getRootAlias();

                            $found = false;

                            if ($componentAlias !== false && 
                                $componentAlias !== null) {
                                $table = $this->_aliasMap[$componentAlias]['table'];

                                // check column existence
                                if ($table->hasColumn($term[0])) {
                                    $found = true;
    
                                    // get the actual field name from alias
                                    $term[0] = $table->getColumnName($term[0]);

                                    $tableAlias = $this->getTableAlias($componentAlias);

                                    if ($this->getType() === Doctrine_Query::SELECT) {
                                        // build sql expression
                                        $term[0] = $this->_conn->quoteIdentifier($tableAlias)
                                                 . '.'
                                                 . $this->_conn->quoteIdentifier($term[0]);
                                    } else {
                                        // build sql expression
                                        $term[0] = $this->_conn->quoteIdentifier($term[0]);
                                    }
                                } else {
                                    $found = false;
                                }
                            }

                            if ( ! $found) {
                                $term[0] = $this->getAggregateAlias($term[0]);
                            }
                        }
                    }
                }
            }

            $str .= $term[0] . $term[1];
        }
        return $str;
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
     * @todo Better description. i.e. What is a 'pending subquery'? What does 'processed' mean?
     *       (parsed? sql is constructed? some information is gathered?)
     */
    public function processPendingSubqueries()
    {
        foreach ($this->_pendingSubqueries as $value) {
            list($dql, $alias) = $value;

            $subquery = $this->createSubquery();

            $sql = $subquery->parseQuery($dql, false)->getQuery();

            reset($this->_aliasMap);
            $componentAlias = key($this->_aliasMap);
            $tableAlias = $this->getTableAlias($componentAlias);

            $sqlAlias = $tableAlias . '__' . count($this->aggregateMap);

            $this->parts['select'][] = '(' . $sql . ') AS ' . $this->_conn->quoteIdentifier($sqlAlias);

            $this->aggregateMap[$alias] = $sqlAlias;
            $this->_aliasMap[$componentAlias]['agg'][] = $alias;
        }
        $this->_pendingSubqueries = array();
    }

    /**
     * processPendingAggregates
     * processes pending aggregate values for given component alias
     *
     * @return void
     * @todo Better description. i.e. What is a 'pending aggregate'? What does 'processed' mean?
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

                    if (is_numeric($component)) {
                        continue;
                    }

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

                    $identifier = $this->_conn->quoteIdentifier($tableAlias . '.' . $field);
                    $expression = str_replace($component, $identifier, $expression);
                }
            }

            if (count($tableAliases) !== 1) {
                $componentAlias = reset($this->tableAliases);
                $tableAlias = key($this->tableAliases);
            }

            $index    = count($this->aggregateMap);
            $sqlAlias = $this->_conn->quoteIdentifier($tableAlias . '__' . $index);

            $this->parts['select'][] = $expression . ' AS ' . $sqlAlias;

            $this->aggregateMap[$alias] = $sqlAlias;
            $this->_expressionMap[$alias][0] = $expression;

            $this->_aliasMap[$componentAlias]['agg'][$index] = $alias;

            $this->_neededTables[] = $tableAlias;
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
			// Check if it's JOIN, if not add a comma separator instead of space
            if (!preg_match('/\bJOIN\b/i', $part) && !isset($this->_pendingJoinConditions[$k])) {
                $q .= ', ' . $part;
            } else {
                if (substr($part, 0, 9) === 'LEFT JOIN') {
                    $e = explode(' ', $part);

                    $aliases = array_merge($this->subqueryAliases,
                                array_keys($this->_neededTables));

                    if ( ! in_array($e[3], $aliases) &&
                        ! in_array($e[2], $aliases) &&

                        ! empty($this->_pendingFields)) {
                        continue;
                    }

                }

                if (isset($this->_pendingJoinConditions[$k])) {
                    $parser = new Doctrine_Query_JoinCondition($this);

                    if (strpos($part, ' ON ') !== false) {
                        $part .= ' AND ';
                    } else {
                        $part .= ' ON ';
                    }
                    $part .= $parser->parse($this->_pendingJoinConditions[$k]);

                    unset($this->_pendingJoinConditions[$k]);
                }

                $q .= ' ' . $part;
            }

            $this->parts['from'][$k] = $part;
        }
        return $q;
    }

    /**
     * preQuery
     *
     * Empty template method to provide Query subclasses with the possibility
     * to hook into the query building procedure, doing any custom / specialized
     * query building procedures that are neccessary.
     *
     * @return void
     */
    public function preQuery()
    {

    }

    /**
     * postQuery
     *
     * Empty template method to provide Query subclasses with the possibility
     * to hook into the query building procedure, doing any custom / specialized
     * post query procedures (for example logging) that are neccessary.
     *
     * @return void
     */
    public function postQuery()
    {

    }

    /**
     * processQueryPart
     * parses given query part
     *
     * @param string $queryPartName     the name of the query part
     * @param array $queryParts         an array containing the query part data
     * @return Doctrine_Query           this object
     * @todo Better description. "parses given query part" ??? Then wheres the difference
     *       between process/parseQueryPart? I suppose this does something different.
     */
    public function processQueryPart($queryPartName, $queryParts)
    {
        $this->removeQueryPart($queryPartName);

        if (is_array($queryParts) && ! empty($queryParts)) {

            foreach ($queryParts as $queryPart) {
                $parser = $this->getParser($queryPartName);

                $sql = $parser->parse($queryPart);

                if (isset($sql)) {
                    if ($queryPartName == 'limit' ||
                        $queryPartName == 'offset') {

                        $this->setQueryPart($queryPartName, $sql);
                    } else {
                        $this->addQueryPart($queryPartName, $sql);
                    }
                }
            }
        }
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
        if ($this->_state !== self::STATE_DIRTY) {
           return $this->_sql;
        }

        // reset the state
        if ( ! $this->isSubquery()) {
            $this->_aliasMap = array();
            $this->pendingAggregates = array();
            $this->aggregateMap = array();
        }
        $this->reset();   

        // parse the DQL parts
        foreach ($this->_dqlParts as $queryPartName => $queryParts) {
            $this->processQueryPart($queryPartName, $queryParts);
        }
        
        $params = $this->convertEnums($params);

        $this->_state = self::STATE_DIRECT;

        // invoke the preQuery hook
        $this->preQuery();        
        $this->_state = self::STATE_CLEAN;

        if (empty($this->parts['from'])) {
            return false;
        }

        $needsSubQuery = false;
        $subquery = '';
        $map   = reset($this->_aliasMap);
        $table = $map['table'];
        $rootAlias = key($this->_aliasMap);

        if ( ! empty($this->parts['limit']) && $this->needsSubquery &&
                $table->getAttribute(Doctrine::ATTR_QUERY_LIMIT) == Doctrine::LIMIT_RECORDS) {
            $this->isLimitSubqueryUsed = true;
            $needsSubQuery = true;
        }

        $sql = array();
        foreach ($this->_aliasMap as $alias => $map) {
            $fieldSql = $this->processPendingFields($alias);
            if ( ! empty($fieldSql)) {
                $sql[] = $fieldSql;
            }
        }
        if ( ! empty($sql)) {
            array_unshift($this->parts['select'], implode(', ', $sql));
        }

        $this->_pendingFields = array();

        // build the basic query
        $q  = $this->getQueryBase();
        $q .= $this->buildFromPart();

        if ( ! empty($this->parts['set'])) {
            $q .= ' SET ' . implode(', ', $this->parts['set']);
        }


        $string = $this->applyInheritance();

        // apply inheritance to WHERE part
        if ( ! empty($string)) {
            if (substr($string, 0, 1) === '(' && substr($string, -1) === ')') {
                $this->parts['where'][] = $string;
            } else {
                $this->parts['where'][] = '(' . $string . ')';
            }
        }

        $modifyLimit = true;
        if ( ! empty($this->parts['limit']) || ! empty($this->parts['offset'])) {

            if ($needsSubQuery) {
                $subquery = $this->getLimitSubquery();
                // what about composite keys?
                $idColumnName = $table->getColumnName($table->getIdentifier());
                switch (strtolower($this->_conn->getName())) {
                    case 'mysql':
                        // mysql doesn't support LIMIT in subqueries
                        $list     = $this->_conn->execute($subquery, $params)->fetchAll(Doctrine::FETCH_COLUMN);
                        $subquery = implode(', ', array_map(array($this->_conn, 'quote'), $list));
                        break;
                    case 'pgsql':
                        // pgsql needs special nested LIMIT subquery
                        $subquery = 'SELECT doctrine_subquery_alias.' . $idColumnName . ' FROM (' . $subquery . ') AS doctrine_subquery_alias';
                        break;
                }

                $field = $this->getTableAlias($rootAlias) . '.' . $idColumnName;

                // only append the subquery if it actually contains something
                if ($subquery !== '') {
                    array_unshift($this->parts['where'], $this->_conn->quoteIdentifier($field) . ' IN (' . $subquery . ')');
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
        $this->_sql = $q;

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
     * @todo A little refactor to make the method easier to understand & maybe shorter?
     */
    public function getLimitSubquery()
    {
        $map    = reset($this->_aliasMap);
        $table  = $map['table'];
        $componentAlias = key($this->_aliasMap);

        // get short alias
        $alias      = $this->getTableAlias($componentAlias);
        // what about composite keys?
        $primaryKey = $alias . '.' . $table->getColumnName($table->getIdentifier());

        // initialize the base of the subquery
        $subquery   = 'SELECT DISTINCT ' . $this->_conn->quoteIdentifier($primaryKey);

        $driverName = $this->_conn->getAttribute(Doctrine::ATTR_DRIVER_NAME);

        // pgsql needs the order by fields to be preserved in select clause
        if ($driverName == 'pgsql') {
            foreach ($this->parts['orderby'] as $part) {
                $part = trim($part);
                $e = Doctrine_Tokenizer::bracketExplode($part, ' ');
                $part = trim($e[0]);

                if (strpos($part, '.') === false) {
                    continue;
                }

                // don't add functions
                if (strpos($part, '(') !== false) {
                    continue;
                }

                // don't add primarykey column (its already in the select clause)
                if ($part !== $primaryKey) {
                    $subquery .= ', ' . $part;
                }
            }
        }

        if ($driverName == 'mysql' || $driverName == 'pgsql') {
            foreach ($this->_expressionMap as $dqlAlias => $expr) {
                if (isset($expr[1])) {
                    $subquery .= ', ' . $expr[0] . ' AS ' . $this->aggregateMap[$dqlAlias];
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

        foreach ($parts as $k => $part) {
            if (strpos($part, ' ') !== false) {
                continue;
            }

            $part = trim($part, "\"'`");

            if ($this->hasTableAlias($part)) {
                $parts[$k] = $this->_conn->quoteIdentifier($this->generateNewTableAlias($part));
                continue;
            }

            if (strpos($part, '.') === false) {
                continue;
            }
            preg_match_all("/[a-zA-Z0-9_]+\.[a-z0-9_]+/i", $part, $m);

            foreach ($m[0] as $match) {
                $e = explode('.', $match);
                $e[0] = $this->generateNewTableAlias($e[0]);

                $parts[$k] = str_replace($match, implode('.', $e), $parts[$k]);
            }
        }

        if ($driverName == 'mysql' || $driverName == 'pgsql') {
            foreach ($parts as $k => $part) {
                if (strpos($part, "'") !== false) {
                    continue;
                }
                if (strpos($part, '__') == false) {
                    continue;
                }

                preg_match_all("/[a-zA-Z0-9_]+\_\_[a-z0-9_]+/i", $part, $m);

                foreach ($m[0] as $match) {
                    $e = explode('__', $match);
                    $e[0] = $this->generateNewTableAlias($e[0]);

                    $parts[$k] = str_replace($match, implode('__', $e), $parts[$k]);
                }
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

        foreach ($e as $k=>$part) {
            $part = trim($part);
            switch (strtolower($part)) {
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
                    if (isset($e[$i]) && strtolower($e[$i]) === 'by') {
                        $p = $part;
                        $parts[$part] = array();
                    } else {
                        $parts[$p][] = $part;
                    }
                break;
                case 'by':
                    continue;
                default:
                    if ( ! isset($p)) {
                        throw new Doctrine_Query_Exception("Couldn't parse query.");
                    }

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

        foreach ($parts as $k => $part) {
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
                    $k = 'from';
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
    
    /**
     * @todo DESCRIBE ME! REFACTOR ME! I'M FAR TOO LONG AND COMPLEX! HARD TO UNDERSTAND!
     */
    public function load($path, $loadFields = true)
    {
    	if (isset($this->_aliasMap[$path])) {
    	   return $this->_aliasMap[$path];
    	}
        $e = Doctrine_Tokenizer::quoteExplode($path, ' INDEXBY ');

        $mapWith = null;
        if (count($e) > 1) {
            $mapWith = trim($e[1]);

            $path = $e[0];
        }

        // parse custom join conditions
        $e = explode(' ON ', $path);

        $joinCondition = '';

        if (count($e) > 1) {
            $joinCondition = $e[1];
            $overrideJoin = true;
            $path = $e[0];
        } else {
            $e = explode(' WITH ', $path);

            if (count($e) > 1) {
                $joinCondition = $e[1];
                $path = $e[0];
            }
            $overrideJoin = false;
        }

        $tmp            = explode(' ', $path);
        $componentAlias = $originalAlias = (count($tmp) > 1) ? end($tmp) : null;

        $e = preg_split("/[.:]/", $tmp[0], -1);

        $fullPath = $tmp[0];
        $prevPath = '';
        $fullLength = strlen($fullPath);

        if (isset($this->_aliasMap[$e[0]])) {
            $table = $this->_aliasMap[$e[0]]['table'];
            $componentAlias = $e[0];

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
                $localTable = $table;

                $table    = $relation->getTable();
                $this->_aliasMap[$componentAlias] = array('table'    => $table,
                                                          'parent'   => $parent,
                                                          'relation' => $relation,
                                                          'map'      => null);
                if ( ! $relation->isOneToOne()) {
                   $this->needsSubquery = true;
                }

                $localAlias   = $this->getTableAlias($parent, $table->getTableName());
                $foreignAlias = $this->getTableAlias($componentAlias, $relation->getTable()->getTableName());
                $localSql     = $this->_conn->quoteIdentifier($table->getTableName())
                              . ' '
                              . $this->_conn->quoteIdentifier($localAlias);

                $foreignSql   = $this->_conn->quoteIdentifier($relation->getTable()->getTableName())
                              . ' '
                              . $this->_conn->quoteIdentifier($foreignAlias);

                $map = $relation->getTable()->inheritanceMap;

                if ( ! $loadFields || ! empty($map) || $joinCondition) {
                    $this->subqueryAliases[] = $foreignAlias;
                }

                if ($relation instanceof Doctrine_Relation_Association) {
                    $asf = $relation->getAssociationTable();

                    $assocTableName = $asf->getTableName();

                    if ( ! $loadFields || ! empty($map) || $joinCondition) {
                        $this->subqueryAliases[] = $assocTableName;
                    }

                    $assocPath = $prevPath . '.' . $asf->getComponentName();

                    $this->_aliasMap[$assocPath] = array('parent' => $prevPath, 'relation' => $relation, 'table' => $asf);

                    $assocAlias = $this->getTableAlias($assocPath, $asf->getTableName());

                    $queryPart = $join . $assocTableName . ' ' . $assocAlias;

                    $queryPart .= ' ON ' . $localAlias
                                . '.'
                                . $localTable->getIdentifier() // what about composite keys?
                                . ' = '
                                . $assocAlias . '.' . $relation->getLocal();

                    if ($relation->isEqual()) {
                        // equal nest relation needs additional condition
                        $queryPart .= ' OR ' . $localAlias
                                    . '.'
                                    . $table->getColumnName($table->getIdentifier())
                                    . ' = '
                                    . $assocAlias . '.' . $relation->getForeign();
                    }

                    $this->parts['from'][] = $queryPart;

                    $queryPart = $join . $foreignSql;

                    if ( ! $overrideJoin) {
                        $queryPart .= ' ON ';

                        if ($relation->isEqual()) {
                            $queryPart .= '(';
                        }

                        $queryPart .= $this->_conn->quoteIdentifier($foreignAlias . '.' . $relation->getTable()->getIdentifier())
                                    . ' = '
                                    . $this->_conn->quoteIdentifier($assocAlias . '.' . $relation->getForeign());

                        if ($relation->isEqual()) {
                            $queryPart .= ' OR '
                                        . $this->_conn->quoteIdentifier($foreignAlias . '.' . $table->getColumnName($table->getIdentifier()))
                                        . ' = ' 
                                        . $this->_conn->quoteIdentifier($assocAlias . '.' . $relation->getLocal())
                                        . ') AND ' 
                                        . $this->_conn->quoteIdentifier($foreignAlias . '.' . $table->getIdentifier())
                                        . ' != '  
                                        . $this->_conn->quoteIdentifier($localAlias . '.' . $table->getIdentifier());
                        }
                    }
                } else {

                    $queryPart = $join . $foreignSql;

                    if ( ! $overrideJoin) {
                        $queryPart .= ' ON '
                                   . $this->_conn->quoteIdentifier($localAlias . '.' . $relation->getLocal())
                                   . ' = ' 
                                   . $this->_conn->quoteIdentifier($foreignAlias . '.' . $relation->getForeign());
                    }
                }

                $queryPart .= $this->buildInheritanceJoinSql($table->getComponentName(), $componentAlias);

                $this->parts['from'][$componentAlias] = $queryPart;
                if ( ! empty($joinCondition)) {
                    $this->_pendingJoinConditions[$componentAlias] = $joinCondition;
                }
            }
            if ($loadFields) {

                $restoreState = false;
                // load fields if necessary
                if ($loadFields && empty($this->_dqlParts['select'])) {
                    $this->_pendingFields[$componentAlias] = array('*');
                }
            }
            $parent = $prevPath;
        }
        
        $table = $this->_aliasMap[$componentAlias]['table'];

        $indexBy = null;

        if (isset($mapWith)) {
            $e = explode('.', $mapWith);

            if (isset($e[1])) {
                $indexBy = $e[1];
            }
        } elseif ($table->getBoundQueryPart('indexBy') !== null) {
            $indexBy = $table->getBoundQueryPart('indexBy');
        }

        if ($indexBy !== null) {
            if ( ! $table->hasColumn($indexBy)) {
                throw new Doctrine_Query_Exception("Couldn't use key mapping. Column " . $indexBy . " does not exist.");
            }
    
            $this->_aliasMap[$componentAlias]['map'] = $table->getColumnName($indexBy);
        }
        return $this->_aliasMap[$componentAlias];
    }

    /**
     * loadRoot
     *
     * @param string $name
     * @param string $componentAlias
     * @todo DESCRIBE ME!
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
            $queryPart .= ' ' . $this->_conn->quoteIdentifier($tableAlias);
        }
        
        $this->tableAliases[$tableAlias] = $componentAlias;

        $queryPart .= $this->buildInheritanceJoinSql($name, $componentAlias);

        $this->parts['from'][] = $queryPart;

        $this->_aliasMap[$componentAlias] = array('table' => $table, 'map' => null);

        return $table;
    }
    
    /**
     * @todo DESCRIBE ME!
     */
    public function buildInheritanceJoinSql($name, $componentAlias)
    {
        // get the connection for the component
        $this->_conn = Doctrine_Manager::getInstance()->getConnectionForComponent($name);

        $table = $this->_conn->getTable($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->getTableAlias($componentAlias, $tableName);
        
        $queryPart = '';

        foreach ($table->getOption('joinedParents') as $parent) {
        	$parentTable = $this->_conn->getTable($parent);

            $parentAlias = $componentAlias . '.' . $parent;

            // get the short alias for the parent table
            $parentTableAlias = $this->getTableAlias($parentAlias, $parentTable->getTableName());

            $queryPart .= ' LEFT JOIN ' . $this->_conn->quoteIdentifier($parentTable->getTableName())
                        . ' ' . $this->_conn->quoteIdentifier($parentTableAlias) . ' ON ';
            
            //Doctrine::dump($table->getIdentifier());
            foreach ((array) $table->getIdentifier() as $identifier) {
                $column = $table->getColumnName($identifier);

                $queryPart .= $this->_conn->quoteIdentifier($tableAlias) 
                            . '.' . $this->_conn->quoteIdentifier($column)
                            . ' = ' . $this->_conn->quoteIdentifier($parentTableAlias)
                            . '.' . $this->_conn->quoteIdentifier($column);
            }
        }
        
        return $queryPart;
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
        $this->getQuery();

        // initialize temporary variables
        $where  = $this->parts['where'];
        $having = $this->parts['having'];
        $groupby = $this->parts['groupby'];
        $map    = reset($this->_aliasMap);
        $componentAlias = key($this->_aliasMap);
        $table = $map['table'];

        // build the query base
        $q  = 'SELECT COUNT(DISTINCT ' . $this->getTableAlias($componentAlias)
              . '.' . implode(',', (array) $table->getIdentifier())
              . ') AS num_results';

        foreach ($this->parts['select'] as $field) {
            if (strpos($field, '(') !== false) {
                $q .= ', ' . $field;
            }
        }

        $q .= ' FROM ' . $this->buildFromPart();

        // append column aggregation inheritance (if needed)
        $string = $this->applyInheritance();

        if ( ! empty($string)) {
            $where[] = $string;
        }
        // append conditions
        $q .= ( ! empty($where)) ?  ' WHERE '  . implode(' AND ', $where) : '';
        $q .= ( ! empty($groupby)) ?  ' GROUP BY '  . implode(', ', $groupby) : '';
        $q .= ( ! empty($having)) ? ' HAVING ' . implode(' AND ', $having): '';

        if ( ! is_array($params)) {
            $params = array($params);
        }
        // append parameters
        $params = array_merge($this->_params['where'], $this->_params['having'], $params);

        $params = $this->convertEnums($params);

        $results = $this->getConnection()->fetchAll($q, $params);

        if (count($results) > 1) {
            $count = 0;
            foreach ($results as $result) {
                $count += $result['num_results'];
            }
        } else {
            $count = isset($results[0]) ? $results[0]['num_results']:0;
        }

        return (int) $count;
    }

    /**
     * query
     * query the database with DQL (Doctrine Query Language)
     *
     * @param string $query      DQL query
     * @param array $params      prepared statement parameters
     * @param int $hydrationMode Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
     * @see Doctrine::FETCH_* constants
     * @return mixed
     */
    public function query($query, $params = array(), $hydrationMode = null)
    {
        $this->parseQuery($query);

        return $this->execute($params, $hydrationMode);
    }

    /**
     * Copies a Doctrine_Query object.
     *
     * @param Doctrine_Query   Doctrine query instance.
     *                         If ommited the instance itself will be used as source.
     * @return Doctrine_Query  Copy of the Doctrine_Query instance.
     */
    public function copy(Doctrine_Query $query = null)
    {
        if ( ! $query) {
            $query = $this;
        }

        $new = new Doctrine_Query();
        $new->_dqlParts = $query->_dqlParts;
        $new->_params = $query->_params;
        $new->_hydrator = $query->_hydrator;

        return $new;
    }

    /**
     * Frees the resources used by the query object. It especially breaks a 
     * cyclic reference between the query object and it's parsers. This enables
     * PHP's current GC to reclaim the memory.
     * This method can therefore be used to reduce memory usage when creating a lot
     * of query objects during a request.
     *
     * @return Doctrine_Query   this object
     */
    public function free()
    {
        $this->reset();
        $this->_parsers = array();
        $this->_dqlParts = array();
        $this->_enumParams = array();
    }
    
    /**
     * serialize
     * this method is automatically called when this Doctrine_Hydrate is serialized
     *
     * @return array    an array of serialized properties
     */
    public function serialize()
    {
        $vars = get_object_vars($this);
    }

    /**
     * unseralize
     * this method is automatically called everytime a Doctrine_Hydrate object is unserialized
     *
     * @param string $serialized                Doctrine_Record as serialized string
     * @return void
     */
    public function unserialize($serialized)
    {

    }
}
