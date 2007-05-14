<?php
/*
 *  $Id: Query.php 1296 2007-04-26 17:42:03Z zYne $
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

/**
 * Doctrine_Query_Parser
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1296 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_Parser 
{
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
                $part  .= ' AND ' . $parser->parse(implode(' AND ', $e2));
            }

            $q .= ' ' . $part;
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
        if (empty($this->parts['select']) || empty($this->parts['from'])) {
            return false;
        }

        $needsSubQuery = false;
        $subquery = '';
        $k  = array_keys($this->_aliasMap);
        $table = $this->_aliasMap[$k[0]]['table'];

        if( ! empty($this->parts['limit']) && $this->needsSubquery && $table->getAttribute(Doctrine::ATTR_QUERY_LIMIT) == Doctrine::LIMIT_RECORDS) {
            $needsSubQuery = true;
            $this->limitSubqueryUsed = true;
        }

        // process all pending SELECT part subqueries
        $this->processPendingSubqueries();

        // build the basic query

        $str = '';
        if($this->isDistinct()) {
            $str = 'DISTINCT ';
        }

        $q  = $this->getQueryBase();
        $q .= $this->buildFrom();

        if ( ! empty($this->parts['set'])) {
            $q .= ' SET ' . implode(', ', $this->parts['set']);
        }

        $string = $this->applyInheritance();

        if ( ! empty($string)) {
            $this->parts['where'][] = '(' . $string . ')';
        }


        $modifyLimit = true;
        if ( ! empty($this->parts["limit"]) || ! empty($this->parts["offset"])) {

            if($needsSubQuery) {
                $subquery = $this->getLimitSubquery();


                switch(strtolower($this->conn->getName())) {
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

                $field    = $this->aliasHandler->getShortAlias($table->getTableName()) . '.' . $table->getIdentifier();

                // only append the subquery if it actually contains something
                if($subquery !== '') {
                    array_unshift($this->parts['where'], $field. ' IN (' . $subquery . ')');
                }
                
                $modifyLimit = false;
            }
        }

        $q .= ( ! empty($this->parts['where']))?   ' WHERE '    . implode(' AND ', $this->parts['where']):'';
        $q .= ( ! empty($this->parts['groupby']))? ' GROUP BY ' . implode(', ', $this->parts['groupby']):'';
        $q .= ( ! empty($this->parts['having']))?  ' HAVING '   . implode(' AND ', $this->parts['having']):'';
        $q .= ( ! empty($this->parts['orderby']))? ' ORDER BY ' . implode(', ', $this->parts['orderby']):'';

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
        $k          = array_keys($this->tables);
        $table      = $this->tables[$k[0]];

        // get short alias
        $alias      = $this->aliasHandler->getShortAlias($table->getTableName());
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

        $subquery .= ' FROM ' . $this->conn->quoteIdentifier($table->getTableName()) . ' ' . $alias;

        foreach ($this->parts['join'] as $parts) {
            foreach ($parts as $part) {
                // preserve LEFT JOINs only if needed
                if (substr($part,0,9) === 'LEFT JOIN') {
                    $e = explode(' ', $part);

                    if ( ! in_array($e[3], $this->subqueryAliases) &&
                         ! in_array($e[2], $this->subqueryAliases)) {
                        continue;
                    }

                }

                $subquery .= ' '.$part;
            }
        }

        // all conditions must be preserved in subquery
        $subquery .= ( ! empty($this->parts['where']))?   ' WHERE '    . implode(' AND ', $this->parts['where'])  : '';
        $subquery .= ( ! empty($this->parts['groupby']))? ' GROUP BY ' . implode(', ', $this->parts['groupby'])   : '';
        $subquery .= ( ! empty($this->parts['having']))?  ' HAVING '   . implode(' AND ', $this->parts['having']) : '';
        $subquery .= ( ! empty($this->parts['orderby']))? ' ORDER BY ' . implode(', ', $this->parts['orderby'])   : '';

        // add driver specific limit clause
        $subquery = $this->conn->modifyLimitQuery($subquery, $this->parts['limit'], $this->parts['offset']);

        $parts = self::quoteExplode($subquery, ' ', "'", "'");

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
                    $this->parts['set'][] = $parser->parse($part);
                break;
                case 'group':
                case 'order':
                    $k .= 'by';
                case 'where':
                case 'having':
                    $class  = 'Doctrine_Query_' . ucwords(strtolower($k));
                    $parser = new $class($this);

                    $name = strtolower($k);
                    $this->parts[$name][] = $parser->parse($part);
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
}
