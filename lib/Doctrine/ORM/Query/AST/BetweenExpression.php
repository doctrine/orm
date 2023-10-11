<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class BetweenExpression extends Node
{
    /**
     * @param ArithmeticExpression $expression
     * @param ArithmeticExpression $leftBetweenExpression
     * @param ArithmeticExpression $rightBetweenExpression
     */
    public function __construct(
        public $expression,
        public $leftBetweenExpression,
        public $rightBetweenExpression,
        public bool $not = false,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkBetweenExpression($this);
    }
}
