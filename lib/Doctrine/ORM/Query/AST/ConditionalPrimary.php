<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
 *
 * @link    www.doctrine-project.org
 */
class ConditionalPrimary extends Node implements Phase2OptimizableConditional
{
    /** @var Node|null */
    public $simpleConditionalExpression;

    /** @var ConditionalExpression|Phase2OptimizableConditional|null */
    public $conditionalExpression;

    /** @return bool */
    public function isSimpleConditionalExpression()
    {
        return (bool) $this->simpleConditionalExpression;
    }

    /** @return bool */
    public function isConditionalExpression()
    {
        return (bool) $this->conditionalExpression;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkConditionalPrimary($this);
    }
}
