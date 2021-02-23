<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * FromClause ::= "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
 */
class FromClause extends Node
{
    /** @var IdentificationVariableDeclaration[] */
    public $identificationVariableDeclarations = [];

    /**
     * @param IdentificationVariableDeclaration[] $identificationVariableDeclarations
     */
    public function __construct(array $identificationVariableDeclarations)
    {
        $this->identificationVariableDeclarations = $identificationVariableDeclarations;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkFromClause($this);
    }
}
