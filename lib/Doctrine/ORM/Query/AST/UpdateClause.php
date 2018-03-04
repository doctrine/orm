<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * UpdateClause ::= "UPDATE" AbstractSchemaName [["AS"] AliasIdentificationVariable] "SET" UpdateItem {"," UpdateItem}*
 */
class UpdateClause extends Node
{
    /** @var string */
    public $abstractSchemaName;

    /** @var string */
    public $aliasIdentificationVariable;

    /** @var UpdateItem[] */
    public $updateItems = [];

    /**
     * @param string       $abstractSchemaName
     * @param UpdateItem[] $updateItems
     */
    public function __construct($abstractSchemaName, array $updateItems)
    {
        $this->abstractSchemaName = $abstractSchemaName;
        $this->updateItems        = $updateItems;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkUpdateClause($this);
    }
}
