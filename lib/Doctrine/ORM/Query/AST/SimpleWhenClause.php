<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleWhenClause ::= "WHEN" ScalarExpression "THEN" ScalarExpression
 *
 * @since   2.2
 * 
 * @link    www.doctrine-project.org
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SimpleWhenClause extends Node
{
    /**
     * @var mixed
     */
    public $caseScalarExpression = null;

    /**
     * @var mixed
     */
    public $thenScalarExpression = null;

    /**
     * @param mixed $caseScalarExpression
     * @param mixed $thenScalarExpression
     */
    public function __construct($caseScalarExpression, $thenScalarExpression)
    {
        $this->caseScalarExpression = $caseScalarExpression;
        $this->thenScalarExpression = $thenScalarExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkWhenClauseExpression($this);
    }
}
