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

use Doctrine\ORM\Query\Expr,
    Doctrine\Common\DoctrineException;

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
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where($qb->expr()->eq('u.id', 1));
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

    /**
     * Get the type of this query instance. Either the constant for SELECT, UPDATE or DELETE
     *
     *     [php]
     *     switch ($qb->getType())
     *     {
     *         case QueryBuilder::SELECT:
     *             echo 'SELECT';
     *         break;
     *
     *         case QueryBuilder::DELETE:
     *             echo 'DELETE';
     *         break;
     *
     *         case QueryBuilder::UPDATE:
     *             echo 'UPDATE';
     *         break;
     *     }
     *
     * @return integer $type
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Get the entity manager instance for this query builder instance
     *
     *     [php]
     *     $em = $qb->getEntityManager();
     *
     * @return EntityManager $em
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * Get the state of this query builder instance
     *
     *     [php]
     *     if ($qb->getState() == QueryBuilder::STATE_DIRTY) {
     *         echo 'Query builder is dirty';
     *     } else {
     *         echo 'Query builder is clean';
     *     }
     *
     * @return integer $state
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Get the complete DQL string for this query builder instance
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *     echo $qb->getDql(); // SELECT u FROM User u
     *
     * @return string $dql The DQL string
     */
    public function getDql()
    {
        if ($this->_dql !== null && $this->_state === self::STATE_CLEAN) {
            return $this->_dql;
        }

        if ( ! $this->_dqlParts['select'] || ! $this->_dqlParts['from']) {
            throw DoctrineException::incompleteQueryBuilder();
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

    /**
     * Get the Query instance with the DQL string set to it
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u');
     *     $q = $qb->getQuery();
     *     $results = $q->execute();
     *
     * @return Query $q
     */
    public function getQuery()
    {
        $this->_q->setDql($this->getDql());

        return $this->_q;
    }

    /**
     * Sets a query parameter.
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter(':user_id', 1);
     *
     * @param string|integer $key The parameter position or name.
     * @param mixed $value The parameter value.
     * @return QueryBuilder $qb
     */
    public function setParameter($key, $value)
    {
        $this->_q->setParameter($key, $value);

        return $this;
    }
    
    /**
     * Sets a collection of query parameters.
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = :user_id1 OR u.id = :user_id2')
     *         ->setParameters(array(
     *             ':user_id1' => 1,
     *             ':user_id2' => 2
     *         ));
     *
     * @param array $params
     * @return QueryBuilder $qb
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
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     * @return QueryBuilder This query builder object.
     */
    public function setFirstResult($firstResult)
    {
        $this->_q->setFirstResult($firstResult);
        return $this;
    }
    
    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this query builder.
     * 
     * @return integer The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->_q->getFirstResult();
    }
    
    /**
     * Sets the maximum number of results to retrieve (the "limit").
     * 
     * @param integer $maxResults
     * @return QueryBuilder This query builder object.
     */
    public function setMaxResults($maxResults)
    {
        $this->_q->setMaxResults($maxResults);
        return $this;
    }
    
    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query builder.
     * 
     * @return integer Maximum number of results.
     */
    public function getMaxResults()
    {
        return $this->_q->getMaxResults();
    }

    /**
     * Add a single DQL query part to the array of parts
     *
     * @param string $dqlPartName 
     * @param string $dqlPart 
     * @param string $append 
     * @return QueryBuilder $qb
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

    /**
     * Add to the SELECT statement
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u', 'p')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p');
     *
     * @param mixed $select  String SELECT statement or SELECT Expr instance
     * @return QueryBuilder $qb
     */
    public function select($select = null)
    {
        $this->_type = self::SELECT;
        $selects = func_get_args();

        if (empty($selects)) {
            return $this;
        }
        
        return $this->add('select', new Expr\Select($selects), true);
    }

    /**
     * Construct a DQL DELETE query
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->delete('User', 'u')
     *         ->where('u.id = :user_id');
     *         ->setParameter(':user_id', 1);
     *
     * @param string $delete    The model to delete 
     * @param string $alias     The alias of the model
     * @return QueryBuilder $qb
     */
    public function delete($delete = null, $alias = null)
    {
        $this->_type = self::DELETE;

        if ( ! $delete) {
            return $this;
        }

        return $this->add('from', new Expr\From($delete, $alias));
    }

    /**
     * Construct a DQL UPDATE query
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     *
     * @param string $update   The model to update
     * @param string $alias    The alias of the model
     * @return QueryBuilder $qb
     */
    public function update($update = null, $alias = null)
    {
        $this->_type = self::UPDATE;

        if ( ! $update) {
            return $this;
        }

        return $this->add('from', new Expr\From($update, $alias));
    }

    /**
     * Specify the FROM part when constructing a SELECT DQL query
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *
     * @param string $from   The model name
     * @param string $alias  The alias of the model
     * @return QueryBuilder $qb
     */
    public function from($from, $alias = null)
    {
        return $this->add('from', new Expr\From($from, $alias));
    }

    /**
     * Add a INNER JOIN
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->innerJoin('u.Phonenumbers', 'p', Expr\Join::WITH, 'p.is_primary = 1');
     *
     * @param string $join           The relationship to join
     * @param string $alias          The alias of the join
     * @param string $conditionType  The condition type constant. Either ON or WITH.
     * @param string $condition      The condition for the join
     * @return QueryBuilder $qb
     */
    public function innerJoin($join, $alias = null, $conditionType = null, $condition = null)
    {
        return $this->add('join', new Expr\Join(
            Expr\Join::INNER_JOIN, $join, $alias, $conditionType, $condition
        ), true);
    }

    /**
     * Add a LEFT JOIN
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p', Expr\Join::WITH, 'p.is_primary = 1');
     *
     * @param string $join           The relationship to join
     * @param string $alias          The alias of the join
     * @param string $conditionType  The condition type constant. Either ON or WITH.
     * @param string $condition      The condition for the join
     * @return QueryBuilder $qb
     */
    public function leftJoin($join, $alias = null, $conditionType = null, $condition = null)
    {
        return $this->add('join', new Expr\Join(
            Expr\Join::LEFT_JOIN, $join, $alias, $conditionType, $condition
        ), true);
    }

    /**
     * Add a SET statement for a DQL UPDATE query
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     *
     * @param string $key     The key/field to set
     * @param string $value   The value, expression, placeholder, etc. to use in the SET
     * @return QueryBuilder $qb
     */
    public function set($key, $value)
    {
        return $this->add('set', new Expr\Comparison($key, Expr\Comparison::EQ, $value), true);
    }

    /**
     * Set and override any existing WHERE statements
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = ?');
     *
     *     // You can optionally programatically build and/or expressions
     *     $qb = $em->createQueryBuilder();
     *
     *     $or = $qb->expr()->orx();
     *     $or->add($qb->expr()->eq('u.id', 1));
     *     $or->add($qb->expr()->eq('u.id', 2));
     *
     *     $qb->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where($or);
     *
     * @param mixed $where The WHERE statement
     * @return QueryBuilder $qb
     */
    public function where($where)
    {
        if ( ! (func_num_args() == 1 && ($where instanceof Expr\Andx || $where instanceof Expr\Orx))) {
            $where = new Expr\Andx(func_get_args());
        }
        
        return $this->add('where', $where);
    }

    /**
     * Add a new WHERE statement with an AND
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.username LIKE ?')
     *         ->andWhere('u.is_active = 1');
     *
     * @param mixed $where The WHERE statement
     * @return QueryBuilder $qb
     * @see where()
     */
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
        
        return $this->add('where', $where, true);
    }

    /**
     * Add a new WHERE statement with an OR
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     *
     * @param mixed $where The WHERE statement
     * @return QueryBuilder $qb
     * @see where()
     */
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
        
        return $this->add('where', $where, true);
    }

    /**
     * Set the GROUP BY clause
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.id');
     *
     * @param string $groupBy  The GROUP BY clause
     * @return QueryBuilder $qb
     */
    public function groupBy($groupBy)
    {
        return $this->add('groupBy', new Expr\GroupBy(func_get_args()));
    }


    /**
     * Add to the existing GROUP BY clause
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.last_login');
     *         ->addGroupBy('u.created_at')
     *
     * @param string $groupBy  The GROUP BY clause
     * @return QueryBuilder $qb
     */
    public function addGroupBy($groupBy)
    {
        return $this->add('groupBy', new Expr\GroupBy(func_get_args()), true);
    }

    /**
     * Set the HAVING clause
     *
     * @param mixed $having 
     * @return QueryBuilder $qb
     */
    public function having($having)
    {
        if ( ! (func_num_args() == 1 && ($having instanceof Expr\Andx || $having instanceof Expr\Orx))) {
            $having = new Expr\Andx(func_get_args());
        }
        
        return $this->add('having', $having);
    }

    /**
     * Add to the existing HAVING clause with an AND
     *
     * @param mixed $having 
     * @return QueryBuilder $qb
     */
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

    /**
     * Add to the existing HAVING clause with an OR
     *
     * @param mixed $having 
     * @return QueryBuilder $qb
     */
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

    /**
     * Set the ORDER BY clause
     *
     * @param string $sort    What to sort on
     * @param string $order   Optional: The order to sort the results.
     * @return QueryBuilder $qb
     */
    public function orderBy($sort, $order = null)
    {
        return $this->add('orderBy', new Expr\OrderBy($sort, $order));
    }

    /**
     * Add to the existing ORDER BY clause
     *
     * @param string $sort    What to sort on
     * @param string $order   Optional: The order to sort the results.
     * @return QueryBuilder $qb
     */
    public function addOrderBy($sort, $order = null)
    {
        return $this->add('orderBy', new Expr\OrderBy($sort, $order), true);
    }

    private function _getDqlForDelete()
    {
         return 'DELETE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '));
    }

    private function _getDqlForUpdate()
    {
         return 'UPDATE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' '))
              . $this->_getReducedDqlQueryPart('set', array('pre' => ' SET ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '));
    }

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