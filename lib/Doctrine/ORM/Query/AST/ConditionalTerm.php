<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
 */
class ConditionalTerm extends Node
{
    /** @var ConditionalFactor[] */
    public $conditionalFactors = [];

    /**
     * @param ConditionalFactor[] $conditionalFactors
     */
    public function __construct(array $conditionalFactors)
    {
        $this->conditionalFactors = $conditionalFactors;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkConditionalTerm($this);
    }
}
