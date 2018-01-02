<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * CoalesceExpression ::= "COALESCE" "(" ScalarExpression {"," ScalarExpression}* ")"
 *
 * @since   2.1
 *
 * @link    www.doctrine-project.org
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class CoalesceExpression extends Node
{
    /**
     * @var array
     */
    public $scalarExpressions = [];

    /**
     * @param array $scalarExpressions
     */
    public function __construct(array $scalarExpressions)
    {
        $this->scalarExpressions  = $scalarExpressions;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkCoalesceExpression($this);
    }
}
