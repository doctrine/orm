<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class InListExpression extends Node
{
    /** @param non-empty-list<mixed> $literals */
    public function __construct(
        public ArithmeticExpression $expression,
        public array $literals,
        public bool $not = false,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkInListExpression($this);
    }
}
