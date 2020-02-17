<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * EmptyCollectionComparisonExpression ::= CollectionValuedPathExpression "IS" ["NOT"] "EMPTY"
 */
class EmptyCollectionComparisonExpression extends Node
{
    /** @var PathExpression */
    public $expression;

    /** @var bool */
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
