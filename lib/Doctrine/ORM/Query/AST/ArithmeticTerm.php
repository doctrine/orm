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
    /** @var mixed[] */
    public $arithmeticFactors;

    /** @param mixed[] $arithmeticFactors */
    public function __construct(array $arithmeticFactors)
    {
        $this->arithmeticFactors = $arithmeticFactors;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkArithmeticTerm($this);
    }
}
