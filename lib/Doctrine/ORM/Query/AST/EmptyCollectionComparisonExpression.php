<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * EmptyCollectionComparisonExpression ::= CollectionValuedPathExpression "IS" ["NOT"] "EMPTY"
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EmptyCollectionComparisonExpression extends Node
{
    /**
     * @var PathExpression
     */
    public $expression;

    /**
     * @var bool
     */
    public $not;

    /**
     * @param PathExpression $expression
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
        return $sqlWalker->walkEmptyCollectionComparisonExpression($this);
    }
}
