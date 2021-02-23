<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * InExpression ::= ArithmeticExpression ["NOT"] "IN" "(" (Literal {"," Literal}* | Subselect) ")"
 */
class InExpression extends Node
{
    /** @var bool */
    public $not;

    /** @var ArithmeticExpression */
    public $expression;

    /** @var Literal[] */
    public $literals = [];

    /** @var Subselect|null */
    public $subselect;

    /**
     * @param ArithmeticExpression $expression
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkInExpression($this);
    }
}
