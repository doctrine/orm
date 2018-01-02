<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class RangeVariableDeclaration extends Node
{
    /**
     * @var string
     */
    public $abstractSchemaName;

    /**
     * @var string
     */
    public $aliasIdentificationVariable;

    /**
     * @var boolean
     */
    public $isRoot;

    /**
     * @param string  $abstractSchemaName
     * @param string  $aliasIdentificationVar
     * @param boolean $isRoot
     */
    public function __construct($abstractSchemaName, $aliasIdentificationVar, $isRoot = true)
    {
        $this->abstractSchemaName          = $abstractSchemaName;
        $this->aliasIdentificationVariable = $aliasIdentificationVar;
        $this->isRoot                      = $isRoot;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkRangeVariableDeclaration($this);
    }
}
