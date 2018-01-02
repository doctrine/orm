<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * UpdateClause ::= "UPDATE" AbstractSchemaName [["AS"] AliasIdentificationVariable] "SET" UpdateItem {"," UpdateItem}*
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class UpdateClause extends Node
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
     * @var array
     */
    public $updateItems = [];

    /**
     * @param string $abstractSchemaName
     * @param array  $updateItems
     */
    public function __construct($abstractSchemaName, array $updateItems)
    {
        $this->abstractSchemaName = $abstractSchemaName;
        $this->updateItems = $updateItems;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkUpdateClause($this);
    }
}
