<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalFactor ::= ["NOT"] ConditionalPrimary
 */
class ConditionalFactor extends Node
{
    /** @var bool */
    public $not = false;

    /** @var ConditionalPrimary */
    public $conditionalPrimary;

    /**
     * @param ConditionalPrimary $conditionalPrimary
     */
    public function __construct($conditionalPrimary)
    {
        $this->conditionalPrimary = $conditionalPrimary;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkConditionalFactor($this);
    }
}
