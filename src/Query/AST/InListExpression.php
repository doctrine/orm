<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class InListExpression extends InExpression
{
    /** @var non-empty-list<mixed> */
    public $literals;

    /** @param non-empty-list<mixed> $literals */
    public function __construct(ArithmeticExpression $expression, array $literals, bool $not = false)
    {
        $this->literals = $literals;
        $this->not      = $not;

        parent::__construct($expression);
    }
}
