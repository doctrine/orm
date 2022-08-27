<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * NullComparisonExpression ::= (SingleValuedPathExpression | InputParameter) "IS" ["NOT"] "NULL"
 *
 * @link    www.doctrine-project.org
 */
class NullComparisonExpression extends Node
{
    /** @var bool */
    public $not;

    /** @param Node $expression */
    public function __construct(public $expression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkNullComparisonExpression($this);
    }
}
