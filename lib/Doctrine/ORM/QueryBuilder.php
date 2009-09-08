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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Doctrine\ORM\Query\Expr;

/**
 * This class is responsible for building DQL query strings via an object oriented
 * PHP interface.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class QueryBuilder
{
    const SELECT = 0;
    const DELETE = 1;
    const UPDATE = 2;

    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /**
     * @var EntityManager $em Instance of an EntityManager to use for query.
     */
    private $_em;

    /**
     * @var array $dqlParts The array of DQL parts collected.
     */
    private $_dqlParts = array(
        'select'  => array(),
        'from'    => null,
        'join'    => array(),
        'set'     => array(),
        'where'   => null,
        'groupBy' => array(),
        'having'  => null,
        'orderBy' => array()
    );

    /**
     * @var integer The type of query this is. Can be select, update or delete.
     */
    private $_type = self::SELECT;

    /**
     * @var integer The state of the query object. Can be dirty or clean.
     */
    private $_state = self::STATE_CLEAN;

    /**
     * @var string The complete DQL string for this query.
     */
    private $_dql;

    /**
     * @var Query The Query instance used for this QueryBuilder.
     */
    private $_q;

    /**
     * @var Expr The Expr instance used to generate DQL expressions
     */
    private $_expr;

    /**
     * Initializes a new <tt>QueryBuilder</tt> that uses the given <tt>EntityManager</tt>.
     * 
     * @param EntityManager $entityManager The EntityManager to use.
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->_em = $entityManager;
        $this->_q = $entityManager->createQuery();
    }

    /**
     * Factory for instantiating and retrieving the Expr instance when needed
     *
     * @return Expr $expr
     */
    public function expr()
    {
        if ( ! $this->_expr) {
            $this->_expr = new Expr;
        }
        return $this->_expr;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getEntityManager()
    {
        return $this->_em;
    }

    public function getState()
    {
        return $this->_state;
    }

    public function getDql()
    {
        if ($this->_dql !== null && $this->_state === self::STATE_CLEAN) {
            return $this->_dql;
        }

        $dql = '';

        switch ($this->_type) {
            case self::DELETE:
                $dql = $this->_getDqlForDelete();
                break;

            case self::UPDATE:
                $dql = $this->_getDqlForUpdate();
                break;

            case self::SELECT:
            default:
                $dql = $this->_getDqlForSelect();
                break;
        }

        $this->_state = self::STATE_CLEAN;
        $this->_dql = $dql;

        return $dql;
    }

    public function getQuery()
    {
        $this->_q->setDql($this->getDql());

        return $this->_q;
    }

    /**
     * Sets a query parameter.
     *
     * @param string|integer $key The parameter position or name.
     * @param mixed $value The parameter value.
     */
    public function setParameter($key, $value)
    {
        $this->_q->setParameter($key, $value);

        return $this;
    }
    
    /**
     * Sets a collection of query parameters.
     *
     * @param array $params
     */
    public function setParameters(array $params)
    {
        $this->_q->setParameters($params);

        return $this;
    }

    /**
     * Get all defined parameters
     *
     * @return array Defined parameters
     */
    public function getParameters($params = array())
    {
        return $this->_q->getParameters($params);
    }
    
    /**
     * Gets a query parameter.
     * 
     * @param mixed $key The key (index or name) of the bound parameter.
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        return $this->_q->getParameter($key);
    }

    /**
     * Add a single DQL query part to the array of parts
     *
     * @param string $dqlPartName 
     * @param string $dqlPart 
     * @param string $append 
     * @return QueryBuilder $this
     */
    public function add($dqlPartName, $dqlPart, $append = false)
    {
        $isMultiple = is_array($this->_dqlParts[$dqlPartName]);
    
        if ($append && $isMultiple) {
            $this->_dqlParts[$dqlPartName][] = $dqlPart;
        } else {
            $this->_dqlParts[$dqlPartName] = ($isMultiple) ? array($dqlPart) : $dqlPart;
        }

        $this->_state = self::STATE_DIRTY;

        return $this;
    }

    public function select($select = null)
    {
        $this->_type = self::SELECT;
        $selects = func_get_args();

        if (empty($selects)) {
            return $this;
        }
        
        return $this->add('select', new Expr\Select($selects), true);
    }

    public function delete($delete = null, $alias = null)
    {
        $this->_type = self::DELETE;

        if ( ! $delete) {
            return $this;
        }

        return $this->add('from', new Expr\From($delete, $alias));
    }

    public function update($update = null, $alias = null)
    {
        $this->_type = self::UPDATE;

        if ( ! $update) {
            return $this;
        }

        return $this->add('from', new Expr\From($update, $alias));
    }

    public function from($from, $alias = null)
    {
        return $this->add('from', new Expr\From($from, $alias));
    }
    
    public function innerJoin($join, $alias = null, $conditionType = null, $condition = null)
    {
        return $this->add('join', new Expr\Join(
            Expr\Join::INNER_JOIN, $join, $alias, $conditionType, $condition
        ), true);
    }

    public function leftJoin($join, $alias = null, $conditionType = null, $condition = null)
    {
        return $this->add('join', new Expr\Join(
            Expr\Join::LEFT_JOIN, $join, $alias, $conditionType, $condition
        ), true);
    }

    public function set($key, $value)
    {
        return $this->add('set', new Expr\Comparison($key, Expr\Comparison::EQ, $value), true);
    }

    public function where($where)
    {
        if ( ! (func_num_args() == 1 && ($where instanceof Expr\Andx || $where instanceof Expr\Orx))) {
            $where = new Expr\Andx(func_get_args());
        }
        
        return $this->add('where', $where);
    }

    public function andWhere($where)
    {
        $where = $this->_getDqlQueryPart('where');
        $args = func_get_args();
        
        if ($where instanceof Expr\Andx) {
            $where->addMultiple($args);
        } else { 
            array_unshift($args, $where);
            $where = new Expr\Andx($args);
        }
        
        return $this->add('where', $where);
    }

    public function orWhere($where)
    {
        $where = $this->_getDqlQueryPart('where');
        $args = func_get_args();
        
        if ($where instanceof Expr\Orx) {
            $where->addMultiple($args);
        } else {            
            array_unshift($args, $where);
            $where = new Expr\Orx($args);
        }
        
        return $this->add('where', $where);
    }

    public function groupBy($groupBy)
    {
        return $this->add('groupBy', new Expr\GroupBy(func_get_args()));
    }

    public function addGroupBy($groupBy)
    {
        return $this->add('groupBy', new Expr\GroupBy(func_get_args()), true);
    }

    public function having($having)
    {
        if ( ! (func_num_args() == 1 && ($having instanceof Expr\Andx || $having instanceof Expr\Orx))) {
            $having = new Expr\Andx(func_get_args());
        }
        
        return $this->add('having', $having);
    }

    public function andHaving($having)
    {
        $having = $this->_getDqlQueryPart('having');
        $args = func_get_args();
        
        if ($having instanceof Expr\Andx) {
            $having->addMultiple($args);
        } else { 
            array_unshift($args, $having);
            $having = new Expr\Andx($args);
        }
        
        return $this->add('having', $having);
    }

    public function orHaving($having)
    {
        $having = $this->_getDqlQueryPart('having');
        $args = func_get_args();
        
        if ($having instanceof Expr\Orx) {
            $having->addMultiple($args);
        } else { 
            array_unshift($args, $having);
            $having = new Expr\Orx($args);
        }
        
        return $this->add('having', $having);
    }

    public function orderBy($sort, $order = null)
    {
        return $this->add('orderBy', new Expr\OrderBy($sort, $order));
    }

    public function addOrderBy($sort, $order = null)
    {
        return $this->add('orderBy', new Expr\OrderBy($sort, $order), true);
    }

    /**
     * Get the DQL query string for DELETE queries
     * EBNF:
     *
     * DeleteStatement = DeleteClause [WhereClause] [OrderByClause] [LimitClause] [OffsetClause]
     * DeleteClause    = "DELETE" "FROM" RangeVariableDeclaration
     * WhereClause     = "WHERE" ConditionalExpression
     * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
     * LimitClause     = "LIMIT" integer
     * OffsetClause    = "OFFSET" integer
     *
     * @return string $dql
     */
    private function _getDqlForDelete()
    {
         return 'DELETE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '));
    }

    /**
     * Get the DQL query string for UPDATE queries
     * EBNF:
     *
     * UpdateStatement = UpdateClause [WhereClause] [OrderByClause]
     * UpdateClause    = "UPDATE" RangeVariableDeclaration "SET" UpdateItem {"," UpdateItem}
     * WhereClause     = "WHERE" ConditionalExpression
     * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
     *
     * @return string $dql
     */
    private function _getDqlForUpdate()
    {
         return 'UPDATE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' '))
              . $this->_getReducedDqlQueryPart('set', array('pre' => ' SET ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '));
    }

    /**
     * Get the DQL query string for SELECT queries
     * EBNF:
     *
     * SelectStatement = [SelectClause] FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     * SelectClause    = "SELECT" ["ALL" | "DISTINCT"] SelectExpression {"," SelectExpression}
     * FromClause      = "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
     * WhereClause     = "WHERE" ConditionalExpression
     * GroupByClause   = "GROUP" "BY" GroupByItem {"," GroupByItem}
     * HavingClause    = "HAVING" ConditionalExpression
     * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
     *
     * @return string $dql
     */
    private function _getDqlForSelect()
    {
         return 'SELECT' 
              . $this->_getReducedDqlQueryPart('select', array('pre' => ' ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' FROM '))
              . $this->_getReducedDqlQueryPart('join', array('pre' => ' ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedDqlQueryPart('groupBy', array('pre' => ' GROUP BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('having', array('pre' => ' HAVING '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '));
    }

    private function _getReducedDqlQueryPart($queryPartName, $options = array())
    {
        $queryPart = $this->_getDqlQueryPart($queryPartName);
        
        if (empty($queryPart)) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }
        
        return (isset($options['pre']) ? $options['pre'] : '')
             . (is_array($queryPart) ? implode($options['separator'], $queryPart) : $queryPart)
             . (isset($options['post']) ? $options['post'] : '');
    }

    private function _getDqlQueryPart($queryPartName)
    {
        return $this->_dqlParts[$queryPartName];
    }
    
    public function __toString()
    {
        return $this->getDql();
    }
}