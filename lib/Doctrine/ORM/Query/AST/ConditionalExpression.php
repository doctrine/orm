<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
 */
class ConditionalExpression extends Node
{
    /** @var ConditionalTerm[] */
    public $conditionalTerms = [];

    /**
     * @param ConditionalTerm[] $conditionalTerms
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
