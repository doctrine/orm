<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

class InSubselectExpression extends InExpression
{
    /** @var Subselect */
    public $subselect;

    public function __construct(ArithmeticExpression $expression, Subselect $subselect, bool $not = false)
    {
        $this->subselect = $subselect;
        $this->not       = $not;

        parent::__construct($expression);
    }
}
