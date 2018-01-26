<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ParenthesisExpression ::= "(" ArithmeticPrimary ")"
 */
class ParenthesisExpression extends Node
{
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $expression;

    public function __construct(Node $expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkParenthesisExpression($this);
    }
}
