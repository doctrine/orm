<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SelectExpression ::= IdentificationVariable ["." "*"] | StateFieldPathExpression |
 *                      (AggregateExpression | "(" Subselect ")") [["AS"] ["HIDDEN"] FieldAliasIdentificationVariable]
 *
 * @link    www.doctrine-project.org
 */
class SelectExpression extends Node
{
    /**
     * @param mixed       $expression
     * @param string|null $fieldIdentificationVariable
     * @param bool        $hiddenAliasResultVariable
     */
    public function __construct(public $expression, public $fieldIdentificationVariable, public $hiddenAliasResultVariable = false)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSelectExpression($this);
    }
}
