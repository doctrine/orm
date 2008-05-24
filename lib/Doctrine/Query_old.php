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
     * @var array
     */
    protected $_subqueryAliases = array();

    /**
     * @var array $_aggregateAliasMap       an array containing all aggregate aliases, keys as dql aliases
     *                                      and values as sql aliases
     */
    protected $_aggregateAliasMap      = array();

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
     * @var array
     */
    protected $_expressionMap = array();

    /**
     * @var string $_sql            cached SQL query
     */
    protected $_sql;

    protected $_dql;


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
        if ($this->_dql !== null) {
            return $this->_dql;
        }

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
        if ($this->_state === self::STATE_DIRTY) {
            $this->parse();
        }

        return $this->_sql;
    }

    public function parse()
    {
        $this->reset();

        // invoke the preQuery hook
        $this->preQuery();

        $parser = new Doctrine_Query_Parser($this);
        $parser->parse();

        if ($parser->isErrors()) {
            throw new Doctrine_Query_Parser_Exception(
                "Errors were detected during query parsing:\n" .
                implode("\n", $parser->getErrors())
            );
        }

        $this->_state = self::STATE_CLEAN;
        $this->_sql = $parser->getSql();
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

    public function setDql($query)
    {
        $this->_dql = $query;
        $this->_state = self::STATE_DIRTY;
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
        $this->setDql($query);
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
