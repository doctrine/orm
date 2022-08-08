<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ParenthesisExpression ::= "(" ArithmeticPrimary ")"
 */
class ParenthesisExpression extends Node
{
    /** @var Node */
    public $expression;

    public function __construct(Node $expression)
    {
        $this->expression = $expression;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkParenthesisExpression($this);
    }
}
