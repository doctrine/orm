<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for DQL join.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Join
{
    const INNER_JOIN    = 'INNER';
    const LEFT_JOIN     = 'LEFT';

    const ON            = 'ON';
    const WITH          = 'WITH';

    /**
     * @var string
     */
    protected $joinType;

    /**
     * @var string
     */
    protected $join;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var string
     */
    protected $conditionType;

    /**
     * @var string
     */
    protected $condition;

    /**
     * @var string
     */
    protected $indexBy;

    /**
     * @param string      $joinType      The condition type constant. Either INNER_JOIN or LEFT_JOIN.
     * @param string      $join          The relationship to join.
     * @param string|null $alias         The alias of the join.
     * @param string|null $conditionType The condition type constant. Either ON or WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     */
    public function __construct($joinType, $join, $alias = null, $conditionType = null, $condition = null, $indexBy = null)
    {
        $this->joinType       = $joinType;
        $this->join           = $join;
        $this->alias          = $alias;
        $this->conditionType  = $conditionType;
        $this->condition      = $condition;
        $this->indexBy        = $indexBy;
    }

    /**
     * @return string 
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * @return string
     */
    public function getJoin()
    {
        return $this->join;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getConditionType()
    {
        return $this->conditionType;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return string
     */
    public function getIndexBy()
    {
        return $this->indexBy;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strtoupper($this->joinType) . ' JOIN ' . $this->join
             . ($this->alias ? ' ' . $this->alias : '')
             . ($this->indexBy ? ' INDEX BY ' . $this->indexBy : '')
             . ($this->condition ? ' ' . strtoupper($this->conditionType) . ' ' . $this->condition : '');
    }
}
