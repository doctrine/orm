<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * GeneralCaseExpression ::= "CASE" WhenClause {WhenClause}* "ELSE" ScalarExpression "END"
 */
class GeneralCaseExpression extends Node
{
    /** @var WhenClause[] */
    public $whenClauses = [];

    /** @var mixed */
    public $elseScalarExpression;

    /**
     * @param WhenClause[] $whenClauses
     * @param mixed        $elseScalarExpression
     */
    public function __construct(array $whenClauses, $elseScalarExpression)
    {
        $this->whenClauses          = $whenClauses;
        $this->elseScalarExpression = $elseScalarExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkGeneralCaseExpression($this);
    }
}
