<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SimpleWhenClause ::= "WHEN" ScalarExpression "THEN" ScalarExpression
 *
 * @link    www.doctrine-project.org
 */
class SimpleWhenClause extends Node
{
    /**
     * @param mixed $caseScalarExpression
     * @param mixed $thenScalarExpression
     */
    public function __construct(public $caseScalarExpression = null, public $thenScalarExpression = null)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkWhenClauseExpression($this);
    }
}
