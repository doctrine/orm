<?php


declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ParenthesisExpression ::= "(" ArithmeticPrimary ")"
 *
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since   2.4
 */
class ParenthesisExpression extends Node
{
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $expression;

    /**
     * @param \Doctrine\ORM\Query\AST\Node $expression
     */
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
