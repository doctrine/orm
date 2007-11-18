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
 * @subpackage  Search
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Search_Query
{

    /**
     * @var Doctrine_Table $_table          the index table
     */
    protected $_table = array();
    
    protected $_sql = '';
    
    protected $_params = array();
    
    protected $_words = array();
    

    protected $_condition;

    /**
     * @param Doctrine_Table $_table        the index table
     */
    public function __construct($table)
    {
        if (is_string($table)) {
           $table = Doctrine_Manager::table($table);
        } else {
            if ( ! $table instanceof Doctrine_Table) {
                throw new Doctrine_Search_Exception('Invalid argument type. Expected instance of Doctrine_Table.');
            }
        }

        $this->_table = $table;

        $foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));

        $this->_condition = $foreignId . ' %s (SELECT ' . $foreignId . ' FROM ' . $this->_table->getTableName() . ' WHERE ';
    }


    public function query($text)
    {
        $text = trim($text);

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
        $orderby = 'ORDER BY relevance DESC';

        $this->_sql = $select . ' ' . $from . ' ' . $where . ' ' . $groupby . ' ' . $orderby;
    }

    public function parseClause($originalClause, $recursive = false)
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
                    $ret[$k] = $this->parseClause($term, true);
                    $leavesOnly = false;
                } else {
                    $ret[$k] = $this->parseTerm($term);
                }
            }

            $return = implode(' OR ', $ret);

            if ($leavesOnly && $recursive) {
                $return = sprintf($this->_condition, 'IN') . $return . ')';
                $brackets = false;
            }
        } else {
            $terms = Doctrine_Tokenizer::sqlExplode($clause, ' ', '(', ')');
            
            if (count($terms) === 1 && ! $recursive) {
                $return = $this->parseTerm($clause);
            } else {
                foreach ($terms as $k => $term) {
                    $term = trim($term);
    
                    if ($term === 'AND') {
                        continue;
                    }
    
                    if (substr($term, 0, 1) === '-') {
                        $operator = 'NOT IN';
                        $term = substr($term, 1);
                    } else {
                        $operator = 'IN';
                    }
    
                    if ($this->isExpression($term)) {
                        $ret[$k] = $this->parseClause($term, true);
                    } else {
                        $ret[$k] = sprintf($this->_condition, $operator) . $this->parseTerm($term) . ')';
                    }
                }
                $return = implode(' AND ', $ret);
            }
        }

        if ($brackets) {
            return '(' . $return . ')';
        } else {
            return $return;
        }
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

    public function parseTerm($term)
    {
        $negation = false;

        if (strpos($term, "'") === false) {
            $where = $this->parseWord($term);
        } else {
            $term = trim($term, "' ");

            $terms = Doctrine_Tokenizer::quoteExplode($term);
            $where = $this->parseWord($terms[0]);

            foreach ($terms as $k => $word) {
                if ($k === 0) {
                    continue;
                }
                $where .= ' AND (position + ' . $k . ') = (SELECT position FROM ' . $this->_table->getTableName() . ' WHERE ' . $this->parseWord($word) . ')';
            }
        }
        return $where;
    }
    public function parseWord($word)
    {
        $this->_words[] = str_replace('*', '', $word);

        if (strpos($word, '?') !== false ||
            strpos($word, '*') !== false) {

            $word = str_replace('*', '%', $word);

            $where = 'keyword LIKE ?';

            $params = array($word);
        } else {
            $where = 'keyword = ?';
        }

        $this->_params[] = $word;

        return $where;
    }

    public function getWords()
    {
        return $this->_words;
    }
    public function getParams()
    {
        return $this->_params;
    }
    public function getSql()
    {
        return $this->_sql;
    }
}
