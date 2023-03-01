<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SimpleSelectClause  ::= "SELECT" ["DISTINCT"] SimpleSelectExpression
 *
 * @link    www.doctrine-project.org
 */
class SimpleSelectClause extends Node
{
    public function __construct(
        public SimpleSelectExpression $simpleSelectExpression,
        public bool $isDistinct = false,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSimpleSelectClause($this);
    }
}
