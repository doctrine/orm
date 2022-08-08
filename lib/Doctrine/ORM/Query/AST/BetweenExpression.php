<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class BetweenExpression extends Node
{
    /** @var ArithmeticExpression */
    public $expression;

    /** @var ArithmeticExpression */
    public $leftBetweenExpression;

    /** @var ArithmeticExpression */
    public $rightBetweenExpression;

    /** @var bool */
    public $not;

    /**
     * @param ArithmeticExpression $expr
     * @param ArithmeticExpression $leftExpr
     * @param ArithmeticExpression $rightExpr
     */
    public function __construct($expr, $leftExpr, $rightExpr)
    {
        $this->expression             = $expr;
        $this->leftBetweenExpression  = $leftExpr;
        $this->rightBetweenExpression = $rightExpr;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkBetweenExpression($this);
    }
}
