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
 * Doctrine_Query
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1296 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query 
{
    /**
     * addSelect
     * adds fields to the SELECT part of the query
     *
     * @param string $select        DQL SELECT part
     * @return Doctrine_Query
     */
    public function addSelect($select)
    {
        return $this->getParser('select')->parse($select, true);
    }
    /**
     * addWhere
     * adds conditions to the WHERE part of the query
     *
     * @param string $where         DQL WHERE part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addWhere($where, $params = array())
    {
        if(is_array($params)) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params[] = $params;
        }
        return $this->getParser('where')->parse($where, true);
    }
    /**
     * addGroupBy
     * adds fields to the GROUP BY part of the query
     *
     * @param string $groupby       DQL GROUP BY part
     * @return Doctrine_Query
     */
    public function addGroupBy($groupby)
    {
        return $this->getParser('groupby')->parse($groupby, true);
    }
    /**
     * addHaving
     * adds conditions to the HAVING part of the query
     *
     * @param string $having        DQL HAVING part
     * @return Doctrine_Query
     */
    public function addHaving($having)
    {
        return $this->getParser('having')->parse($having, true);
    }
    /**
     * addOrderBy
     * adds fields to the ORDER BY part of the query
     *
     * @param string $orderby       DQL ORDER BY part
     * @return Doctrine_Query
     */
    public function addOrderBy($orderby)
    {
        return $this->getParser('orderby')->parse($orderby, true);
    }
    /**
     * select
     * sets the SELECT part of the query
     *
     * @param string $select        DQL SELECT part
     * @return Doctrine_Query
     */
    public function select($select)
    {
        return $this->getParser('from')->parse($select);
    }
    /**
     * from
     * sets the FROM part of the query
     *
     * @param string $from          DQL FROM part
     * @return Doctrine_Query
     */
    public function from($from)
    {
        return $this->getParser('from')->parse($from);
    }
    /**
     * innerJoin
     * appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join         DQL INNER JOIN
     * @return Doctrine_Query
     */
    public function innerJoin($join)
    {
        return $this->getParser('from')->parse('INNER JOIN ' . $join);
    }
    /**
     * leftJoin
     * appends a LEFT JOIN to the FROM part of the query
     *
     * @param string $join         DQL LEFT JOIN
     * @return Doctrine_Query
     */
    public function leftJoin($join)
    {
        return $this->getParser('from')->parse('LERT JOIN ' . $join);
    }
    /**
     * groupBy
     * sets the GROUP BY part of the query
     *
     * @param string $groupby      DQL GROUP BY part
     * @return Doctrine_Query
     */
    public function groupBy($groupby)
    {
        return $this->getParser('groupby')->parse($groupby);
    }
    /**
     * where
     * sets the WHERE part of the query
     *
     * @param string $join         DQL WHERE part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function where($where, $params = array())
    {
        if(is_array($params)) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params[] = $params;
        }
        return $this->getParser('where')->parse($where);
    }
    /**
     * having
     * sets the HAVING part of the query
     *
     * @param string $having       DQL HAVING part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function having($having, $params)
    {
        if(is_array($params)) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params[] = $params;
        }
        return $this->getParser('having')->parse($having);
    }
    /**
     * orderBy
     * sets the ORDER BY part of the query
     *
     * @param string $groupby      DQL ORDER BY part
     * @return Doctrine_Query
     */
    public function orderBy($dql)
    {
        return $this->getParser('orderby')->parse($dql);
    }
    /**
     * limit
     * sets the DQL query limit
     *
     * @param integer $limit        limit to be used for limiting the query results
     * @return Doctrine_Query
     */
    public function limit($limit)
    {
        return $this->getParser('limit')->parse($dql);
    }
    /**
     * offset
     * sets the DQL query offset
     *
     * @param integer $offset       offset to be used for paginating the query
     * @return Doctrine_Query
     */
    public function offset($dql)
    {
        return $this->getParser('offset')->parse($dql);
    }
}
