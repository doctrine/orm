<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\QueryException;

use function is_numeric;
use function strlen;
use function substr;

class InputParameter extends Node
{
    /** @var bool */
    public $isNamed;

    /** @var string */
    public $name;

    /**
     * @param string $value
     *
     * @throws QueryException
     */
    public function __construct($value)
    {
        if (strlen($value) === 1) {
            throw QueryException::invalidParameterFormat($value);
        }

        $param         = substr($value, 1);
        $this->isNamed = ! is_numeric($param);
        $this->name    = $param;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkInputParameter($this);
    }
}
