<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class AggregateExpression extends Node
{
    /**
     * @param string                                    $functionName
     * @param PathExpression|SimpleArithmeticExpression $pathExpression
     * @param bool                                      $isDistinct     Some aggregate expressions support distinct, eg COUNT.
     */
    public function __construct(
        public $functionName,
        public $pathExpression,
        public $isDistinct,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkAggregateExpression($this);
    }
}
