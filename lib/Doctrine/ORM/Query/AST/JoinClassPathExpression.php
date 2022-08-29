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
    /** @var mixed */
    public $aliasIdentificationVariable;

    /**
     * @param mixed $abstractSchemaName
     * @param mixed $aliasIdentificationVar
     */
    public function __construct(public $abstractSchemaName, $aliasIdentificationVar)
    {
        $this->aliasIdentificationVariable = $aliasIdentificationVar;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoinPathExpression($this);
    }
}
