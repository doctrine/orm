<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SimpleCaseExpression ::= "CASE" CaseOperand SimpleWhenClause {SimpleWhenClause}* "ELSE" ScalarExpression "END"
 *
 * @link    www.doctrine-project.org
 */
class SimpleCaseExpression extends Node
{
    /** @var PathExpression */
    public $caseOperand = null;

    /** @var mixed[] */
    public $simpleWhenClauses = [];

    /** @var mixed */
    public $elseScalarExpression = null;

    /**
     * @param PathExpression $caseOperand
     * @param mixed[]        $simpleWhenClauses
     * @param mixed          $elseScalarExpression
     */
    public function __construct($caseOperand, array $simpleWhenClauses, $elseScalarExpression)
    {
        $this->caseOperand          = $caseOperand;
        $this->simpleWhenClauses    = $simpleWhenClauses;
        $this->elseScalarExpression = $elseScalarExpression;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSimpleCaseExpression($this);
    }
}
