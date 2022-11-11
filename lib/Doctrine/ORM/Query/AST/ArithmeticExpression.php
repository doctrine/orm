<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
 *
 * @link    www.doctrine-project.org
 */
class ArithmeticExpression extends Node
{
    /** @var SimpleArithmeticExpression|null */
    public $simpleArithmeticExpression;

    /** @var Subselect|null */
    public $subselect;

    public function isSimpleArithmeticExpression(): bool
    {
        return (bool) $this->simpleArithmeticExpression;
    }

    public function isSubselect(): bool
    {
        return (bool) $this->subselect;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkArithmeticExpression($this);
    }
}
