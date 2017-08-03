<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * GeneralCaseExpression ::= "CASE" WhenClause {WhenClause}* "ELSE" ScalarExpression "END"
 *
 * @since   2.2
 *
 * @link    www.doctrine-project.org
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GeneralCaseExpression extends Node
{
    /**
     * @var array
     */
    public $whenClauses = [];

    /**
     * @var mixed
     */
    public $elseScalarExpression = null;

    /**
     * @param array $whenClauses
     * @param mixed $elseScalarExpression
     */
    public function __construct(array $whenClauses, $elseScalarExpression)
    {
        $this->whenClauses = $whenClauses;
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
