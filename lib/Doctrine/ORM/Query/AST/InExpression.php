<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * InExpression ::= ArithmeticExpression ["NOT"] "IN" "(" (Literal {"," Literal}* | Subselect) ")"
 *
 * @link    www.doctrine-project.org
 */
class InExpression extends Node
{
    /** @var bool */
    public $not;

    /** @var mixed[] */
    public $literals = [];

    /** @var Subselect|null */
    public $subselect;

    /** @param ArithmeticExpression $expression */
    public function __construct(public $expression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkInExpression($this);
    }
}
