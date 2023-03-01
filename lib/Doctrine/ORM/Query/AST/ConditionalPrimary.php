<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
 *
 * @link    www.doctrine-project.org
 */
class ConditionalPrimary extends Node
{
    public Node|null $simpleConditionalExpression = null;

    public ConditionalExpression|null $conditionalExpression = null;

    public function isSimpleConditionalExpression(): bool
    {
        return (bool) $this->simpleConditionalExpression;
    }

    public function isConditionalExpression(): bool
    {
        return (bool) $this->conditionalExpression;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkConditionalPrimary($this);
    }
}
