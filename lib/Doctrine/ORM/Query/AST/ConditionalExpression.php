<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ConditionalExpression extends Node
{
    /**
     * @var array
     */
    public $conditionalTerms = [];

    /**
     * @param array $conditionalTerms
     */
    public function __construct(array $conditionalTerms)
    {
        $this->conditionalTerms = $conditionalTerms;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkConditionalExpression($this);
    }
}
