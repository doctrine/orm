<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class NamedScalarExpression extends Node
{
    public function __construct(
        public readonly Node $innerExpression,
        public readonly string|null $name = null,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $this->innerExpression->dispatch($walker);
    }
}
