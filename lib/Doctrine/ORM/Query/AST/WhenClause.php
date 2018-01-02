<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * WhenClause ::= "WHEN" ConditionalExpression "THEN" ScalarExpression
 *
 * @since   2.2
 * 
 * @link    www.doctrine-project.org
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class WhenClause extends Node
{
    /**
     * @var ConditionalExpression
     */
    public $caseConditionExpression;

    /**
     * @var mixed
     */
    public $thenScalarExpression;

    /**
     * @param ConditionalExpression $caseConditionExpression
     * @param mixed                 $thenScalarExpression
     */
    public function __construct($caseConditionExpression, $thenScalarExpression)
    {
        $this->caseConditionExpression = $caseConditionExpression;
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
