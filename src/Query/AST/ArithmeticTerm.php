<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
 *
 * @link    www.doctrine-project.org
 */
class ArithmeticTerm extends Node
{
    /** @param mixed[] $arithmeticFactors */
    public function __construct(public array $arithmeticFactors)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkArithmeticTerm($this);
    }
}
