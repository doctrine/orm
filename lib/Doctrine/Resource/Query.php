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

/**
 * Doctrine_Resource_Query
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Resource_Query
{
    protected $_parts = array();
    protected $_dql = null;
    protected $_params = array();
    
    public function getConfig($key = null)
    {
        return Doctrine_Resource_Client::getInstance()->getConfig($key);
    }
    
    public function query($dql, $params = array())
    {
        $this->_dql = $dql;
        
        return $this->execute($params);
    }
    
    public function execute($params = array())
    {
        $request = new Doctrine_Resource_Request();
        $request->set('dql', $this->getDql());
        $request->set('params', $params);
        $request->set('format', $this->getConfig()->get('format'));
        $request->set('type', 'query');
        
        $response = $request->execute();
        
        $array = Doctrine_Parser::load($response, $this->getConfig()->get('format'));
        
        return $request->hydrate($array, $this->getModel());
    }
    
    public function getDql()
    {
        if (!$this->_dql && !empty($this->_parts)) {
            $q = '';
            $q .= ( ! empty($this->_parts['select']))?  'SELECT '    . implode(', ', $this->_parts['select']) : '';
            $q .= ( ! empty($this->_parts['from']))?    ' FROM '     . implode(' ', $this->_parts['from']) : '';
            $q .= ( ! empty($this->_parts['where']))?   ' WHERE '    . implode(' AND ', $this->_parts['where']) : '';
            $q .= ( ! empty($this->_parts['groupby']))? ' GROUP BY ' . implode(', ', $this->_parts['groupby']) : '';
            $q .= ( ! empty($this->_parts['having']))?  ' HAVING '   . implode(' AND ', $this->_parts['having']) : '';
            $q .= ( ! empty($this->_parts['orderby']))? ' ORDER BY ' . implode(', ', $this->_parts['orderby']) : '';
            $q .= ( ! empty($this->_parts['limit']))?   ' LIMIT '    . implode(' ', $this->_parts['limit']) : '';
            $q .= ( ! empty($this->_parts['offset']))?  ' OFFSET '   . implode(' ', $this->_parts['offset']) : '';
            
            return $q;
        } else {
            return $this->_dql;
        }
    }
    
    public function buildUrl($array)
    {
        $url = '';
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $url .= $this->buildUrl($value);
            } else {
                $url .= $key.'='.$value.'&';
            }
        }
        
        return $url;
    }
    
    public function getModel()
    {
        $dql = $this->getDql();
        
        $e = explode('FROM ', $dql);
        $e = explode(' ', $e[1]);
        
        return $e[0];
    }
    
    /**
     * addSelect
     * adds fields to the SELECT part of the query
     *
     * @param string $select        Query SELECT part
     * @return Doctrine_Query
     */
    public function addSelect($select)
    {
        return $this->parseQueryPart('select', $select, true);
    }
    /**
     * addFrom
     * adds fields to the FROM part of the query
     *
     * @param string $from        Query FROM part
     * @return Doctrine_Query
     */
    public function addFrom($from)
    {
        return $this->parseQueryPart('from', $from, true);
    }
    /**
     * addWhere
     * adds conditions to the WHERE part of the query
     *
     * @param string $where         Query WHERE part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addWhere($where, $params = array())
    {
        if (is_array($params)) {
            $this->_params['where'] = array_merge($this->_params['where'], $params);
        } else {
            $this->_params['where'][] = $params;
        }
        return $this->parseQueryPart('where', $where, true);
    }
    /**
     * whereIn
     * adds IN condition to the query WHERE part
     *
     * @param string $expr
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function whereIn($expr, $params = array())
    {
        $params = (array) $params;
        $a = array();
        foreach ($params as $k => $value) {
            if ($value instanceof Doctrine_Expression) {
                $value = $value->getSql();
                unset($params[$k]);
            } else {
                $value = '?';          
            }
            $a[] = $value;
        }

        $this->_params['where'] = array_merge($this->_params['where'], $params);

        $where = $expr . ' IN (' . implode(', ', $a) . ')';

        return $this->parseQueryPart('where', $where, true);
    }
    /**
     * addGroupBy
     * adds fields to the GROUP BY part of the query
     *
     * @param string $groupby       Query GROUP BY part
     * @return Doctrine_Query
     */
    public function addGroupBy($groupby)
    {
        return $this->parseQueryPart('groupby', $groupby, true);
    }
    /**
     * addHaving
     * adds conditions to the HAVING part of the query
     *
     * @param string $having        Query HAVING part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addHaving($having, $params = array())
    {
        if (is_array($params)) {
            $this->_params['having'] = array_merge($this->_params['having'], $params);
        } else {
            $this->_params['having'][] = $params;
        }
        return $this->parseQueryPart('having', $having, true);
    }
    /**
     * addOrderBy
     * adds fields to the ORDER BY part of the query
     *
     * @param string $orderby       Query ORDER BY part
     * @return Doctrine_Query
     */
    public function addOrderBy($orderby)
    {
        return $this->parseQueryPart('orderby', $orderby, true);
    }
    /**
     * select
     * sets the SELECT part of the query
     *
     * @param string $select        Query SELECT part
     * @return Doctrine_Query
     */
    public function select($select)
    {
        return $this->parseQueryPart('select', $select);
    }
    /**
     * distinct
     * Makes the query SELECT DISTINCT.
     *
     * @param bool $flag            Whether or not the SELECT is DISTINCT (default true).
     * @return Doctrine_Query
     */
    public function distinct($flag = true)
    {   
        $this->_parts['distinct'] = (bool) $flag;

        return $this;
    }

    /**
     * forUpdate
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag            Whether or not the SELECT is FOR UPDATE (default true).
     * @return Doctrine_Query
     */
    public function forUpdate($flag = true)
    {
        $this->_parts[self::FOR_UPDATE] = (bool) $flag;

        return $this;
    }
    /**
     * delete
     * sets the query type to DELETE
     *
     * @return Doctrine_Query
     */
    public function delete()
    {
        $this->type = self::DELETE;

        return $this;
    }
    /**
     * update
     * sets the UPDATE part of the query
     *
     * @param string $update        Query UPDATE part
     * @return Doctrine_Query
     */
    public function update($update)
    {
        $this->type = self::UPDATE;

        return $this->parseQueryPart('from', $update);
    }
    /**
     * set
     * sets the SET part of the query
     *
     * @param string $update        Query UPDATE part
     * @return Doctrine_Query
     */
    public function set($key, $value, $params = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, '?', array($v));                               
            }
        } else {
            if ($params !== null) {
                if (is_array($params)) {
                    $this->_params['set'] = array_merge($this->_params['set'], $params);
                } else {
                    $this->_params['set'][] = $params;
                }
            }
            return $this->parseQueryPart('set', $key . ' = ' . $value, true);
        }
    }
    /**
     * from
     * sets the FROM part of the query
     *
     * @param string $from          Query FROM part
     * @return Doctrine_Query
     */
    public function from($from)
    {
        return $this->parseQueryPart('from', $from);
    }
    /**
     * innerJoin
     * appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join         Query INNER JOIN
     * @return Doctrine_Query
     */
    public function innerJoin($join)
    {
        return $this->parseQueryPart('from', 'INNER JOIN ' . $join, true);
    }
    /**
     * leftJoin
     * appends a LEFT JOIN to the FROM part of the query
     *
     * @param string $join         Query LEFT JOIN
     * @return Doctrine_Query
     */
    public function leftJoin($join)
    {
        return $this->parseQueryPart('from', 'LEFT JOIN ' . $join, true);
    }
    /**
     * groupBy
     * sets the GROUP BY part of the query
     *
     * @param string $groupby      Query GROUP BY part
     * @return Doctrine_Query
     */
    public function groupBy($groupby)
    {
        return $this->parseQueryPart('groupby', $groupby);
    }
    /**
     * where
     * sets the WHERE part of the query
     *
     * @param string $join         Query WHERE part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function where($where, $params = array())
    {
        $this->_params['where'] = array();
        if (is_array($params)) {
            $this->_params['where'] = $params;
        } else {
            $this->_params['where'][] = $params;
        }

        return $this->parseQueryPart('where', $where);
    }
    /**
     * having
     * sets the HAVING part of the query
     *
     * @param string $having       Query HAVING part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function having($having, $params = array())
    {
        $this->_params['having'] = array();
        if (is_array($params)) {
            $this->_params['having'] = $params;
        } else {
            $this->_params['having'][] = $params;
        }
        
        return $this->parseQueryPart('having', $having);
    }
    /**
     * orderBy
     * sets the ORDER BY part of the query
     *
     * @param string $orderby      Query ORDER BY part
     * @return Doctrine_Query
     */
    public function orderBy($orderby)
    {
        return $this->parseQueryPart('orderby', $orderby);
    }
    /**
     * limit
     * sets the Query query limit
     *
     * @param integer $limit        limit to be used for limiting the query results
     * @return Doctrine_Query
     */
    public function limit($limit)
    {
        return $this->parseQueryPart('limit', $limit);
    }
    /**
     * offset
     * sets the Query query offset
     *
     * @param integer $offset       offset to be used for paginating the query
     * @return Doctrine_Query
     */
    public function offset($offset)
    {
        return $this->parseQueryPart('offset', $offset);
    }
    
    /**
      * parseQueryPart
      * parses given DQL query part
      *
      * @param string $queryPartName     the name of the query part
      * @param string $queryPart         query part to be parsed
      * @return Doctrine_Query           this object
      */
      public function parseQueryPart($queryPartName, $queryPart)
      {
          $this->_parts[$queryPartName][] = $queryPart;
          
          return $this;
      }
}
