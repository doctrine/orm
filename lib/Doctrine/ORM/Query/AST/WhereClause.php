<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * WhereClause ::= "WHERE" ConditionalExpression
 *
 * @link    www.doctrine-project.org
 */
class WhereClause extends Node
{
    /** @param ConditionalExpression|ConditionalTerm $conditionalExpression */
    public function __construct(public $conditionalExpression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkWhereClause($this);
    }
}
