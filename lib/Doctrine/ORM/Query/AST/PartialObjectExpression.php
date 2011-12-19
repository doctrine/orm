<?php

namespace Doctrine\ORM\Query\AST;

class PartialObjectExpression extends Node
{
    public $identificationVariable;
    public $partialFieldSet;

    public function __construct($identificationVariable, array $partialFieldSet)
    {
        $this->identificationVariable = $identificationVariable;
        $this->partialFieldSet = $partialFieldSet;
    }
}