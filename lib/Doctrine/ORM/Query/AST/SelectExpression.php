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
    /** @var mixed */
    public $expression;

    /** @var string|null */
    public $fieldIdentificationVariable;

    /** @var bool */
    public $hiddenAliasResultVariable;

    /**
     * @param mixed       $expression
     * @param string|null $fieldIdentificationVariable
     * @param bool        $hiddenAliasResultVariable
     */
    public function __construct($expression, $fieldIdentificationVariable, $hiddenAliasResultVariable = false)
    {
        $this->expression                  = $expression;
        $this->fieldIdentificationVariable = $fieldIdentificationVariable;
        $this->hiddenAliasResultVariable   = $hiddenAliasResultVariable;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSelectExpression($this);
    }
}
