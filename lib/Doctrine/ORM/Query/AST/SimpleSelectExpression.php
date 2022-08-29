<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SimpleSelectExpression ::= StateFieldPathExpression | IdentificationVariable
 *                          | (AggregateExpression [["AS"] FieldAliasIdentificationVariable])
 *
 * @link    www.doctrine-project.org
 */
class SimpleSelectExpression extends Node
{
    /** @var string */
    public $fieldIdentificationVariable;

    /** @param Node|string $expression */
    public function __construct(public $expression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSimpleSelectExpression($this);
    }
}
