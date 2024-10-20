<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * IndexBy ::= "INDEX" "BY" SingleValuedPathExpression
 *
 * @link    www.doctrine-project.org
 */
class IndexBy extends Node
{
    public function __construct(public PathExpression $singleValuedPathExpression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        $walker->walkIndexBy($this);

        return '';
    }
}
