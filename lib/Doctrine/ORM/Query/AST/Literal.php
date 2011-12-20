<?php

namespace Doctrine\ORM\Query\AST;

class Literal extends Node
{
    const STRING = 1;
    const BOOLEAN = 2;
    const NUMERIC = 3;

    public $type;
    public $value;

    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function dispatch($walker)
    {
        return $walker->walkLiteral($this);
    }
}