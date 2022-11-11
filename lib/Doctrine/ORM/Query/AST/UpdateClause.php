<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * UpdateClause ::= "UPDATE" AbstractSchemaName [["AS"] AliasIdentificationVariable] "SET" UpdateItem {"," UpdateItem}*
 *
 * @link    www.doctrine-project.org
 */
class UpdateClause extends Node
{
    /** @var string */
    public $aliasIdentificationVariable;

    /**
     * @param string  $abstractSchemaName
     * @param mixed[] $updateItems
     */
    public function __construct(public $abstractSchemaName, public array $updateItems)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkUpdateClause($this);
    }
}
