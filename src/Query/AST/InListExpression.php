<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/** @phpstan-ignore class.extendsDeprecatedClass */
class InListExpression extends InExpression
{
    /** @var non-empty-list<mixed> */
    public $literals;

    /** @param non-empty-list<mixed> $literals */
    public function __construct(ArithmeticExpression $expression, array $literals, bool $not = false)
    {
        $this->literals = $literals;
        $this->not      = $not;

        // @phpstan-ignore staticMethod.deprecatedClass
        parent::__construct($expression);
    }
}
