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

        $terms = Doctrine_Tokenizer::quoteExplode($text);
        
        $foreignId = current(array_diff($this->_table->getColumnNames(), array('keyword', 'field', 'position')));
        
        $numTerms = count($terms);
        switch ($numTerms) {
            case 0:
                return false;
            break;
            case 1:
                // only one term found, use fast and simple query
                $select = 'SELECT COUNT(keyword) AS relevance, ' . $foreignId;
                $from = 'FROM ' . $this->_table->getTableName();

                if (strpos($terms[0], "'") === false) {
                    $where = 'WHERE keyword = ?';
                    
                    $params = array($terms[0]);
                } else {
                    $terms[0] = trim($terms[0], "' ");
                    
                    $where = 'WHERE keyword = ?';
                    $terms = Doctrine_Tokenizer::quoteExplode($terms[0]);
                    $params = $terms;
                    foreach ($terms as $k => $term) {
                        if ($k === 0) {
                            continue;
                        }
                        $where .= ' AND (position + ' . $k . ') = (SELECT position FROM ' . $this->_table->getTableName() . ' WHERE keyword = ?)';
                    }
                }
            break;
            default:
                $select = 'SELECT COUNT(keyword) AS relevance, ' . $foreignId;
                $from = 'FROM ' . $this->_table->getTableName();

                $where = 'WHERE ';
                $cond = array();
                $params = array();

                foreach ($terms as $term) {
                    $data   = $this->parseTerm($term);
                    $params = array_merge($params, $data[1]);
                    $cond[] = $foreignId . ' IN (SELECT ' . $foreignId . ' FROM ' . $this->_table->getTableName() . ' ' . $data[0] . ')';
                }
                $where .= implode(' AND ', $cond);
        }
        
        $groupby = 'GROUP BY ' . $foreignId;
        $orderby = 'ORDER BY relevance';

        $this->_sql = $select . ' ' . $from . ' ' . $where . ' ' . $groupby . ' ' . $orderby;
    }
    public function parseTerm($term)
    {
        if (strpos($term, "'") === false) {
            $where = 'WHERE keyword = ?';
            
            $params = array($term);
        } else {
            $term = trim($term, "' ");
            
            $where = 'WHERE keyword = ?';
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
    public function search2($text)
    {
    	$text = strtolower($text);

        $terms = Doctrine_Tokenizer::quoteExplode($text);

        $map = $this->_query->getRootDeclaration();
        $rootAlias = $this->_query->getRootAlias();

        $component = $map['table']->getComponentName() . 'Index';
        $subAlias = 'i2';

        $rel = $map['table']->getRelation($component);

        $foreign = (array) $rel->getForeign();
        foreach ((array) $rel->getLocal() as $k => $field) {
            $joinCondition = $rootAlias . '.' . $field . ' = ' . $subAlias . '.' . $foreign[$k];
        }

        $this->_query->innerJoin($rootAlias . '.' . $component . ' ' . 'i');

        foreach ($this->_aliases as $alias) {
            $condition = array();
            $subcondition = array();

            foreach ($terms as $term) {
                $condition[] = $alias . '.keyword = ?';
                $subcondition[] = $subAlias . '.keyword = ?';
            }
            $this->_query->addSelect('(SELECT COUNT(' . $subAlias . '.position) FROM '
                                    . $component . ' ' . $subAlias . ' WHERE '
                                    . implode(' OR ', $subcondition) . ' AND ' . $joinCondition . ') relevancy');

            $this->_query->addWhere(implode(' OR ', $condition), $terms);
        }
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
