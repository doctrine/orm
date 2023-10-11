<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
 *
 * @link    www.doctrine-project.org
 */
class IdentificationVariableDeclaration extends Node
{
    /**
     * @param RangeVariableDeclaration|null $rangeVariableDeclaration
     * @param IndexBy|null                  $indexBy
     * @param mixed[]                       $joins
     */
    public function __construct(
        public $rangeVariableDeclaration = null,
        public $indexBy = null,
        public array $joins = [],
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkIdentificationVariableDeclaration($this);
    }
}
