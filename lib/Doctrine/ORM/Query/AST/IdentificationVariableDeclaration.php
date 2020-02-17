<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
 */
class IdentificationVariableDeclaration extends Node
{
    /** @var RangeVariableDeclaration|null */
    public $rangeVariableDeclaration;

    /** @var IndexBy|null */
    public $indexBy;

    /** @var Join[] */
    public $joins = [];

    /**
     * @param RangeVariableDeclaration|null $rangeVariableDecl
     * @param IndexBy|null                  $indexBy
     * @param Join[]                        $joins
     */
    public function __construct($rangeVariableDecl, $indexBy, array $joins)
    {
        $this->rangeVariableDeclaration = $rangeVariableDecl;
        $this->indexBy                  = $indexBy;
        $this->joins                    = $joins;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkIdentificationVariableDeclaration($this);
    }
}
