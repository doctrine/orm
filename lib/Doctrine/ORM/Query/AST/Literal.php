<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class Literal extends Node
{
    public const STRING  = 1;
    public const BOOLEAN = 2;
    public const NUMERIC = 3;

    /**
     * @var int
     * @psalm-var self::*
     */
    public $type;

    /**
     * @param int   $type
     * @param mixed $value
     * @psalm-param self::* $type
     */
    public function __construct($type, public $value)
    {
        $this->type = $type;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkLiteral($this);
    }
}
