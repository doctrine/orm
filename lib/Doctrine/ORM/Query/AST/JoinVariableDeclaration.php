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
    /** @var Join */
    public $join;

    /** @var IndexBy|null */
    public $indexBy;

    /**
     * @param Join         $join
     * @param IndexBy|null $indexBy
     */
    public function __construct($join, $indexBy)
    {
        $this->join    = $join;
        $this->indexBy = $indexBy;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoinVariableDeclaration($this);
    }
}
