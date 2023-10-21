<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class HavingClause extends Node
{
    /** @var ConditionalExpression|Phase2OptimizableConditional */
    public $conditionalExpression;

    /** @param ConditionalExpression|Phase2OptimizableConditional $conditionalExpression */
    public function __construct($conditionalExpression)
    {
        $this->conditionalExpression = $conditionalExpression;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkHavingClause($this);
    }
}
