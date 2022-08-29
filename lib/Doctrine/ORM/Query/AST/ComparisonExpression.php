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
    /** @var Node|string */
    public $leftExpression;

    /** @var Node|string */
    public $rightExpression;

    /**
     * @param Node|string $leftExpr
     * @param string      $operator
     * @param Node|string $rightExpr
     */
    public function __construct($leftExpr, public $operator, $rightExpr)
    {
        $this->leftExpression  = $leftExpr;
        $this->rightExpression = $rightExpr;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkComparisonExpression($this);
    }
}
