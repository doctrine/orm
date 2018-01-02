<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class PartialObjectExpression extends Node
{
    /**
     * @var string
     */
    public $identificationVariable;

    /**
     * @var array
     */
    public $partialFieldSet;

    /**
     * @param string $identificationVariable
     * @param array  $partialFieldSet
     */
    public function __construct($identificationVariable, array $partialFieldSet)
    {
        $this->identificationVariable = $identificationVariable;
        $this->partialFieldSet = $partialFieldSet;
    }
}
