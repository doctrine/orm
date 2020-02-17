<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use function implode;

/**
 * Expression class for generating DQL functions.
 */
class Func
{
    /** @var string */
    protected $name;

    /** @var mixed[] */
    protected $arguments;

    /**
     * Creates a function, with the given argument.
     *
     * @param string  $name
     * @param mixed[] $arguments
     */
    public function __construct($name, $arguments)
    {
        $this->name      = $name;
        $this->arguments = (array) $arguments;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name . '(' . implode(', ', $this->arguments) . ')';
    }
}
