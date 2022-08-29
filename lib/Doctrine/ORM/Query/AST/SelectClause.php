<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SelectClause = "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
 *
 * @link    www.doctrine-project.org
 */
class SelectClause extends Node
{
    /** @var mixed[] */
    public $selectExpressions = [];

    /**
     * @param mixed[] $selectExpressions
     * @param bool    $isDistinct
     */
    public function __construct(array $selectExpressions, public $isDistinct)
    {
        $this->selectExpressions = $selectExpressions;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSelectClause($this);
    }
}
