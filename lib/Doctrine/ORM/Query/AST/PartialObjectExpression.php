<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class PartialObjectExpression extends Node
{
    /**
     * @param string  $identificationVariable
     * @param mixed[] $partialFieldSet
     */
    public function __construct(public $identificationVariable, public array $partialFieldSet)
    {
    }
}
