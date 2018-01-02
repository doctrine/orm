<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class Literal extends Node
{
    const STRING = 1;
    const BOOLEAN = 2;
    const NUMERIC = 3;

    /**
     * @var int
     */
    public $type;

    /**
     * @var mixed
     */
    public $value;

    /**
     * @param int   $type
     * @param mixed $value
     */
    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkLiteral($this);
    }
}
