<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleSelectExpression ::= StateFieldPathExpression | IdentificationVariable
 *                          | (AggregateExpression [["AS"] FieldAliasIdentificationVariable])
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SimpleSelectExpression extends Node
{
    /**
     * @var Node
     */
    public $expression;

    /**
     * @var string
     */
    public $fieldIdentificationVariable;

    /**
     * @param Node $expression
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkSimpleSelectExpression($this);
    }
}
