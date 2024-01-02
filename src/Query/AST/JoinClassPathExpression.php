<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * JoinClassPathExpression ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
 *
 * @link    www.doctrine-project.org
 */
class JoinClassPathExpression extends Node
{
    public function __construct(
        public mixed $abstractSchemaName,
        public mixed $aliasIdentificationVariable,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoinPathExpression($this);
    }
}
