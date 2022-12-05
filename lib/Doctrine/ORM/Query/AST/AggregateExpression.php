<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class AggregateExpression extends Node
{
    /**
     * @param PathExpression|SimpleArithmeticExpression $pathExpression
     * @param bool                                      $isDistinct     Some aggregate expressions support distinct, eg COUNT.
     */
    public function __construct(
        public string $functionName,
        public $pathExpression,
        public bool $isDistinct,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkAggregateExpression($this);
    }
}
