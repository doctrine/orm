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
     * @psalm-param self::INNER_JOIN|self::LEFT_JOIN $joinType
     * @psalm-param self::ON|self::WITH|null $conditionType
     */
    public function __construct(
        protected string $joinType,
        protected string $join,
        protected string|null $alias = null,
        protected string|null $conditionType = null,
        protected string|Comparison|Composite|null $condition = null,
        protected string|null $indexBy = null,
    ) {
    }

    /** @psalm-return self::INNER_JOIN|self::LEFT_JOIN */
    public function getJoinType(): string
    {
        return $this->joinType;
    }

    public function getJoin(): string
    {
        return $this->join;
    }

    public function getAlias(): string|null
    {
        return $this->alias;
    }

    /** @psalm-return self::ON|self::WITH|null */
    public function getConditionType(): string|null
    {
        return $this->conditionType;
    }

    public function getCondition(): string|Comparison|Composite|null
    {
        return $this->condition;
    }

    public function getIndexBy(): string|null
    {
        return $this->indexBy;
    }

    public function __toString(): string
    {
        return strtoupper($this->joinType) . ' JOIN ' . $this->join
             . ($this->alias ? ' ' . $this->alias : '')
             . ($this->indexBy ? ' INDEX BY ' . $this->indexBy : '')
             . ($this->condition ? ' ' . strtoupper($this->conditionType) . ' ' . $this->condition : '');
    }
}
