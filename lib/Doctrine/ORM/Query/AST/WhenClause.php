<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * WhenClause ::= "WHEN" ConditionalExpression "THEN" ScalarExpression
 *
 * @link    www.doctrine-project.org
 */
class WhenClause extends Node
{
    /**
     * @param ConditionalExpression $caseConditionExpression
     * @param mixed                 $thenScalarExpression
     */
    public function __construct(public $caseConditionExpression = null, public $thenScalarExpression = null)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkWhenClauseExpression($this);
    }
}
