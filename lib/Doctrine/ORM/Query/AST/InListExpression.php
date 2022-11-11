<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class InListExpression extends InExpression
{
    /** @param non-empty-list<mixed> $literals */
    public function __construct(ArithmeticExpression $expression, public array $literals, bool $not = false)
    {
        $this->not = $not;

        parent::__construct($expression);
    }
}
