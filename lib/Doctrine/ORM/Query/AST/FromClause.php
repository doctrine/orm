<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * FromClause ::= "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
 *
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class FromClause extends Node
{
    /**
     * @var array
     */
    public $identificationVariableDeclarations = [];

    /**
     * @param array $identificationVariableDeclarations
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
