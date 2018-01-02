<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for generating DQL functions.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Func
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * Creates a function, with the given argument.
     *
     * @param string $name
     * @param array  $arguments
     */
    public function __construct($name, $arguments)
    {
        $this->name         = $name;
        $this->arguments    = (array) $arguments;
    }

    /**
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
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
