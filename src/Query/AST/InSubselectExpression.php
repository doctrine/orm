<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/** @phpstan-ignore class.extendsDeprecatedClass */
class InSubselectExpression extends InExpression
{
    /** @var Subselect */
    public $subselect;

    public function __construct(ArithmeticExpression $expression, Subselect $subselect, bool $not = false)
    {
        $this->subselect = $subselect;
        $this->not       = $not;

        // @phpstan-ignore staticMethod.deprecatedClass
        parent::__construct($expression);
    }
}
