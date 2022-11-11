<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class Literal extends Node
{
    final public const STRING  = 1;
    final public const BOOLEAN = 2;
    final public const NUMERIC = 3;

    /** @psalm-param self::* $type */
    public function __construct(
        public int $type,
        public mixed $value,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkLiteral($this);
    }
}
