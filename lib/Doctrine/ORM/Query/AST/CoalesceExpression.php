<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * CoalesceExpression ::= "COALESCE" "(" ScalarExpression {"," ScalarExpression}* ")"
 */
class CoalesceExpression extends Node
{
    /** @var Node[] */
    public $scalarExpressions = [];

    /**
     * @param Node[] $scalarExpressions
     */
    public function __construct(array $scalarExpressions)
    {
        $this->scalarExpressions = $scalarExpressions;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkCoalesceExpression($this);
    }
}
