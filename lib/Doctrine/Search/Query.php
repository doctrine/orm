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
    
    protected $_condition;
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
        $foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));

        $this->_condition = $foreignId . ' IN (SELECT ' . $foreignId . ' FROM ' . $this->_table->getTableName() . ' WHERE ';
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

    public function parseClause($originalClause, $addCondition = false)
    {
        $clause = Doctrine_Tokenizer::bracketTrim($originalClause);
        
        $brackets = false;

        if ($clause !== $originalClause) {
            $brackets = true;
        }

        $foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));
        
        $terms = Doctrine_Tokenizer::sqlExplode($clause, ' OR ', '(', ')');
        
        $ret = array();

        if (count($terms) > 1) {

            $leavesOnly = true;
            foreach ($terms as $k => $term) {
                if ($this->isExpression($term)) {
                    $ret[$k] = $this->parseClause($term);
                    $leavesOnly = false;
                } else {
                    $ret[$k] = $this->parseTerm($term);
                }
            }

            $return = implode(' OR ', $ret);

            if ($leavesOnly) {
                $return = $this->_condition . $return;
            }
            $brackets = false;
        } else {
            $terms = Doctrine_Tokenizer::sqlExplode($clause, ' ', '(', ')');

            foreach ($terms as $k => $term) {
                $term = trim($term);
                
                if ($term === 'AND') {
                    continue;
                }

                if ($this->isExpression($term)) {
                    $ret[$k] = $this->parseClause($term, true);
                } else {
                    $ret[$k] = $this->_condition . $this->parseTerm($term);
                }

                $ret[$k] .= ')';
            }
            $return = implode(' AND ', $ret);
        }
        
        if ($brackets) {
            return '(' . $return . ')';
        } else {
            return $return;
        }

        /**
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
        */
    }
    public function isExpression($term)
    {
        if (strpos($term, '(') !== false) {
            return true;
        } else {
            $terms = Doctrine_Tokenizer::quoteExplode($term);
            
            return (count($terms) > 1);
        }
    }
    public function parseTerms(array $terms)
    {
    	$foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));

        if (count($terms) > 1) {
            $ret = array();
            foreach ($terms as $term) {
                if (strpos($term, '(') === false) {
                    $term = $this->parseClause($term);

                    $ret[] = $foreignId . ' IN (SELECT ' . $foreignId . ' FROM ' . $this->_table->getTableName() . ' WHERE ' . $term . ')';
                }
            }
            $parsed = implode(' AND ', $ret);

            return $parsed;
        } else {
            $ret = $this->parseTerm($terms[0]);
            return $ret[0];
        }
    }
    public function parseExpression($expr) 
    {
        $expr = Doctrine_Tokenizer::bracketTrim($expr);

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
        return $where;
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
