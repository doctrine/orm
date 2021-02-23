<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleCaseExpression ::= "CASE" CaseOperand SimpleWhenClause {SimpleWhenClause}* "ELSE" ScalarExpression "END"
 */
class SimpleCaseExpression extends Node
{
    /** @var PathExpression */
    public $caseOperand;

    /** @var SimpleWhenClause[] */
    public $simpleWhenClauses = [];

    /** @var mixed */
    public $elseScalarExpression;

    /**
     * @param PathExpression     $caseOperand
     * @param SimpleWhenClause[] $simpleWhenClauses
     * @param mixed              $elseScalarExpression
     */
    public function __construct($caseOperand, array $simpleWhenClauses, $elseScalarExpression)
    {
        $this->caseOperand          = $caseOperand;
        $this->simpleWhenClauses    = $simpleWhenClauses;
        $this->elseScalarExpression = $elseScalarExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkSimpleCaseExpression($this);
    }
}
