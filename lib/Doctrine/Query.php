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
 * <http://www.phpdoctrine.org>.
 */
Doctrine::autoload('Doctrine_Query_Abstract');
/**
 * Doctrine_Query
 * A Doctrine_Query object represents a DQL query. It is used to query databases for
 * data in an object-oriented fashion. A DQL query understands relations and inheritance
 * and is dbms independant.
 *
 * @package     Doctrine
 * @subpackage  Query
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @todo        Proposal: This class does far too much. It should have only 1 task: Collecting
 *              the DQL query parts and the query parameters (the query state and caching options/methods
 *              can remain here, too).
 *              The actual SQL construction could be done by a separate object (Doctrine_Query_SqlBuilder?)
 *              whose task it is to convert DQL into SQL.
 *              Furthermore the SqlBuilder? can then use other objects (Doctrine_Query_Tokenizer?),
 *              (Doctrine_Query_Parser(s)?) to accomplish his work. Doctrine_Query does not need
 *              to know the tokenizer/parsers. There could be extending
 *              implementations of SqlBuilder? that cover the specific SQL dialects.
 *              This would release Doctrine_Connection and the Doctrine_Connection_xxx classes
 *              from this tedious task.
 *              This would also largely reduce the currently huge interface of Doctrine_Query(_Abstract)
 *              and better hide all these transformation internals from the public Query API.
 *
 * @internal    The lifecycle of a Query object is the following:
 *              After construction the query object is empty. Through using the fluent
 *              query interface the user fills the query object with DQL parts and query parameters.
 *              These get collected in {@link $_dqlParts} and {@link $_params}, respectively.
 *              When the query is executed the first time, or when {@link getSqlQuery()}
 *              is called the first time, the collected DQL parts get parsed and the resulting
 *              connection-driver specific SQL is generated. The generated SQL parts are
 *              stored in {@link $_sqlParts} and the final resulting SQL query is stored in
 *              {@link $_sql}.
 */
class Doctrine_Query extends Doctrine_Query_Abstract implements Countable, Serializable
{
    /**
     * @var array  The DQL keywords.
     */
    protected static $_keywords  = array('ALL', 
                                         'AND', 
                                         'ANY', 
                                         'AS', 
                                         'ASC', 
                                         'AVG', 
                                         'BETWEEN', 
                                         'BIT_LENGTH', 
                                         'BY', 
                                         'CHARACTER_LENGTH', 
                                         'CHAR_LENGTH', 
                                         'CURRENT_DATE',
                                         'CURRENT_TIME', 
                                         'CURRENT_TIMESTAMP', 
                                         'DELETE', 
                                         'DESC', 
                                         'DISTINCT', 
                                         'EMPTY', 
                                         'EXISTS', 
                                         'FALSE', 
                                         'FETCH', 
                                         'FROM', 
                                         'GROUP', 
                                         'HAVING', 
                                         'IN', 
                                         'INDEXBY', 
                                         'INNER', 
                                         'IS', 
                                         'JOIN',
                                         'LEFT', 
                                         'LIKE', 
                                         'LOWER',
                                         'MEMBER',
                                         'MOD',
                                         'NEW', 
                                         'NOT', 
                                         'NULL', 
                                         'OBJECT', 
                                         'OF', 
                                         'OR', 
                                         'ORDER', 
                                         'OUTER', 
                                         'POSITION', 
                                         'SELECT', 
                                         'SOME',
                                         'TRIM', 
                                         'TRUE', 
                                         'UNKNOWN', 
                                         'UPDATE', 
                                         'WHERE');
    
    /**
     * @var array
     */
    protected $_subqueryAliases = array();
    
    /**
     * @var array $_aggregateAliasMap       an array containing all aggregate aliases, keys as dql aliases
     *                                      and values as sql aliases
     */
    protected $_aggregateAliasMap      = array();
    
    /**
     * @var array
     */
    protected $_pendingAggregates = array();

    /**
     * @param boolean $needsSubquery
     */
    protected $_needsSubquery = false;

    /**
     * @param boolean $isSubquery           whether or not this query object is a subquery of another 
     *                                      query object
     */
    protected $_isSubquery;

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
     * @var array $_pendingJoinConditions    an array containing pending joins
     */
    protected $_pendingJoinConditions = array();
    
