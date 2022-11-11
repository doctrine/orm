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
    /**
     * @param string $abstractSchemaName
     * @param string $aliasIdentificationVariable
     * @param bool   $isRoot
     */
    public function __construct(
        public $abstractSchemaName,
        public $aliasIdentificationVariable,
        public $isRoot = true,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkRangeVariableDeclaration($this);
    }
}
