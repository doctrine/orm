<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SelectClause = "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
 */
class SelectClause extends Node
{
    /** @var bool */
    public $isDistinct;

    /** @var SelectExpression[] */
    public $selectExpressions = [];

    /**
     * @param SelectExpression[] $selectExpressions
     * @param bool               $isDistinct
     */
    public function __construct(array $selectExpressions, $isDistinct)
    {
        $this->isDistinct        = $isDistinct;
        $this->selectExpressions = $selectExpressions;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkSelectClause($this);
    }
}
