<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class AggregateExpression extends Node
{
    /** @var string */
    public $functionName;

    /** @var PathExpression|SimpleArithmeticExpression */
    public $pathExpression;

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
    public function __construct($functionName, $pathExpression, $isDistinct)
    {
        $this->functionName   = $functionName;
        $this->pathExpression = $pathExpression;
        $this->isDistinct     = $isDistinct;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkAggregateExpression($this);
    }
}
