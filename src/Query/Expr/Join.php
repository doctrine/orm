<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use function strtoupper;

/**
 * Expression class for DQL join.
 *
 * @link    www.doctrine-project.org
 */
class Join
{
    public const INNER_JOIN = 'INNER';
    public const LEFT_JOIN  = 'LEFT';

    public const ON   = 'ON';
    public const WITH = 'WITH';

    /**
     * @var string
     * @psalm-var self::INNER_JOIN|self::LEFT_JOIN
     */
    protected $joinType;

    /** @var string */
    protected $join;

    /** @var string|null */
    protected $alias;

    /**
     * @var string|null
     * @psalm-var self::ON|self::WITH|null
     */
    protected $conditionType;

    /** @var string|Comparison|Composite|Func|null */
    protected $condition;

    /** @var string|null */
    protected $indexBy;

    /**
     * @param string                                $joinType      The condition type constant. Either INNER_JOIN or LEFT_JOIN.
     * @param string                                $join          The relationship to join.
     * @param string|null                           $alias         The alias of the join.
     * @param string|null                           $conditionType The condition type constant. Either ON or WITH.
     * @param string|Comparison|Composite|Func|null $condition     The condition for the join.
     * @param string|null                           $indexBy       The index for the join.
     * @psalm-param self::INNER_JOIN|self::LEFT_JOIN $joinType
     * @psalm-param self::ON|self::WITH|null $conditionType
     */
    public function __construct($joinType, $join, $alias = null, $conditionType = null, $condition = null, $indexBy = null)
    {
        $this->joinType      = $joinType;
        $this->join          = $join;
        $this->alias         = $alias;
        $this->conditionType = $conditionType;
        $this->condition     = $condition;
        $this->indexBy       = $indexBy;
    }

    /**
     * @return string
     * @psalm-return self::INNER_JOIN|self::LEFT_JOIN
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /** @return string */
    public function getJoin()
    {
        return $this->join;
    }

    /** @return string|null */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string|null
     * @psalm-return self::ON|self::WITH|null
     */
    public function getConditionType()
    {
        return $this->conditionType;
    }

    /** @return string|Comparison|Composite|Func|null */
    public function getCondition()
    {
        return $this->condition;
    }

    /** @return string|null */
    public function getIndexBy()
    {
        return $this->indexBy;
    }

    /** @return string */
    public function __toString()
    {
        return strtoupper($this->joinType) . ' JOIN ' . $this->join
             . ($this->alias ? ' ' . $this->alias : '')
             . ($this->indexBy ? ' INDEX BY ' . $this->indexBy : '')
             . ($this->condition ? ' ' . strtoupper($this->conditionType) . ' ' . $this->condition : '');
    }
}
