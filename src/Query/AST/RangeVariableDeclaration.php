<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
 *
 * @link    www.doctrine-project.org
 */
class RangeVariableDeclaration extends Node
{
    public function __construct(
        public string $abstractSchemaName,
        public string $aliasIdentificationVariable,
        public bool $isRoot = true,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkRangeVariableDeclaration($this);
    }
}
