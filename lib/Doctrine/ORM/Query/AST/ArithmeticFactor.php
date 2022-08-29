<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
 *
 * @link    www.doctrine-project.org
 */
class ArithmeticFactor extends Node
{
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
    public function __construct(public $arithmeticPrimary, $sign = null)
    {
        $this->sign = $sign;
    }

    /** @return bool */
    public function isPositiveSigned()
    {
        return $this->sign === true;
    }

    /** @return bool */
    public function isNegativeSigned()
    {
        return $this->sign === false;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkArithmeticFactor($this);
    }
}
