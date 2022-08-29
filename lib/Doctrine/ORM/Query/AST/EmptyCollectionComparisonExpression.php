<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * EmptyCollectionComparisonExpression ::= CollectionValuedPathExpression "IS" ["NOT"] "EMPTY"
 *
 * @link    www.doctrine-project.org
 */
class EmptyCollectionComparisonExpression extends Node
{
    /** @var bool */
    public $not;

    /** @param PathExpression $expression */
    public function __construct(public $expression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkEmptyCollectionComparisonExpression($this);
    }
}
