<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * NewObjectNullArg ::= "NULL"
 *
 * Represents a NULL argument passed to the constructor of a NewObjectExpression.
 *
 * @link    www.doctrine-project.org
 */
class NewObjectNullArg extends Node
{
    public function dispatch(SqlWalker $walker): string
    {
        return 'NULL';
    }
}
