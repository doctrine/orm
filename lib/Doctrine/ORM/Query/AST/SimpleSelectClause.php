<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SimpleSelectClause  ::= "SELECT" ["DISTINCT"] SimpleSelectExpression
 *
 * @link    www.doctrine-project.org
 */
class SimpleSelectClause extends Node
{
    /** @var bool */
    public $isDistinct = false;

    /** @var SimpleSelectExpression */
    public $simpleSelectExpression;

    /**
     * @param SimpleSelectExpression $simpleSelectExpression
     * @param bool                   $isDistinct
     */
    public function __construct($simpleSelectExpression, $isDistinct)
    {
        $this->simpleSelectExpression = $simpleSelectExpression;
        $this->isDistinct             = $isDistinct;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSimpleSelectClause($this);
    }
}
