<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
 *
 * @link    www.doctrine-project.org
 */
class SimpleArithmeticExpression extends Node
{
    /** @var mixed[] */
    public $arithmeticTerms = [];

    /** @param mixed[] $arithmeticTerms */
    public function __construct(array $arithmeticTerms)
    {
        $this->arithmeticTerms = $arithmeticTerms;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSimpleArithmeticExpression($this);
    }
}