    /**
     * @var array
     */
    protected $_expressionMap = array();
    
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
        $this->_subqueryAliases = array();
        $this->_needsSubquery = false;
        $this->_isLimitSubqueryUsed = false;
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
     * _addPendingJoinCondition
     *
     * @param string $componentAlias    component alias
     * @param string $joinCondition     dql join condition
     * @return Doctrine_Query           this object
     */
    protected function _addPendingJoinCondition($componentAlias, $joinCondition)
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
     * getParams
     *
     * @return array
     */
    public function getParams()
    {
        return array_merge($this->_params['join'], $this->_params['set'], $this->_params['where'], $this->_params['having']);
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
            return $this->_isSubquery;
        }

        $this->_isSubquery = (bool) $bool;
        return $this;
    }

    /**
     * getAggregateAlias
     *
     * @param string $dqlAlias      the dql alias of an aggregate value
     * @return string
     * @deprecated
     */
    public function getAggregateAlias($dqlAlias)
    {
        return $this->getSqlAggregateAlias($dqlAlias);
    }
    
    /**
     * getSqlAggregateAlias
     *
     * @param string $dqlAlias      the dql alias of an aggregate value
     * @return string
     */
    public function getSqlAggregateAlias($dqlAlias)
    {
        if (isset($this->_aggregateAliasMap[$dqlAlias])) {
            // mark the expression as used
            $this->_expressionMap[$dqlAlias][1] = true;

            return $this->_aggregateAliasMap[$dqlAlias];
        } else if ( ! empty($this->_pendingAggregates)) {
            $this->processPendingAggregates();

            return $this->getSqlAggregateAlias($dqlAlias);
        } else {
            throw new Doctrine_Query_Exception('Unknown aggregate alias: ' . $dqlAlias);
        }
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
     * contains
     *
     * Method to check if a arbitrary piece of dql exists
     *
     * @param string $dql Arbitrary piece of dql to check for
     * @return boolean
     */
    public function contains($dql)
    {
      return stripos($this->getDql(), $dql) === false ? false : true;
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
        $tableAlias = $this->getSqlTableAlias($componentAlias);
        $baseTable = $this->_queryComponents[$componentAlias]['table'];
        $mapper = $this->_queryComponents[$componentAlias]['mapper'];

        if ( ! isset($this->_pendingFields[$componentAlias])) {
            return;
        }

        $fields = $this->_pendingFields[$componentAlias];

        // check for wildcards
        if (in_array('*', $fields)) {
            $fields = $mapper->getFieldNames();
        } else {
            // only auto-add the primary key fields if this query object is not
            // a subquery of another query object
            if ( ! $this->_isSubquery) {
                $fields = array_unique(array_merge((array) $baseTable->getIdentifier(), $fields));
            }
        }
        
        $fields = array_unique(array_merge($fields, $mapper->getCustomFields()));
        
        $sql = array();
        foreach ($fields as $fieldName) {
            $table = $mapper->getOwningTable($fieldName);
            if ($table !== $baseTable) {
                $tableAlias = $this->getSqlTableAlias($componentAlias . '.' . $table->getComponentName());
            } else {
                $tableAlias = $this->getSqlTableAlias($componentAlias);
            }
            
            $columnName = $table->getColumnName($fieldName);
            $columnName = $table->getColumnName($fieldName);
            $sql[] = $this->_conn->quoteIdentifier($tableAlias . '.' . $columnName)
                   . ' AS '
                   . $this->_conn->quoteIdentifier($this->getSqlTableAlias($componentAlias) . '__' . $columnName);

            if ( ! in_array($tableAlias, $this->_neededTables)) {
                $this->_neededTables[] = $tableAlias;
            }
        }
        
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
            reset($this->_queryComponents);
            $componentAlias = key($this->_queryComponents);
            $fields = $terms[0];
        }

        $tableAlias = $this->getTableAlias($componentAlias);
        $table      = $this->_queryComponents[$componentAlias]['table'];


        // check for wildcards
        if ($field === '*') {
            $sql = array();

            foreach ($table->getColumnNames() as $field) {
                $sql[] = $this->parseSelectField($componentAlias . '.' . $field);
            }

            return implode(', ', $sql);
        } else {
            $name = $table->getColumnName($field);
    
            $this->_neededTables[] = $tableAlias;
    
            return $this->_conn->quoteIdentifier($tableAlias . '.' . $name)
                   . ' AS '
                   . $this->_conn->quoteIdentifier($tableAlias . '__' . $name);
        }
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
        $refs = $this->_tokenizer->sqlExplode($dql, ',');

        $pos   = strpos(trim($refs[0]), ' ');
        $first = substr($refs[0], 0, $pos);

        // check for DISTINCT keyword
        if ($first === 'DISTINCT') {
            $this->_sqlParts['distinct'] = true;
            $refs[0] = substr($refs[0], ++$pos);
        }

        $parsedComponents = array();

        foreach ($refs as $reference) {
            $reference = trim($reference);

            if (empty($reference)) {
                continue;
            }

            $terms = $this->_tokenizer->sqlExplode($reference, ' ');

            $pos = strpos($terms[0], '(');

            if (count($terms) > 1 || $pos !== false) {
                $expression = array_shift($terms);
                $alias = array_pop($terms);

                if ( ! $alias) {
                    $alias = substr($expression, 0, $pos);
                }

                $componentAlias = $this->getExpressionOwner($expression);
                $expression = $this->parseClause($expression);

                $tableAlias = $this->getTableAlias($componentAlias);

                $index = count($this->_aggregateAliasMap);

                $sqlAlias = $this->_conn->quoteIdentifier($tableAlias . '__' . $index);

                $this->_sqlParts['select'][] = $expression . ' AS ' . $sqlAlias;

                $this->_aggregateAliasMap[$alias] = $sqlAlias;
                $this->_expressionMap[$alias][0] = $expression;

                $this->_queryComponents[$componentAlias]['agg'][$index] = $alias;

                $this->_neededTables[] = $tableAlias;
            } else {
                $e = explode('.', $terms[0]);

                if (isset($e[1])) {
                    $componentAlias = $e[0];
                    $field = $e[1];
                } else {
                    reset($this->_queryComponents);
                    $componentAlias = key($this->_queryComponents);
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
     * 3. Converts all field names to actual column names
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

        $terms = $this->_tokenizer->clauseExplode($clause, array(' ', '+', '-', '*', '/'));

        $str = '';
        foreach ($terms as $term) {
            $pos = strpos($term[0], '(');

            if ($pos !== false) {
                $name = substr($term[0], 0, $pos);
                $term[0] = $this->parseFunctionExpression($term[0]);
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
                                if ( ! isset($this->_queryComponents[$componentAlias])) {
                                    throw new Doctrine_Query_Exception('Unknown component alias ' . $componentAlias);
                                }

                                $table = $this->_queryComponents[$componentAlias]['table'];

                                $def = $table->getDefinitionOf($field);

                                // get the actual field name from alias
                                $field = $table->getColumnName($field);

                                // check column existence
                                if ( ! $def) {
                                    throw new Doctrine_Query_Exception('Unknown column ' . $field);
                                }
                                
                                if (isset($def['owner'])) {
                                    $componentAlias = $componentAlias . '.' . $def['owner'];
                                }

                                $tableAlias = $this->getTableAlias($componentAlias);

                                // build sql expression
                                $term[0] = $this->_conn->quoteIdentifier($tableAlias)
                                         . '.'
                                         . $this->_conn->quoteIdentifier($field);
                            } else {
                                // build sql expression
                                $field = $this->getRoot()->getColumnName($field);
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
                                $table = $this->_queryComponents[$componentAlias]['table'];

                                // check column existence
                                if ($table->hasField($term[0])) {
                                    $found = true;

                                    $def = $table->getDefinitionOf($term[0]);

                                    // get the actual column name from field name
                                    $term[0] = $table->getColumnName($term[0]);


                                    if (isset($def['owner'])) {
                                        $componentAlias = $componentAlias . '.' . $def['owner'];
                                    }

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
                                $term[0] = $this->getSqlAggregateAlias($term[0]);
                            }
                        }
                    }
                }
            }

            $str .= $term[0] . $term[1];
        }
        return $str;
    }
    
    public function parseIdentifierReference($expr)
    {
    	
    }

    public function parseFunctionExpression($expr)
    {
        $pos = strpos($expr, '(');
        
        $name = substr($expr, 0, $pos);

        if ($name === '') {
            return $this->parseSubquery($expr);
        }

        $argStr = substr($expr, ($pos + 1), -1);

        $args   = array();
        // parse args

        foreach ($this->_tokenizer->sqlExplode($argStr, ',') as $arg) {
           $args[] = $this->parseClause($arg);
        }

        // convert DQL function to its RDBMS specific equivalent
        try {
            $expr = call_user_func_array(array($this->_conn->expression, $name), $args);
        } catch (Doctrine_Expression_Exception $e) {
            throw new Doctrine_Query_Exception('Unknown function ' . $name . '.');
        }
        
        return $expr;
    }
    public function parseSubquery($subquery)
    {
        $trimmed = trim($this->_tokenizer->bracketTrim($subquery));

        // check for possible subqueries
        if (substr($trimmed, 0, 4) == 'FROM' || substr($trimmed, 0, 6) == 'SELECT') {
            // parse subquery
            $trimmed = $this->createSubquery()->parseDqlQuery($trimmed)->getQuery();
        } else {
            // parse normal clause
            $trimmed = $this->parseClause($trimmed);
        }

        return '(' . $trimmed . ')';
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

            $sql = $subquery->parseDqlQuery($dql, false)->getQuery();

            reset($this->_queryComponents);
            $componentAlias = key($this->_queryComponents);
            $tableAlias = $this->getTableAlias($componentAlias);

            $sqlAlias = $tableAlias . '__' . count($this->_aggregateAliasMap);

            $this->_sqlParts['select'][] = '(' . $sql . ') AS ' . $this->_conn->quoteIdentifier($sqlAlias);

            $this->_aggregateAliasMap[$alias] = $sqlAlias;
            $this->_queryComponents[$componentAlias]['agg'][] = $alias;
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
        foreach ($this->_pendingAggregates as $aggregate) {
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
                    if ( ! isset($this->_queryComponents[$componentAlias])) {
                        throw new Doctrine_Query_Exception('Unknown component alias ' . $componentAlias);
                    }

                    $table = $this->_queryComponents[$componentAlias]['table'];

                    $field = $table->getColumnName($field);

                    // check column existence
                    if ( ! $table->hasColumn($field)) {
                        throw new Doctrine_Query_Exception('Unknown column ' . $field);
                    }

                    $sqlTableAlias = $this->getSqlTableAlias($componentAlias);

                    $tableAliases[$sqlTableAlias] = true;

                    // build sql expression

                    $identifier = $this->_conn->quoteIdentifier($sqlTableAlias . '.' . $field);
                    $expression = str_replace($component, $identifier, $expression);
                }
            }

            if (count($tableAliases) !== 1) {
                $componentAlias = reset($this->_tableAliasMap);
                $tableAlias = key($this->_tableAliasMap);
            }

            $index    = count($this->_aggregateAliasMap);
            $sqlAlias = $this->_conn->quoteIdentifier($tableAlias . '__' . $index);

            $this->_sqlParts['select'][] = $expression . ' AS ' . $sqlAlias;

            $this->_aggregateAliasMap[$alias] = $sqlAlias;
            $this->_expressionMap[$alias][0] = $expression;

            $this->_queryComponents[$componentAlias]['agg'][$index] = $alias;

            $this->_neededTables[] = $tableAlias;
        }
        // reset the state
        $this->_pendingAggregates = array();
    }

    /**
     * _buildSqlQueryBase
     * returns the base of the generated sql query
     * On mysql driver special strategy has to be used for DELETE statements
     * (where is this special strategy??)
     *
     * @return string       the base of the generated sql query
     */
    protected function _buildSqlQueryBase()
    {
        switch ($this->_type) {
            case self::DELETE:
                $q = 'DELETE FROM ';
            break;
            case self::UPDATE:
                $q = 'UPDATE ';
            break;
            case self::SELECT:
                $distinct = ($this->_sqlParts['distinct']) ? 'DISTINCT ' : '';
                $q = 'SELECT ' . $distinct . implode(', ', $this->_sqlParts['select']) . ' FROM ';
            break;
        }
        return $q;
    }

    /**
     * _buildSqlFromPart
     * builds the from part of the query and returns it
     *
     * @return string   the query sql from part
     */
    protected function _buildSqlFromPart()
    {
        $q = '';
        foreach ($this->_sqlParts['from'] as $k => $part) {
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

                    $aliases = array_merge($this->_subqueryAliases,
                                array_keys($this->_neededTables));

                    if ( ! in_array($e[3], $aliases) &&
                        ! in_array($e[2], $aliases) &&

                        ! empty($this->_pendingFields)) {
                        continue;
                    }

                }

                if (isset($this->_pendingJoinConditions[$k])) {
                    $parser = new Doctrine_Query_JoinCondition($this, $this->_tokenizer);

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

            $this->_sqlParts['from'][$k] = $part;
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
     * builds the sql query from the given parameters and applies things such as
     * column aggregation inheritance and limit subqueries if needed
     *
     * @param array $params             an array of prepared statement params (needed only in mysql driver
     *                                  when limit subquery algorithm is used)
     * @return string                   the built sql query
     */
    public function getSqlQuery($params = array())
    {
        if ($this->_state !== self::STATE_DIRTY) {
           return $this->_sql;
        }

        // reset the state
        if ( ! $this->isSubquery()) {
            $this->_queryComponents = array();
            $this->_pendingAggregates = array();
            $this->_aggregateAliasMap = array();
        }
        $this->reset();

        // invoke the preQuery hook
        $this->preQuery();

        // process the DQL parts => generate the SQL parts.
        // this will also populate the $_queryComponents.
        foreach ($this->_dqlParts as $queryPartName => $queryParts) {
            $this->_processDqlQueryPart($queryPartName, $queryParts);
        }
        $this->_state = self::STATE_CLEAN;

        $params = $this->convertEnums($params);

        // Proceed with the generated SQL

        if (empty($this->_sqlParts['from'])) {
            return false;
        }

        $needsSubQuery = false;
        $subquery = '';
        $map = reset($this->_queryComponents);
        $table = $map['table'];
        $rootAlias = key($this->_queryComponents);

        if ( ! empty($this->_sqlParts['limit']) && $this->_needsSubquery &&
                $table->getAttribute(Doctrine::ATTR_QUERY_LIMIT) == Doctrine::LIMIT_RECORDS) {
            $this->_isLimitSubqueryUsed = true;
            $needsSubQuery = true;
        }

        $sql = array();
        foreach ($this->_queryComponents as $alias => $map) {
            $fieldSql = $this->processPendingFields($alias);
            if ( ! empty($fieldSql)) {
                $sql[] = $fieldSql;
            }
        }
        if ( ! empty($sql)) {
            array_unshift($this->_sqlParts['select'], implode(', ', $sql));
        }

        $this->_pendingFields = array();

        // build the basic query
        $q  = $this->_buildSqlQueryBase();
        $q .= $this->_buildSqlFromPart();

        if ( ! empty($this->_sqlParts['set'])) {
            $q .= ' SET ' . implode(', ', $this->_sqlParts['set']);
        }

        // append discriminator column conditions (if any)
        $string = $this->_createDiscriminatorConditionSql();
        //echo "orig:$string<br /><br />";
        if ( ! empty($string)) {
            if (substr($string, 0, 1) === '(' && substr($string, -1) === ')') {
                $this->_sqlParts['where'][] = $string;
            } else {
                $this->_sqlParts['where'][] = '(' . $string . ')';
            }
        }

        $modifyLimit = true;
        if ( ! empty($this->_sqlParts['limit']) || ! empty($this->_sqlParts['offset'])) {

            if ($needsSubQuery) {
                $subquery = $this->getLimitSubquery();
                // what about composite keys?
                $idColumnName = $table->getColumnName($table->getIdentifier());
                switch (strtolower($this->_conn->getDriverName())) {
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

                $field = $this->getSqlTableAlias($rootAlias) . '.' . $idColumnName;

                // only append the subquery if it actually contains something
                if ($subquery !== '') {
                    array_unshift($this->_sqlParts['where'], $this->_conn->quoteIdentifier($field) . ' IN (' . $subquery . ')');
                }

                $modifyLimit = false;
            }
        }

        $q .= ( ! empty($this->_sqlParts['where']))?   ' WHERE '    . implode(' AND ', $this->_sqlParts['where']) : '';
        $q .= ( ! empty($this->_sqlParts['groupby']))? ' GROUP BY ' . implode(', ', $this->_sqlParts['groupby'])  : '';
        $q .= ( ! empty($this->_sqlParts['having']))?  ' HAVING '   . implode(' AND ', $this->_sqlParts['having']): '';
        $q .= ( ! empty($this->_sqlParts['orderby']))? ' ORDER BY ' . implode(', ', $this->_sqlParts['orderby'])  : '';

        if ($modifyLimit) {
            $q = $this->_conn->modifyLimitQuery($q, $this->_sqlParts['limit'], $this->_sqlParts['offset']);
        }

        // return to the previous state
        if ( ! empty($string)) {
            array_pop($this->_sqlParts['where']);
        }
        if ($needsSubQuery) {
            array_shift($this->_sqlParts['where']);
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
        $map    = reset($this->_queryComponents);
        $table  = $map['table'];
        $componentAlias = key($this->_queryComponents);

        // get short alias
        $alias      = $this->getTableAlias($componentAlias);
        // what about composite keys?
        $primaryKey = $alias . '.' . $table->getColumnName($table->getIdentifier());

        // initialize the base of the subquery
        $subquery   = 'SELECT DISTINCT ' . $this->_conn->quoteIdentifier($primaryKey);

        $driverName = $this->_conn->getAttribute(Doctrine::ATTR_DRIVER_NAME);

        // pgsql needs the order by fields to be preserved in select clause
        if ($driverName == 'pgsql') {
            foreach ($this->_sqlParts['orderby'] as $part) {
                $part = trim($part);
                $e = $this->_tokenizer->bracketExplode($part, ' ');
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
                    $subquery .= ', ' . $expr[0] . ' AS ' . $this->_aggregateAliasMap[$dqlAlias];
                }
            }
        }

        $subquery .= ' FROM';

        foreach ($this->_sqlParts['from'] as $part) {
            // preserve LEFT JOINs only if needed
            if (substr($part, 0, 9) === 'LEFT JOIN') {
                $e = explode(' ', $part);

                if (empty($this->_sqlParts['orderby']) && empty($this->_sqlParts['where'])) {
                    continue;
                }
            }

            $subquery .= ' ' . $part;
        }

        // all conditions must be preserved in subquery
        $subquery .= ( ! empty($this->_sqlParts['where']))?   ' WHERE '    . implode(' AND ', $this->_sqlParts['where'])  : '';
        $subquery .= ( ! empty($this->_sqlParts['groupby']))? ' GROUP BY ' . implode(', ', $this->_sqlParts['groupby'])   : '';
        $subquery .= ( ! empty($this->_sqlParts['having']))?  ' HAVING '   . implode(' AND ', $this->_sqlParts['having']) : '';

        $subquery .= ( ! empty($this->_sqlParts['orderby']))? ' ORDER BY ' . implode(', ', $this->_sqlParts['orderby'])   : '';

        // add driver specific limit clause
        $subquery = $this->_conn->modifyLimitQuery($subquery, $this->_sqlParts['limit'], $this->_sqlParts['offset']);

        $parts = $this->_tokenizer->quoteExplode($subquery, ' ', "'", "'");

        foreach ($parts as $k => $part) {
            if (strpos($part, ' ') !== false) {
                continue;
            }

            $part = trim($part, "\"'`");

            if ($this->hasSqlTableAlias($part)) {
                $parts[$k] = $this->_conn->quoteIdentifier($this->generateNewSqlTableAlias($part));
                continue;
            }

            if (strpos($part, '.') === false) {
                continue;
            }
            preg_match_all("/[a-zA-Z0-9_]+\.[a-z0-9_]+/i", $part, $m);

            foreach ($m[0] as $match) {
                $e = explode('.', $match);
                $e[0] = $this->generateNewSqlTableAlias($e[0]);

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
    public function parseDqlQuery($query, $clear = true)
    {
        if ($clear) {
            $this->clear();
        }

        $query = trim($query);
        $query = str_replace("\n", ' ', $query);
        $query = str_replace("\r", ' ', $query);

        $parts = $this->_tokenizer->tokenizeQuery($query);

        foreach ($parts as $partName => $subParts) {
            $subParts = trim($subParts);
            $partName = strtolower($partName);
            switch ($partName) {
                case 'create':
                    $this->_type = self::CREATE;
                break;
                case 'insert':
                    $this->_type = self::INSERT;
                break;
                case 'delete':
                    $this->_type = self::DELETE;
                break;
                case 'select':
                    $this->_type = self::SELECT;
                    $this->_addDqlQueryPart($partName, $subParts);
                break;
                case 'update':
                    $this->_type = self::UPDATE;
                    $partName = 'from';
                case 'from':
                    $this->_addDqlQueryPart($partName, $subParts);
                break;
                case 'set':
                    $this->_addDqlQueryPart($partName, $subParts, true);
                break;
                case 'group':
                case 'order':
                    $partName .= 'by';
                case 'where':
                case 'having':
                case 'limit':
                case 'offset':
                    $this->_addDqlQueryPart($partName, $subParts);
                break;
            }
        }

        return $this;
    }
    
    /**
     * @todo Describe & refactor... too long and nested.
     */
    public function load($path, $loadFields = true)
    {
    	if (isset($this->_queryComponents[$path])) {
    	   return $this->_queryComponents[$path];
    	}
        $e = $this->_tokenizer->quoteExplode($path, ' INDEXBY ');

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

        if (isset($this->_queryComponents[$e[0]])) {
            $table = $this->_queryComponents[$e[0]]['table'];
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

            // if the current alias already exists, it's user error
            if (isset($this->_queryComponents[$componentAlias])) {
                throw new Doctrine_Query_Exception("Duplicate alias '$componentAlias' in query.");
            }

            if ( ! isset($table)) {
                // process the root of the path
                $table = $this->loadRoot($name, $componentAlias);
            } else {
                $join = ($delimeter == ':') ? 'INNER JOIN ' : 'LEFT JOIN ';
                $relation = $table->getRelation($name);
                $localTable = $table;

                $table = $relation->getTable();
                //echo "<br /><br />" . $table->getComponentName() . "------" . $relation->getForeignComponentName() . "<br /><br />";                
                $this->_queryComponents[$componentAlias] = array(
                        'table'    => $table,
                        'mapper'   => $this->_conn->getMapper($relation->getForeignComponentName()),
                        'parent'   => $parent,
                        'relation' => $relation,
                        'map'      => null
                );
                if ( ! $relation->isOneToOne()) {
                   $this->_needsSubquery = true;
                }

                $localAlias   = $this->getTableAlias($parent, $table->getTableName());
                $foreignAlias = $this->getTableAlias($componentAlias, $relation->getTable()->getTableName());
                $localSql     = $this->_conn->quoteIdentifier($table->getTableName())
                              . ' '
                              . $this->_conn->quoteIdentifier($localAlias);

                $foreignSql   = $this->_conn->quoteIdentifier($relation->getTable()->getTableName())
                              . ' '
                              . $this->_conn->quoteIdentifier($foreignAlias);

                $map = $relation->getTable()->getOption('inheritanceMap');

                if ( ! $loadFields || ! empty($map) || $joinCondition) {
                    $this->_subqueryAliases[] = $foreignAlias;
                }

                if ($relation instanceof Doctrine_Relation_Association) {
                    $asf = $relation->getAssociationTable();

                    $assocTableName = $asf->getTableName();
                    
                    if ( ! $loadFields || ! empty($map) || $joinCondition) {
                        $this->_subqueryAliases[] = $assocTableName;
                    }

                    $assocPath = $prevPath . '.' . $asf->getComponentName();
                    //echo "<br /><br />" . $asf->getComponentName() . "---2---" . $relation->getForeignComponentName() . "<br /><br />";
                    $this->_queryComponents[$assocPath] = array(
                            'parent' => $prevPath,
                            'relation' => $relation,
                            'table' => $asf,
                            'mapper' => $this->_conn->getMapper($relation->getAssociationClassName())
                    );

                    $assocAlias = $this->getTableAlias($assocPath, $asf->getTableName());

                    $queryPart = $join . $assocTableName . ' ' . $assocAlias;

                    $queryPart .= ' ON ' . $localAlias
                                . '.'
                                . $localTable->getColumnName($localTable->getIdentifier()) // what about composite keys?
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

                    $this->_sqlParts['from'][] = $queryPart;

                    $queryPart = $join . $foreignSql;

                    if ( ! $overrideJoin) {
                        $queryPart .= ' ON ';

                        if ($relation->isEqual()) {
                            $queryPart .= '(';
                        }
                        
                        $relationTable = $relation->getTable();
                        $queryPart .= $this->_conn->quoteIdentifier($foreignAlias . '.' . $relationTable->getColumnName($relationTable->getIdentifier()))
                                    . ' = '
                                    . $this->_conn->quoteIdentifier($assocAlias . '.' . $relation->getForeign());

                        if ($relation->isEqual()) {
                            $queryPart .= ' OR '
                                        . $this->_conn->quoteIdentifier($foreignAlias . '.' . $table->getColumnName($table->getIdentifier()))
                                        . ' = ' 
                                        . $this->_conn->quoteIdentifier($assocAlias . '.' . $relation->getLocal())
                                        . ') AND ' 
                                        . $this->_conn->quoteIdentifier($foreignAlias . '.' . $table->getColumnName($table->getIdentifier()))
                                        . ' != '  
                                        . $this->_conn->quoteIdentifier($localAlias . '.' . $table->getColumnName($table->getIdentifier()));
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
                
                $queryPart .= $this->_createCustomJoinSql($table->getComponentName(), $componentAlias);

                $this->_sqlParts['from'][$componentAlias] = $queryPart;
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
        
        $table = $this->_queryComponents[$componentAlias]['table'];
        $mapper = $this->_queryComponents[$componentAlias]['mapper'];

        $indexBy = null;

        if (isset($mapWith)) {
            $e = explode('.', $mapWith);

            if (isset($e[1])) {
                $indexBy = $e[1];
            }
        } else if ($table->getBoundQueryPart('indexBy') !== null) {
            $indexBy = $table->getBoundQueryPart('indexBy');
        }

        if ($indexBy !== null) {
            if ( ! $table->hasField($indexBy)) {
                throw new Doctrine_Query_Exception("Couldn't use key mapping. Column " . $indexBy . " does not exist.");
            }
    
            $this->_queryComponents[$componentAlias]['map'] = $indexBy;
        }
        return $this->_queryComponents[$componentAlias];
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
        $manager = Doctrine_Manager::getInstance();
        if ($manager->hasConnectionForComponent($name)) {
            $this->_conn = $manager->getConnectionForComponent($name);
        }

        $table = $this->_conn->getMetadata($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->getTableAlias($componentAlias, $tableName);
        // quote table name
        $queryPart = $this->_conn->quoteIdentifier($tableName);

        if ($this->_type === self::SELECT) {
            $queryPart .= ' ' . $this->_conn->quoteIdentifier($tableAlias);
        }
        
        $this->_tableAliasMap[$tableAlias] = $componentAlias;

        $queryPart .= $this->_createCustomJoinSql($name, $componentAlias);

        $this->_sqlParts['from'][] = $queryPart;
        //echo "<br /><br />" . $table->getComponentName() . "---3---" . $name . "<br /><br />";
        $this->_queryComponents[$componentAlias] = array(
                'table' => $table, 'mapper' => $this->_conn->getMapper($name), 'map' => null);

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
        // triggers dql parsing/processing
        $this->getQuery(); // this is ugly

        // initialize temporary variables
        $where  = $this->_sqlParts['where'];
        $having = $this->_sqlParts['having'];
        $groupby = $this->_sqlParts['groupby'];
        $map = reset($this->_queryComponents);
        $componentAlias = key($this->_queryComponents);
        $table = $map['table'];

        // build the query base
        $q  = 'SELECT COUNT(DISTINCT ' . $this->getTableAlias($componentAlias)
              . '.' . implode(',', $table->getIdentifierColumnNames())
              . ') AS num_results';

        foreach ($this->_sqlParts['select'] as $field) {
            if (strpos($field, '(') !== false) {
                $q .= ', ' . $field;
            }
        }

        $q .= ' FROM ' . $this->_buildSqlFromPart();

        // append discriminator column conditions (if any)
        $string = $this->_createDiscriminatorConditionSql();
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
        $this->parseDqlQuery($query);
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
