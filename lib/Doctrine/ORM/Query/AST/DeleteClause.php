<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * DeleteClause ::= "DELETE" ["FROM"] AbstractSchemaName [["AS"] AliasIdentificationVariable]
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class DeleteClause extends Node
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
     * @param string $abstractSchemaName
     */
    public function __construct($abstractSchemaName)
    {
        $this->abstractSchemaName = $abstractSchemaName;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkDeleteClause($this);
    }
}
