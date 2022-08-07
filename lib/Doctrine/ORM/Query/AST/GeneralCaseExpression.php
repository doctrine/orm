<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * GeneralCaseExpression ::= "CASE" WhenClause {WhenClause}* "ELSE" ScalarExpression "END"
 *
 * @link    www.doctrine-project.org
 */
class GeneralCaseExpression extends Node
{
    /** @var mixed[] */
    public $whenClauses = [];

    /** @var mixed */
    public $elseScalarExpression = null;

    /**
     * @param mixed[] $whenClauses
     * @param mixed   $elseScalarExpression
     */
    public function __construct(array $whenClauses, $elseScalarExpression)
    {
        $this->whenClauses          = $whenClauses;
        $this->elseScalarExpression = $elseScalarExpression;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkGeneralCaseExpression($this);
    }
}
