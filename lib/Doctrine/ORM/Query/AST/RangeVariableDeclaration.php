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
    /** @var string */
    public $aliasIdentificationVariable;

    /**
     * @param string $abstractSchemaName
     * @param string $aliasIdentificationVar
     * @param bool   $isRoot
     */
    public function __construct(public $abstractSchemaName, $aliasIdentificationVar, public $isRoot = true)
    {
        $this->aliasIdentificationVariable = $aliasIdentificationVar;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkRangeVariableDeclaration($this);
    }
}
