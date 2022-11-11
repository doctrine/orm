<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ComparisonExpression ::= ArithmeticExpression ComparisonOperator ( QuantifiedExpression | ArithmeticExpression ) |
 *                          StringExpression ComparisonOperator (StringExpression | QuantifiedExpression) |
 *                          BooleanExpression ("=" | "<>" | "!=") (BooleanExpression | QuantifiedExpression) |
 *                          EnumExpression ("=" | "<>" | "!=") (EnumExpression | QuantifiedExpression) |
 *                          DatetimeExpression ComparisonOperator (DatetimeExpression | QuantifiedExpression) |
 *                          EntityExpression ("=" | "<>") (EntityExpression | QuantifiedExpression)
 *
 * @link    www.doctrine-project.org
 */
class ComparisonExpression extends Node
{
    /**
     * @param Node|string $leftExpression
     * @param string      $operator
     * @param Node|string $rightExpression
     */
    public function __construct(
        public $leftExpression,
        public $operator,
        public $rightExpression,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkComparisonExpression($this);
    }
}
