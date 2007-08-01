<?php
/*
 *  $Id: Hook.php 1939 2007-07-05 23:47:48Z zYne $
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
 * Doctrine_Search_Query
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1939 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Search_Query
{
    const OPERATOR_OR = 0;
    const OPERATOR_AND = 1;
    /**
     * @var Doctrine_Query $query           the base query
     */
    protected $_query;
    /**
     * @var Doctrine_Table $_table          the index table
     */
    protected $_table = array();
    
    protected $_sql = '';
    /**
     * @param octrine_Table $_table         the index table
     */
    public function __construct($table)
    {
        if (is_string($table)) {
           $table = Doctrine_Manager::table($table);
        }

        $this->_table = $table;

        $this->_query = new Doctrine_Query();
    }
    /**
     * getQuery
     *
     * @return Doctrine_Query       returns the query object associated with this object
     */
    public function getQuery()
    {
        return $this->_query;
    }

    public function search($text)
    {
        $text = strtolower(trim($text));

        $foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));

        $weighted = false;
        if (strpos($text, '^') === false) {
            $select = 'SELECT COUNT(keyword) AS relevance, ' . $foreignId;
            $from = 'FROM ' . $this->_table->getTableName();
        } else {
            // organize terms according weights
            $weighted = true;

            $select = 'SELECT SUM(sub_relevance) AS relevance, ' . $foreignId;
            $from = 'FROM ' ;
        }
        
        $where = 'WHERE ';
        $where .= $this->parseClause($text);

        $groupby = 'GROUP BY ' . $foreignId;
        $orderby = 'ORDER BY relevance';

        $this->_sql = $select . ' ' . $from . ' ' . $where . ' ' . $groupby . ' ' . $orderby;
    }
    public function tokenizeClause($clause)
    {
    	$clause = strtolower(trim($clause));
        $clause = Doctrine_Tokenizer::bracketTrim($clause);

        $terms = Doctrine_Tokenizer::sqlExplode($clause, ' ', '(', ')');

        $operator = self::OPERATOR_AND;

        $ret = array();

        $pending = false;

        $i = 0;
        $prev = false;
        foreach ($terms as $k => $term) {
            $term = trim($term);

            if ($term === 'and') {
                $operator = self::OPERATOR_AND;
            } elseif ($term === 'or') {
                $operator = self::OPERATOR_OR;
            } else {
                if ($operator === self::OPERATOR_OR) {
                    $ret[$i] = $term;      
                    $i++;
                } else {
                    if ($k === 0) {
                        $ret[$i] = $term;
                        $i++;
                    } else {
                        if ( ! is_array($ret[($i - 1)])) {
                            $ret[($i - 1)] = array_merge(array($ret[($i - 1)]), array($term));
                        } else {
                            $ret[($i - 1)][] = $term;
                        }
                    }
                }
                $operator = self::OPERATOR_AND;
            }
        }

        return $ret;
    }

    public function parseClause($clause)
    {
        $clause = Doctrine_Tokenizer::bracketTrim($clause);

        $foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));

        $terms = $this->tokenizeClause($clause);

        if (count($terms) > 1) {
            $ret = array();

            foreach ($terms as $term) {
                if (is_array($term)) {
                    $parsed = $this->parseTerms($term);
                } else {
                    if (strpos($term, '(') === false) {
                        if (substr($term, 0, 1) === '-') {
                            $operator = 'NOT IN';
                            $term = substr($term, 1);
                        } else {
                            $operator = 'IN';
                        }
                        $parsed = $foreignId . ' ' . $operator . ' (SELECT ' . $foreignId . ' FROM ' . $this->_table->getTableName() . ' WHERE ' . $this->parseClause($term) . ')';
                    } else {
                        $parsed = $this->parseClause($term);
                    }
                }

                if (strlen($parsed) > 20) {
                    $ret[] = '(' . $parsed . ')';
                } else {
                    $ret[] = $parsed;
                }
            }

            $r = implode(' AND ', $ret);
        } else {
            $terms = (is_array($terms[0])) ? $terms[0] : array($terms[0]);

            return $this->parseTerms($terms);
        }
        return $r;
    }
    public function parseTerms(array $terms)
    {
    	$foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));

        if (count($terms) > 1) {
            $ret = array();
            foreach ($terms as $term) {
                $ret[] = $this->parseClause($term);
            }
            $parsed = implode(' OR ', $ret);

            if (strpos($parsed, '(') === false) {
                $parsed = $foreignId . ' IN (SELECT ' . $foreignId . ' FROM ' . $this->_table->getTableName() . ' WHERE ' . $parsed . ')';
            }

            return $parsed;
        } else {
            $ret = $this->parseTerm($terms[0]);
            return $ret[0];
        }
    }
    public function parseTerm($term)
    {
    	$negation = false;


        if (strpos($term, "'") === false) {

            $where = 'keyword = ?';
            
            $params = array($term);
        } else {
            $term = trim($term, "' ");
            
            $where = 'keyword = ?';
            $terms = Doctrine_Tokenizer::quoteExplode($term);
            $params = $terms;
            foreach ($terms as $k => $word) {
                if ($k === 0) {
                    continue;
                }
                $where .= ' AND (position + ' . $k . ') = (SELECT position FROM ' . $this->_table->getTableName() . ' WHERE keyword = ?)';
            }
        }
        return array($where, $params);
    }

    public function getSql()
    {
        return $this->_sql;
    }
    public function execute()
    {
        $resultSet = $this->_query->execute(); 
        
        return $resultSet;
    }
}
