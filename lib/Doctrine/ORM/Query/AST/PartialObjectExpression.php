<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class PartialObjectExpression extends Node
{
    /** @param mixed[] $partialFieldSet */
    public function __construct(
        public string $identificationVariable,
        public array $partialFieldSet,
    ) {
    }
}
