<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class IdentificationVariableDeclaration extends Node
{
    /**
     * @var RangeVariableDeclaration|null
     */
    public $rangeVariableDeclaration;

    /**
     * @var IndexBy|null
     */
    public $indexBy;

    /**
     * @var array
     */
    public $joins = [];

    /**
     * @param RangeVariableDeclaration|null $rangeVariableDecl
     * @param IndexBy|null                  $indexBy
     * @param array                         $joins
     */
    public function __construct($rangeVariableDecl, $indexBy, array $joins)
    {
        $this->rangeVariableDeclaration = $rangeVariableDecl;
        $this->indexBy = $indexBy;
        $this->joins = $joins;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkIdentificationVariableDeclaration($this);
    }
}
