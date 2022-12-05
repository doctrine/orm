<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SelectClause = "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
 *
 * @link    www.doctrine-project.org
 */
class SelectClause extends Node
{
    /** @param mixed[] $selectExpressions */
    public function __construct(
        public array $selectExpressions,
        public bool $isDistinct,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSelectClause($this);
    }
}
