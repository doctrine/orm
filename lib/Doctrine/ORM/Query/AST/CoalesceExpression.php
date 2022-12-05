<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * CoalesceExpression ::= "COALESCE" "(" ScalarExpression {"," ScalarExpression}* ")"
 *
 * @link    www.doctrine-project.org
 */
class CoalesceExpression extends Node
{
    /** @param mixed[] $scalarExpressions */
    public function __construct(public array $scalarExpressions)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkCoalesceExpression($this);
    }
}
