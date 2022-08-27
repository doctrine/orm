<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class AggregateExpression extends Node
{
    /**
     * Some aggregate expressions support distinct, eg COUNT.
     *
     * @var bool
     */
    public $isDistinct = false;

    /**
     * @param string                                    $functionName
     * @param PathExpression|SimpleArithmeticExpression $pathExpression
     * @param bool                                      $isDistinct
     */
    public function __construct(public $functionName, public $pathExpression, $isDistinct)
    {
        $this->isDistinct = $isDistinct;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkAggregateExpression($this);
    }
}
