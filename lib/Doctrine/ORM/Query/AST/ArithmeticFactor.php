<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
 */
class ArithmeticFactor extends Node
{
    /** @var Node */
    public $arithmeticPrimary;

    /**
     * NULL represents no sign, TRUE means positive and FALSE means negative sign.
     *
     * @var bool|null
     */
    public $sign;

    /**
     * @param mixed     $arithmeticPrimary
     * @param bool|null $sign
     */
    public function __construct($arithmeticPrimary, $sign = null)
    {
        $this->arithmeticPrimary = $arithmeticPrimary;
        $this->sign              = $sign;
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
