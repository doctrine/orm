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
     * @var array $_aliases                 an array of searchable component aliases
     */
    protected $_aliases = array();
    /**
     * @param Doctrine_Query $query         the base query
     */
    public function __construct($query)
    {
        if (is_string($query)) {
            $this->_query = new Doctrine_Query();
            $this->_query->parseQuery($query);
        } elseif ($query instanceof Doctrine_Query) {
            $this->_query = $query;
        } else {
            throw new Doctrine_Exception('Constructor argument should be either Doctrine_Query object or a valid DQL query string');
        }
        
        $this->_query->getQuery();
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
    
    public function addAlias($alias)
    {
        $this->_aliases[] = $alias;
    }

    public function search($text)
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
    
    public function execute()
    {
        $resultSet = $this->_query->execute(); 
        
        return $resultSet;
    }
}
