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
    /**
     * @param Join         $join
     * @param IndexBy|null $indexBy
     */
    public function __construct(public $join, public $indexBy)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoinVariableDeclaration($this);
    }
}
