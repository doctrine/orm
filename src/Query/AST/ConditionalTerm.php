<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
 *
 * @link    www.doctrine-project.org
 */
class ConditionalTerm extends Node implements Phase2OptimizableConditional
{
    /** @param mixed[] $conditionalFactors */
    public function __construct(public array $conditionalFactors)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkConditionalTerm($this);
    }
}
