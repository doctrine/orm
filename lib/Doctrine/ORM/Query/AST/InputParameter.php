<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * Description of InputParameter.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class InputParameter extends Node
{
    /**
     * @var bool
     */
    public $isNamed;

    /**
     * @var string
     */
    public $name;

    /**
     * @param string $value
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function __construct($value)
    {
        if (strlen($value) === 1) {
            throw \Doctrine\ORM\Query\QueryException::invalidParameterFormat($value);
        }

        $param = substr($value, 1);
        $this->isNamed = ! is_numeric($param);
        $this->name = $param;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkInputParameter($this);
    }
}
