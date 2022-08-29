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
    /** @var mixed[] */
    public $scalarExpressions = [];

    /** @param mixed[] $scalarExpressions */
    public function __construct(array $scalarExpressions)
    {
        $this->scalarExpressions = $scalarExpressions;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkCoalesceExpression($this);
    }
}
