<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleCaseExpression ::= "CASE" CaseOperand SimpleWhenClause {SimpleWhenClause}* "ELSE" ScalarExpression "END"
 *
 * @since   2.2
 *
 * @link    www.doctrine-project.org
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SimpleCaseExpression extends Node
{
    /**
     * @var PathExpression
     */
    public $caseOperand;

    /**
     * @var array
     */
    public $simpleWhenClauses = [];

    /**
     * @var mixed
     */
    public $elseScalarExpression;

    /**
     * @param PathExpression $caseOperand
     * @param array          $simpleWhenClauses
     * @param mixed          $elseScalarExpression
     */
    public function __construct($caseOperand, array $simpleWhenClauses, $elseScalarExpression)
    {
        $this->caseOperand = $caseOperand;
        $this->simpleWhenClauses = $simpleWhenClauses;
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
