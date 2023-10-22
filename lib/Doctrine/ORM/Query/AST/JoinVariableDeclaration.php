<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * JoinVariableDeclaration ::= Join [IndexBy]
 *
 * @link    www.doctrine-project.org
 */
class JoinVariableDeclaration extends Node
{
    public function __construct(public Join $join, public IndexBy|null $indexBy)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoinVariableDeclaration($this);
    }
}
