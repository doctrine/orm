<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
 *
 * @link    www.doctrine-project.org
 */
class ConditionalExpression extends Node
{
    /** @param mixed[] $conditionalTerms */
    public function __construct(public array $conditionalTerms)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkConditionalExpression($this);
    }
}
