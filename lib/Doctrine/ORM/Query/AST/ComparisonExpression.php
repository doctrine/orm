<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

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

    /** @var string */
    public $operator;

    /**
     * @param Node|string $leftExpr
     * @param string      $operator
     * @param Node|string $rightExpr
     */
    public function __construct($leftExpr, $operator, $rightExpr)
    {
        $this->leftExpression  = $leftExpr;
        $this->rightExpression = $rightExpr;
        $this->operator        = $operator;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkComparisonExpression($this);
    }
}
