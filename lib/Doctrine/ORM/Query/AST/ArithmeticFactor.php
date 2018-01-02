<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ArithmeticFactor extends Node
{
    /**
     * @var mixed
     */
    public $arithmeticPrimary;

    /**
     * NULL represents no sign, TRUE means positive and FALSE means negative sign.
     *
     * @var null|boolean
     */
    public $sign;

    /**
     * @param mixed     $arithmeticPrimary
     * @param null|bool $sign
     */
    public function __construct($arithmeticPrimary, $sign = null)
    {
        $this->arithmeticPrimary = $arithmeticPrimary;
        $this->sign = $sign;
    }

    /**
     * @return bool
     */
    public function isPositiveSigned()
    {
        return $this->sign === true;
    }

    /**
     * @return bool
     */
    public function isNegativeSigned()
    {
        return $this->sign === false;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkArithmeticFactor($this);
    }
}
